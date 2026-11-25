<?php

namespace Tests\Feature;

use App\Models\PagoManual;
use App\Models\Pedido;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** El cajero ve en Cobros los pagos con captura por revisar y los confirma; el menú no muestra secciones vacías. */
class CajeroCapturasTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, Tenant $t): User
    {
        Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => ucfirst($rol), 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole($rol);

        return $u;
    }

    public function test_cajero_ve_el_aviso_y_confirma_el_pago_del_cliente(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Bodega Sol', 'slug' => 'bodega-sol', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $pedido = Pedido::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'numero' => 'T-261125-ABCD', 'canal' => 'tienda', 'estado' => 'pendiente', 'tipo' => 'recojo',
            'subtotal' => 30, 'total' => 30, 'metodo_pago' => 'yape', 'pagado' => false, 'cliente_nombre' => 'Lucía']);
        $pago = PagoManual::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'pedido_id' => $pedido->id, 'metodo' => 'yape', 'monto' => 30, 'estado' => 'pendiente', 'origen' => 'tienda']);
        $cajero = $this->usuario('cajero', $t);

        $this->actingAs($cajero)->get(route('admin.modulo', 'cobros'))->assertOk()
            ->assertSee('1 pago por revisar')->assertSee(route('admin.pagos'), false);
        $this->actingAs($cajero)->get(route('admin.pagos'))->assertOk()->assertSee('T-261125-ABCD');
        $this->actingAs($cajero)->post(route('admin.pagos.confirmar', $pago->id))->assertSessionHas('ok');

        $this->assertTrue((bool) $pedido->fresh()->pagado);
        $this->actingAs($cajero)->get(route('admin.modulo', 'cobros'))->assertDontSee('pago por revisar');
    }

    public function test_vendedor_no_revisa_pagos_ni_ve_secciones_vacias(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Bodega Luna', 'slug' => 'bodega-luna', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        config(['app.modulos_extra' => false]);
        $vendedor = $this->usuario('vendedor', $t);

        $this->actingAs($vendedor)->get(route('admin.pagos'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('admin.pedidos'))->assertOk()
            ->assertDontSee('<div class="nav-sec-lbl">Control</div>', false)
            ->assertSee('<div class="nav-sec-lbl">Principal</div>', false);
    }
}
