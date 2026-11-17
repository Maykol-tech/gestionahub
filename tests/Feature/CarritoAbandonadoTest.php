<?php

namespace Tests\Feature;

use App\Models\CheckoutAbandonado;
use App\Models\CrmCliente;
use App\Models\FormaEntrega;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CarritosAbandonados;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-85: carrito abandonado con enlace de recuperación, un recordatorio y reporte. */
class CarritoAbandonadoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-11 12:00'));
        $this->tenant = Tenant::create(['nombre_negocio' => 'Tienda Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'trial']);
        $this->tenant->slug = 'tienda-prueba';
        // Abierto todos los días a toda hora, para que la prueba no dependa de la hora en que corre
        $this->tenant->horarios = json_encode(collect(range(0, 6))->map(fn ($d) => ['dia' => $d, 'activo' => true, 'abre' => '00:00', 'cierra' => '23:59'])->all());
        $this->tenant->save();
        $this->producto = Producto::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'nombre' => 'Polo', 'slug' => 'polo', 'precio' => 40, 'stock' => 10, 'stock_minimo' => 0, 'activo' => true]);
    }

    public function test_captura_recupera_y_marca_recuperado_al_comprar(): void
    {
        $r = $this->postJson(route('publico.carrito', 'tienda-prueba'), [
            'nombre' => 'Luis', 'telefono' => '987654321', 'total' => 80,
            'carrito' => [['producto_id' => $this->producto->id, 'nombre' => 'Polo', 'cantidad' => 2, 'precio' => 40]],
        ])->assertOk();
        $token = $r->json('token');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);

        // Un solo recordatorio, aunque el comando corra dos veces
        $this->travel(31)->minutes();
        $this->assertSame(1, CarritosAbandonados::recordar($this->tenant->id));
        $this->assertSame(0, CarritosAbandonados::recordar($this->tenant->id));
        $this->assertNotNull(CheckoutAbandonado::withoutGlobalScopes()->first()->recordado_at);

        $this->getJson(url("/tienda/tienda-prueba/carrito/{$token}"))->assertOk()
            ->assertJsonPath('carrito.0.producto_id', $this->producto->id)->assertJsonPath('carrito.0.cantidad', 2);

        $entrega = FormaEntrega::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'nombre' => 'Recojo', 'tipo' => 'recojo', 'costo' => 0, 'activa' => true]);
        $this->post(route('tienda.pedido', 'tienda-prueba'), [
            'carrito' => json_encode([['producto_id' => $this->producto->id, 'cantidad' => 2]]),
            'nombre' => 'Luis', 'telefono' => '987654321', 'entrega_id' => $entrega->id, 'metodo_pago' => 'yape',
        ])->assertRedirect();

        $c = CheckoutAbandonado::withoutGlobalScopes()->first();
        $this->assertTrue($c->recuperado);
        $this->assertNotNull($c->pedido_id);
        $this->assertSame(1, CarritosAbandonados::resumen($this->tenant->id)['recuperados']);
        // GEST-76: el pedido de la tienda queda a nombre del cliente para sumar puntos
        $this->assertNotNull(CrmCliente::withoutGlobalScopes()->where('telefono', '987654321')->first());
    }

    public function test_pagina_del_panel_carga(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Ana', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $admin->assignRole('admin');
        CheckoutAbandonado::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'nombre' => 'Rosa', 'telefono' => '911222333', 'carrito' => [['nombre' => 'Polo', 'cantidad' => 1]], 'total' => 40]);

        $this->actingAs($admin)->get(route('admin.carritos'))->assertOk()->assertSee('Rosa')->assertSee('Recordar');
        $this->actingAs($admin)->post(route('admin.carritos.guardar'), ['carrito_activo' => 1, 'carrito_minutos' => 45, 'carrito_mensaje' => 'Hola {nombre} {enlace}'])->assertSessionHas('ok');
    }
}
