<?php

namespace Tests\Feature;

use App\Models\CrmCliente;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Clientes: editar, etiquetas, cumpleaños y tarjeta digital de puntos con QR. */
class ClientesEtiquetasTarjetaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $t = Tenant::create(['nombre_negocio' => 'Bodega Rosa', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');

        return $u;
    }

    public function test_crea_y_edita_con_etiquetas_y_cumpleanos(): void
    {
        $u = $this->admin();
        $r = $this->actingAs($u)->postJson(route('admin.clientes.guardar'), [
            'nombre' => 'María Torres', 'telefono' => '987654321',
            'etiquetas' => ['VIP', ' vip ', 'Mayorista', ''], 'cumpleanos' => now()->subYears(30)->format('Y-m-d'),
        ])->assertCreated();
        $this->assertSame(['VIP', 'Mayorista'], $r->json('data.etiquetas'));
        $this->assertTrue($r->json('data.cumple_mes'));
        $this->assertSame(0, $r->json('data.cumple_dias'));

        $id = $r->json('data.id');
        $this->actingAs($u)->putJson(route('admin.clientes.actualizar', $id), [
            'nombre' => 'María Torres Díaz', 'telefono' => '987654321', 'etiquetas' => ['Mayorista'], 'cumpleanos' => '1990-02-14',
        ])->assertOk()->assertJsonPath('data.etiquetas', ['Mayorista'])->assertJsonPath('data.cumple_texto', '14 de febrero');
        $this->assertSame('María Torres Díaz', CrmCliente::withoutGlobalScopes()->find($id)->nombre);

        $this->actingAs($u)->get(route('admin.clientes'))->assertOk()->assertSee('Cumpleaños del mes')->assertSee('Mayorista');
    }

    public function test_valida_etiquetas_y_cumpleanos(): void
    {
        $u = $this->admin();
        $this->actingAs($u)->postJson(route('admin.clientes.guardar'), [
            'nombre' => 'Ana', 'etiquetas' => ['1', '2', '3', '4', '5', '6', '7'], 'cumpleanos' => now()->addDay()->format('Y-m-d'),
        ])->assertStatus(422)->assertJsonValidationErrors(['etiquetas', 'cumpleanos']);
    }

    public function test_no_edita_cliente_de_otro_negocio(): void
    {
        $u = $this->admin();
        $otro = $this->admin();
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $otro->tenant_id, 'nombre' => 'Ajeno', 'activo' => true]);
        $this->actingAs($u)->putJson(route('admin.clientes.actualizar', $c->id), ['nombre' => 'X'])->assertNotFound();
        $this->actingAs($u)->get(route('admin.clientes.tarjeta', $c->id))->assertNotFound();
    }

    public function test_tarjeta_digital_muestra_puntos_y_qr(): void
    {
        $u = $this->admin();
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $u->tenant_id, 'nombre' => 'Luis Pérez', 'activo' => true]);
        $c->forceFill(['puntos_saldo' => 320, 'puntos_historicos' => 320])->save();

        $r = $this->actingAs($u)->get(route('admin.clientes.tarjeta', $c->id));
        $token = $c->fresh()->tarjeta_token;
        $this->assertNotEmpty($token);
        $r->assertRedirect(route('tarjeta.cliente', $token));

        auth()->logout();
        $this->get(route('tarjeta.cliente', $token))->assertOk()
            ->assertSee('Luis')->assertSee('320')->assertSee('Bodega Rosa')->assertSee('<svg', false)
            ->assertSee('para ser Plata')->assertHeader('X-Robots-Tag', 'noindex');

        $this->get('/tarjeta/' . str_repeat('x', 32))->assertNotFound();
    }
}
