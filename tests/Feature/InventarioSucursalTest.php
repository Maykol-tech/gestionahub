<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\StockSucursal;
use App\Models\Sucursal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Inventario por sucursal: traslados y ventas de caja que descuentan del local correcto. */
class InventarioSucursalTest extends TestCase
{
    use RefreshDatabase;

    private User $u;
    private Producto $p;
    private Sucursal $s;

    protected function setUp(): void
    {
        parent::setUp();
        $t = Tenant::create(['nombre_negocio' => 'Bodega', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->u = User::create(['tenant_id' => $t->id, 'name' => 'Ana', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $this->u->assignRole('admin');
        $this->p = Producto::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Arroz 1 kg', 'precio' => 5, 'stock' => 20, 'activo' => true]);
        $this->s = Sucursal::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Los Olivos', 'activo' => true]);
    }

    public function test_traslada_sin_cambiar_total_y_valida_lo_que_hay(): void
    {
        $this->actingAs($this->u)->get(route('admin.sucursales.inventario'))->assertOk()->assertSee('Los Olivos')->assertSee('Arroz 1 kg');

        $this->actingAs($this->u)->postJson(route('admin.sucursales.inventario.mover'), ['producto_id' => $this->p->id, 'desde' => 0, 'hacia' => $this->s->id, 'cantidad' => 8])
            ->assertOk()->assertJsonPath('locales.0', 12)->assertJsonPath('locales.' . $this->s->id, 8);
        $this->assertSame(20, (int) $this->p->fresh()->stock);

        $this->actingAs($this->u)->postJson(route('admin.sucursales.inventario.mover'), ['producto_id' => $this->p->id, 'desde' => $this->s->id, 'hacia' => 0, 'cantidad' => 9])
            ->assertStatus(422)->assertJsonValidationErrors('cantidad');
        $this->actingAs($this->u)->postJson(route('admin.sucursales.inventario.mover'), ['producto_id' => $this->p->id, 'desde' => 0, 'hacia' => 0, 'cantidad' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('hacia');
    }

    public function test_venta_en_sucursal_descuenta_de_esa_sucursal(): void
    {
        StockSucursal::withoutGlobalScopes()->create(['tenant_id' => $this->u->tenant_id, 'producto_id' => $this->p->id, 'sucursal_id' => $this->s->id, 'stock' => 3]);
        $this->actingAs($this->u);
        \App\Models\AperturaCaja::create(['tenant_id' => $this->u->tenant_id, 'fecha' => today()->toDateString(), 'monto_inicial' => 0]);

        $venta = ['metodo_pago' => 'efectivo', 'tipo_comprobante' => 'ticket', 'sucursal_id' => $this->s->id];
        $this->postJson(route('admin.pos.cobrar'), $venta + ['items' => [['producto_id' => $this->p->id, 'cantidad' => 4]]])
            ->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson(route('admin.pos.cobrar'), $venta + ['items' => [['producto_id' => $this->p->id, 'cantidad' => 2]]])->assertOk();

        $this->assertSame(18, (int) $this->p->fresh()->stock);
        $this->assertSame(1, (int) StockSucursal::withoutGlobalScopes()->first()->stock);
    }
}
