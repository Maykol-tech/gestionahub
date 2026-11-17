<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Repartidor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-24: app del repartidor con enlace privado. */
class RepartidorAppTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $t = Tenant::create(['nombre_negocio' => 'Pollería Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $admin->assignRole('admin');
        $r = Repartidor::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Carlos Ruiz', 'telefono' => '987654321', 'activo' => true, 'disponible' => true]);
        $p = Pedido::withoutGlobalScopes()->create([
            'tenant_id' => $t->id, 'numero' => 'P-0001', 'canal' => 'tienda', 'estado' => 'listo', 'tipo' => 'delivery',
            'subtotal' => 30, 'total' => 35, 'direccion_entrega' => 'Av. Lima 123', 'metodo_pago' => 'efectivo', 'pagado' => false,
            'cliente_nombre' => 'Lucía', 'cliente_telefono' => '912345678', 'repartidor_id' => $r->id, 'asignado_at' => now(),
        ]);

        return [$admin, $r, $p];
    }

    public function test_admin_crea_enlace_y_el_repartidor_entrega_cobrando(): void
    {
        [$admin, $r, $p] = $this->escenario();

        $res = $this->actingAs($admin)->postJson(route('admin.repartidores.crud.enlace', $r->id))->assertOk()->json();
        $this->assertStringContainsString('/r/', $res['url']);
        $this->assertStringContainsString('wa.me/51987654321', $res['whatsapp']);
        $token = $r->fresh()->token;
        auth()->logout();

        $this->get(route('repartidor.app', $token))->assertOk()->assertSee('Hola, Carlos')->assertSee('P-0001');
        $this->getJson(route('repartidor.pedidos', $token))->assertOk()->assertJsonPath('data.0.numero', 'P-0001');

        $this->patchJson(url("r/{$token}/pedidos/{$p->id}/estado"), ['estado' => 'en_camino'])->assertOk();
        $this->assertSame('en_camino', $p->fresh()->estado);
        $this->assertFalse($r->fresh()->disponible);

        $this->postJson(route('repartidor.ubicacion', $token), ['lat' => -12.1, 'lng' => -77.03, 'precision' => 12])->assertOk();
        $this->assertEqualsWithDelta(-12.1, $r->fresh()->lat_actual, 0.0001);

        $this->patchJson(url("r/{$token}/pedidos/{$p->id}/estado"), ['estado' => 'entregado', 'cobrado' => true])->assertOk();
        $p->refresh();
        $this->assertSame('entregado', $p->estado);
        $this->assertTrue((bool) $p->pagado);
        $this->assertNotNull($p->entregado_at);
        $this->assertTrue($r->fresh()->disponible);
    }

    public function test_enlace_nuevo_invalida_el_anterior_y_no_toca_pedidos_ajenos(): void
    {
        [$admin, $r, $p] = $this->escenario();
        $this->actingAs($admin)->postJson(route('admin.repartidores.crud.enlace', $r->id));
        $viejo = $r->fresh()->token;
        $this->actingAs($admin)->postJson(route('admin.repartidores.crud.enlace', $r->id), ['nuevo' => 1]);
        $nuevo = $r->fresh()->token;
        $this->assertNotSame($viejo, $nuevo);
        auth()->logout();

        $this->get(route('repartidor.app', $viejo))->assertNotFound();

        $otro = Repartidor::withoutGlobalScopes()->create(['tenant_id' => $r->tenant_id, 'nombre' => 'Otro', 'activo' => true, 'disponible' => true, 'token' => str_repeat('x', 40)]);
        $this->patchJson(url('r/' . $otro->token . "/pedidos/{$p->id}/estado"), ['estado' => 'en_camino'])->assertNotFound();
        $this->patchJson(url("r/{$nuevo}/pedidos/{$p->id}/estado"), ['estado' => 'entregado'])->assertStatus(200);
    }
}
