<?php

namespace Tests\Feature;

use App\Models\CrmCliente;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Caja: el lector encuentra al cliente por el QR o el código de su tarjeta. */
class PosLectorTarjetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_busca_cliente_por_qr_o_codigo_de_tarjeta(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Bodega', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'A', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Luis', 'activo' => true]);
        $token = $c->tokenTarjeta();

        $this->actingAs($u)->getJson(route('admin.pos.clientes', ['q' => route('tarjeta.cliente', $token)]))
            ->assertOk()->assertJsonPath('clientes.0.nombre', 'Luis')->assertJsonPath('tarjeta', true);
        $codigo = strtoupper(substr($token, 0, 4) . '-' . substr($token, 4, 4));
        $this->actingAs($u)->getJson(route('admin.pos.clientes', ['q' => $codigo]))->assertJsonPath('clientes.0.nombre', 'Luis');

        // Tarjeta de otro negocio no aparece.
        $t2 = Tenant::create(['nombre_negocio' => 'Otro', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $ajeno = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $t2->id, 'nombre' => 'Ajeno', 'activo' => true]);
        $this->actingAs($u)->getJson(route('admin.pos.clientes', ['q' => '/tarjeta/' . $ajeno->tokenTarjeta()]))->assertJsonCount(0, 'clientes');

        $this->actingAs($u)->get(route('admin.pos'))->assertOk()->assertSee('escanear código de barras');
    }
}
