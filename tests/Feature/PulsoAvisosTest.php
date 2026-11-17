<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Sonido de avisos: el pulso devuelve el último pedido del propio negocio y lo puede pedir el cajero. */
class PulsoAvisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_pulso_solo_ve_su_negocio_y_lo_usa_el_cajero(): void
    {
        $a = Tenant::create(['nombre_negocio' => 'A', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $b = Tenant::create(['nombre_negocio' => 'B', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'cajero', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $a->id, 'name' => 'Caja', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('cajero');
        $base = ['canal' => 'tienda', 'estado' => 'pendiente', 'tipo' => 'delivery', 'subtotal' => 10, 'total' => 10];
        Pedido::withoutGlobalScopes()->create($base + ['tenant_id' => $a->id, 'numero' => 'A-1']);
        Pedido::withoutGlobalScopes()->create($base + ['tenant_id' => $b->id, 'numero' => 'B-9']);

        $this->actingAs($u)->getJson(route('admin.pulso'))->assertOk()
            ->assertJsonPath('pedido.numero', 'A-1')->assertJsonPath('pendientes', 1);
        $this->actingAs($u)->get(route('admin.modulo', 'caja'))->assertOk()->assertSee('gh-sonido', false);
    }
}
