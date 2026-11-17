<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Cobros y Pagos: lo que el negocio debe a sus proveedores. */
class CuentasPorPagarTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_deuda_y_marca_pagada_con_gasto(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Bodega', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');
        $p = Proveedor::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Distribuidora Sol', 'condicion_pago' => 'credito_15']);
        $o = OrdenCompra::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'proveedor_id' => $p->id, 'fecha' => today(), 'estado' => 'recibida',
            'total' => 350.5, 'recibida_at' => now()->subDays(20), 'vence_at' => today()->subDays(5)]);
        OrdenCompra::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'proveedor_id' => $p->id, 'fecha' => today(), 'estado' => 'recibida', 'total' => 99]);

        $this->actingAs($u)->get(route('admin.modulo', 'cobros'))->assertOk()
            ->assertSee('Lo que debes a proveedores')->assertSee('Distribuidora Sol')->assertSee('Debes a proveedores');

        $this->actingAs($u)->patchJson(route('admin.cobros.proveedor.pagado', $o->id), ['metodo' => 'raro'])->assertStatus(422);
        $this->actingAs($u)->patchJson(route('admin.cobros.proveedor.pagado', $o->id), ['metodo' => 'transferencia', 'registrar_gasto' => true])->assertOk();

        $o->refresh();
        $this->assertNotNull($o->pagada_at);
        $this->assertSame('transferencia', $o->pago_metodo);
        $g = Gasto::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(350.5, $g->monto);
        $this->assertStringContainsString('Distribuidora Sol', $g->concepto);

        // Ya no se puede pagar dos veces.
        $this->actingAs($u)->patchJson(route('admin.cobros.proveedor.pagado', $o->id), ['metodo' => 'efectivo'])->assertNotFound();
    }
}
