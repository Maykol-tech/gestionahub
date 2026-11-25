<?php

namespace Tests\Feature;

use App\Models\CrmCliente;
use App\Models\Insumo;
use App\Models\OrdenProduccion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\RecetaInsumo;
use App\Models\Recompensa;
use App\Models\Servicio;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Reglas lógicas del negocio: stock, estados de pedidos, pagos, producción, citas y puntos. */
class ReglasNegocioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $t = Tenant::create(['nombre_negocio' => 'Pollería Reglas', 'slug' => 'polleria-reglas', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    private function producto(int $stock, array $extra = []): Producto
    {
        return Producto::withoutGlobalScopes()->create($extra + [
            'tenant_id' => $this->admin->tenant_id, 'nombre' => 'Pollo ' . uniqid(), 'slug' => uniqid(), 'precio' => 20,
            'stock' => $stock, 'stock_minimo' => 0, 'activo' => true,
        ]);
    }

    private function pedido(Producto $p, int $cantidad = 1)
    {
        return $this->postJson(route('admin.pedidos.guardar'), [
            'cliente_nuevo' => true, 'cliente_nombre' => 'Ana', 'cliente_telefono' => '987654321',
            'tipo' => 'recojo', 'metodo_pago' => 'yape',
            'items' => [['producto_id' => $p->id, 'cantidad' => $cantidad]],
        ]);
    }

    public function test_producto_sin_stock_no_se_puede_vender(): void
    {
        $p = $this->producto(0);
        $this->pedido($p)->assertStatus(422);
        $this->assertSame(0, Pedido::withoutGlobalScopes()->count());
    }

    public function test_no_se_vende_mas_de_lo_que_hay(): void
    {
        $p = $this->producto(2);
        $this->pedido($p, 3)->assertStatus(422);
        $this->assertSame(2, (int) $p->fresh()->stock);
    }

    public function test_vender_descuenta_y_cancelar_devuelve_stock(): void
    {
        $p = $this->producto(5);
        $this->pedido($p, 2)->assertSuccessful();
        $this->assertSame(3, (int) $p->fresh()->stock);

        $pedido = Pedido::withoutGlobalScopes()->latest('id')->first();
        $this->patchJson(route('admin.pedidos.cancelar', $pedido->id))->assertOk();
        $this->assertSame(5, (int) $p->fresh()->stock);
    }

    public function test_pedido_entregado_no_vuelve_atras_ni_se_cancela(): void
    {
        $this->pedido($this->producto(5))->assertSuccessful();
        $pedido = Pedido::withoutGlobalScopes()->latest('id')->first();
        $this->patchJson(route('admin.pedidos.mover', $pedido->id), ['estado' => 'entregado'])->assertOk();

        $this->patchJson(route('admin.pedidos.mover', $pedido->id), ['estado' => 'preparando'])->assertStatus(422);
        $this->patchJson(route('admin.pedidos.cancelar', $pedido->id))->assertStatus(422);
        $this->assertSame('entregado', $pedido->fresh()->estado);
    }

    public function test_pedido_cancelado_no_se_marca_como_pagado(): void
    {
        $this->pedido($this->producto(5))->assertSuccessful();
        $pedido = Pedido::withoutGlobalScopes()->latest('id')->first();
        $this->patchJson(route('admin.pedidos.cancelar', $pedido->id))->assertOk();

        $this->patchJson(route('admin.pedidos.pagado', $pedido->id))->assertStatus(422);
        $this->assertFalse((bool) $pedido->fresh()->pagado);
    }

    public function test_no_se_borra_un_producto_con_pedidos_sin_entregar(): void
    {
        $p = $this->producto(5);
        $this->pedido($p)->assertSuccessful();
        $this->deleteJson(route('admin.productos.eliminar', $p->id))->assertStatus(422);
        $this->assertNotNull($p->fresh());

        // Ya entregado, sí se puede borrar
        $pedido = Pedido::withoutGlobalScopes()->latest('id')->first();
        $this->patchJson(route('admin.pedidos.mover', $pedido->id), ['estado' => 'entregado'])->assertOk();
        $this->deleteJson(route('admin.productos.eliminar', $p->id))->assertOk();
    }

    public function test_lote_terminado_descuenta_insumos_y_suma_stock_al_producto(): void
    {
        $tid = $this->admin->tenant_id;
        $p = $this->producto(0);
        $harina = Insumo::withoutGlobalScopes()->create(['tenant_id' => $tid, 'nombre' => 'Harina', 'unidad' => 'kg', 'stock' => 10, 'stock_minimo' => 0, 'costo_unitario' => 3, 'activo' => true]);
        $receta = Receta::withoutGlobalScopes()->create(['tenant_id' => $tid, 'nombre' => 'Pan', 'rendimiento' => 10, 'activo' => true, 'producto_id' => $p->id]);
        RecetaInsumo::withoutGlobalScopes()->create(['tenant_id' => $tid, 'receta_id' => $receta->id, 'insumo_id' => $harina->id, 'cantidad' => 2, 'merma_pct' => 0]);
        $lote = OrdenProduccion::withoutGlobalScopes()->create(['tenant_id' => $tid, 'titulo' => 'Pan del día', 'receta_id' => $receta->id, 'cantidad' => 20, 'fecha' => today(), 'estado' => 'pendiente']);

        $this->patchJson(route('admin.produccion.lotes.estado', $lote->id), ['estado' => 'terminada'])->assertOk();

        $this->assertEqualsWithDelta(6.0, (float) $harina->fresh()->stock, 0.001);
        $this->assertSame(20, (int) $p->fresh()->stock);
        // Un lote terminado ya no cambia ni se borra
        $this->patchJson(route('admin.produccion.lotes.estado', $lote->id), ['estado' => 'pendiente'])->assertStatus(422);
    }

    public function test_lote_sin_insumos_suficientes_no_se_termina(): void
    {
        $tid = $this->admin->tenant_id;
        $harina = Insumo::withoutGlobalScopes()->create(['tenant_id' => $tid, 'nombre' => 'Harina', 'unidad' => 'kg', 'stock' => 1, 'stock_minimo' => 0, 'costo_unitario' => 3, 'activo' => true]);
        $receta = Receta::withoutGlobalScopes()->create(['tenant_id' => $tid, 'nombre' => 'Pan', 'rendimiento' => 10, 'activo' => true]);
        RecetaInsumo::withoutGlobalScopes()->create(['tenant_id' => $tid, 'receta_id' => $receta->id, 'insumo_id' => $harina->id, 'cantidad' => 2, 'merma_pct' => 0]);
        $lote = OrdenProduccion::withoutGlobalScopes()->create(['tenant_id' => $tid, 'titulo' => 'Pan', 'receta_id' => $receta->id, 'cantidad' => 10, 'fecha' => today(), 'estado' => 'pendiente']);

        $this->patchJson(route('admin.produccion.lotes.estado', $lote->id), ['estado' => 'terminada'])->assertStatus(422);
        $this->assertEqualsWithDelta(1.0, (float) $harina->fresh()->stock, 0.001);
    }

    public function test_no_se_agenda_una_cita_en_el_pasado(): void
    {
        $s = Servicio::withoutGlobalScopes()->create(['tenant_id' => $this->admin->tenant_id, 'nombre' => 'Corte', 'duracion_min' => 30, 'precio' => 20, 'activo' => true]);
        $datos = ['servicio_id' => $s->id, 'cliente_nombre' => 'Luis', 'duracion_min' => 30];

        $this->postJson(route('admin.servicios.citas.guardar'), $datos + ['inicio' => now()->subDay()->format('Y-m-d H:i')])->assertStatus(422);
        $this->postJson(route('admin.servicios.citas.guardar'), $datos + ['inicio' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i')])->assertSuccessful();
    }

    public function test_no_se_canjea_sin_puntos_suficientes(): void
    {
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $this->admin->tenant_id, 'nombre' => 'Rosa', 'telefono' => '911111111', 'activo' => true]);
        $c->forceFill(['puntos_saldo' => 10])->save();
        $r = Recompensa::withoutGlobalScopes()->create(['tenant_id' => $this->admin->tenant_id, 'nombre' => 'Gaseosa gratis', 'puntos_requeridos' => 100, 'activo' => true]);

        $this->postJson(route('admin.fidelizacion.canjear'), ['cliente_id' => $c->id, 'recompensa_id' => $r->id])->assertStatus(422);
        $this->assertSame(10, (int) $c->fresh()->puntos_saldo);
    }
}
