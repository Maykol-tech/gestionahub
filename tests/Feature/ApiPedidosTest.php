<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AjustesNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-62: API de pedidos con token del negocio y aviso (webhook) al cambiar de estado. */
class ApiPedidosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $t = Tenant::create(['nombre_negocio' => 'Polleria Sol', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');

        return $u;
    }

    private function token(User $u): string
    {
        return $this->actingAs($u)->postJson(route('admin.api.tokens.crear'), ['nombre' => 'Mi web'])->assertCreated()->json('token');
    }

    private function conToken(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeaders(['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json']);
    }

    private function producto(int $tenant, array $extra = []): Producto
    {
        return Producto::withoutGlobalScopes()->create($extra + ['tenant_id' => $tenant, 'nombre' => 'Pollo a la brasa', 'precio' => 60, 'stock' => 10, 'sku' => 'POLLO-1', 'activo' => true]);
    }

    public function test_pagina_crea_y_revoca_tokens(): void
    {
        $u = $this->admin();
        $this->actingAs($u)->get(route('admin.api'))->assertOk()->assertSee('Tokens de acceso')->assertSee('Crear un pedido');
        $token = $this->token($u);
        $this->assertNotEmpty($token);
        $id = $this->actingAs($u)->get(route('admin.api'))->viewData('tokens')->first()['id'];

        $this->conToken($token)->getJson(route('api.v1.pedidos'))->assertOk();

        $this->actingAs($u)->deleteJson(route('admin.api.tokens.revocar', $id))->assertOk();
        $this->conToken($token)->getJson(route('api.v1.pedidos'))->assertUnauthorized();
    }

    public function test_lista_ve_crea_y_cambia_estado(): void
    {
        $u = $this->admin();
        $p = $this->producto($u->tenant_id, ['precio_oferta' => 50]);
        $token = $this->token($u);

        $r = $this->conToken($token)->postJson(route('api.v1.pedidos.crear'), [
            'cliente' => ['nombre' => 'Ana Pérez', 'telefono' => '987 654 321'], 'tipo' => 'delivery', 'direccion' => 'Av. Sol 1',
            'metodo_pago' => 'yape', 'costo_envio' => 5, 'referencia_externa' => 'WEB-1',
            'items' => [['sku' => 'POLLO-1', 'cantidad' => 2]],
        ])->assertCreated();
        $this->assertEquals(105, $r->json('data.total')); // precio de oferta del catálogo + envío
        $this->assertSame('app', $r->json('data.canal'));
        $this->assertSame(8, $p->fresh()->stock);
        $numero = $r->json('data.numero');

        $this->conToken($token)->getJson(route('api.v1.pedidos', ['estado' => 'pendiente']))->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.numero', $numero);
        $this->conToken($token)->getJson(route('api.v1.pedidos', ['estado' => 'entregado']))->assertJsonPath('meta.total', 0);
        $this->conToken($token)->getJson(route('api.v1.pedidos.ver', $numero))->assertOk()->assertJsonPath('data.items.0.cantidad', 2);

        $this->conToken($token)->patchJson(route('api.v1.pedidos.estado', $numero), ['estado' => 'entregado'])->assertOk()
            ->assertJsonPath('data.estado', 'entregado')->assertJsonPath('data.pagado', true);

        // Cancelar uno nuevo devuelve el stock
        $id = $this->conToken($token)->postJson(route('api.v1.pedidos.crear'), ['cliente' => ['nombre' => 'Luis'], 'items' => [['producto_id' => $p->id, 'cantidad' => 3]]])->json('data.id');
        $this->assertSame(5, $p->fresh()->stock);
        $this->conToken($token)->patchJson(route('api.v1.pedidos.estado', $id), ['estado' => 'cancelado', 'motivo' => 'Sin stock'])->assertOk();
        $this->assertSame(8, $p->fresh()->stock);
        $this->conToken($token)->patchJson(route('api.v1.pedidos.estado', $id), ['estado' => 'listo'])->assertStatus(422);
    }

    public function test_validaciones_y_stock(): void
    {
        $u = $this->admin();
        $this->producto($u->tenant_id, ['stock' => 1]);
        $token = $this->token($u);
        $this->conToken($token)->postJson(route('api.v1.pedidos.crear'), ['items' => []])->assertStatus(422)->assertJsonValidationErrors(['cliente.nombre', 'items']);
        $this->conToken($token)->postJson(route('api.v1.pedidos.crear'), ['cliente' => ['nombre' => 'X'], 'items' => [['sku' => 'POLLO-1', 'cantidad' => 2]]])
            ->assertStatus(422)->assertJsonValidationErrors(['items.0']);
        $this->conToken($token)->postJson(route('api.v1.pedidos.crear'), ['cliente' => ['nombre' => 'X'], 'items' => [['sku' => 'NO-EXISTE', 'cantidad' => 1]]])->assertStatus(422);
        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_no_ve_pedidos_de_otro_negocio_ni_usa_su_catalogo(): void
    {
        $u = $this->admin();
        $otro = $this->admin();
        $ajeno = Pedido::withoutGlobalScopes()->create(['tenant_id' => $otro->tenant_id, 'numero' => 'P-0001', 'canal' => 'manual', 'estado' => 'pendiente', 'tipo' => 'recojo', 'subtotal' => 10, 'total' => 10, 'metodo_pago' => 'efectivo']);
        $prodAjeno = $this->producto($otro->tenant_id);
        $token = $this->token($u);

        $this->conToken($token)->getJson(route('api.v1.pedidos'))->assertJsonPath('meta.total', 0);
        $this->conToken($token)->getJson(route('api.v1.pedidos.ver', $ajeno->id))->assertNotFound();
        $this->conToken($token)->patchJson(route('api.v1.pedidos.estado', 'P-0001'), ['estado' => 'entregado'])->assertNotFound();
        $this->conToken($token)->postJson(route('api.v1.pedidos.crear'), ['cliente' => ['nombre' => 'X'], 'items' => [['producto_id' => $prodAjeno->id, 'cantidad' => 1]]])->assertStatus(422);
        $this->assertSame('pendiente', $ajeno->fresh()->estado);

        // Un token de inicio de sesión normal (sin permiso de pedidos)... sí tiene "*", pero uno con otro permiso no entra
        $limitado = $u->createToken('otro', ['productos'])->plainTextToken;
        $this->conToken($limitado)->getJson(route('api.v1.pedidos'))->assertForbidden();
    }

    public function test_aviso_firmado_al_cambiar_estado(): void
    {
        Http::fake(['hooks.ejemplo.pe/*' => Http::response('ok', 200)]);
        $u = $this->admin();
        $this->actingAs($u)->postJson(route('admin.api.webhook'), ['url' => 'http://inseguro.pe/x'])->assertStatus(422);
        $secreto = $this->actingAs($u)->postJson(route('admin.api.webhook'), ['url' => 'https://hooks.ejemplo.pe/gh'])->assertOk()->json('secreto');
        $this->assertSame(40, strlen($secreto));

        $this->actingAs($u)->postJson(route('admin.api.webhook.probar'))->assertOk()->assertJsonPath('ok', true);

        $p = Pedido::withoutGlobalScopes()->create(['tenant_id' => $u->tenant_id, 'numero' => 'P-0009', 'canal' => 'manual', 'estado' => 'pendiente', 'tipo' => 'recojo', 'subtotal' => 10, 'total' => 10, 'metodo_pago' => 'efectivo']);
        // Desde el tablero del panel también avisa
        $this->actingAs($u)->patchJson(route('admin.pedidos.mover', $p->id), ['estado' => 'preparando'])->assertOk();

        Http::assertSent(function ($r) use ($secreto) {
            $d = json_decode($r->body(), true);

            return $r->url() === 'https://hooks.ejemplo.pe/gh' && ($d['evento'] ?? null) === 'pedido.estado'
                && $d['estado_anterior'] === 'pendiente' && $d['pedido']['estado'] === 'preparando'
                && $r->header('X-GestionaHub-Firma')[0] === 'sha256=' . hash_hmac('sha256', $r->body(), $secreto);
        });

        // Sin URL no se envía nada
        AjustesNegocio::guardar($u->tenant_id, ['api_webhook_url' => '']);
        Http::fake();
        $p->fresh()->update(['estado' => 'listo']);
        Http::assertNothingSent();
    }
}
