<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PantallaBloqueoController as Bloqueo;
use App\Models\ComprobanteElectronico;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VentaPos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Modulo A (POS y Facturacion) + Seguridad (bloqueo de login y pantalla de bloqueo). */
class ModuloAPosSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'vendedor', 'cajero', 'repartidor'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $tenant = Tenant::create(['nombre_negocio' => 'Bodega Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo', 'ruc' => '20123456789']);
        $this->admin = User::create(['tenant_id' => $tenant->id, 'name' => 'Ana Admin', 'email' => 'ana@test.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
        $this->producto = Producto::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'nombre' => 'Gaseosa 1L', 'slug' => 'gaseosa-1l', 'precio' => 5.90, 'stock' => 20, 'stock_minimo' => 2, 'activo' => true,
        ]);
        Http::fake(); // nada debe salir a Nubefact en modo simulado
    }

    // ---------------- Seguridad: login ----------------

    public function test_login_se_bloquea_15_minutos_tras_5_intentos(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->from('/login')->post('/login', ['email' => 'ana@test.pe', 'password' => 'mala'])->assertSessionHas('intentos_restantes', 5 - $i);
        }

        $r = $this->from('/login')->post('/login', ['email' => 'ana@test.pe', 'password' => 'mala']);
        $r->assertSessionHas('bloqueo_segundos');
        $this->assertGreaterThan(890, session('bloqueo_segundos'));
        $this->assertStringContainsString('14 min', session('errors')->first('email'));

        // Ni con la contraseña correcta entra durante el bloqueo
        $this->from('/login')->post('/login', ['email' => 'ana@test.pe', 'password' => 'Secreto123!'])->assertSessionHas('bloqueo_segundos');
        $this->assertGuest();

        // Pasados 15 minutos vuelve a funcionar
        $this->travel(16)->minutes();
        $this->post('/login', ['email' => 'ana@test.pe', 'password' => 'Secreto123!'])->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_pagina_de_login_muestra_el_contador(): void
    {
        $this->withSession(['bloqueo_segundos' => 600])->get('/login')->assertOk()->assertSee('10:00')->assertSee('Acceso bloqueado temporalmente');
    }

    // ---------------- Seguridad: pantalla de bloqueo ----------------

    public function test_pantalla_bloqueada_no_responde_datos_y_se_desbloquea_con_la_clave(): void
    {
        $this->actingAs($this->admin);

        $this->postJson(route('bloqueo.bloquear'))->assertOk();
        $this->postJson(route('admin.pos.cobrar'), [])->assertStatus(423);
        $this->getJson(route('admin.pos.ventas'))->assertStatus(423);

        // La pagina completa carga, pero con la capa de bloqueo
        $this->get(route('admin.pos'))->assertOk()->assertSee('Pantalla bloqueada por inactividad');

        $this->postJson(route('bloqueo.desbloquear'), ['password' => 'mala'])->assertStatus(422)->assertJsonPath('intentos_restantes', 4);
        $this->postJson(route('bloqueo.desbloquear'), ['password' => 'Secreto123!'])->assertOk()->assertJsonPath('bloqueado', false);
        $this->getJson(route('admin.pos.ventas'))->assertOk();
        $this->assertAuthenticatedAs($this->admin); // nunca cerro sesion
    }

    public function test_cinco_claves_malas_en_la_pantalla_de_bloqueo_cierran_sesion(): void
    {
        $this->actingAs($this->admin);
        $this->postJson(route('bloqueo.bloquear'));
        for ($i = 0; $i < 4; $i++) {
            $this->postJson(route('bloqueo.desbloquear'), ['password' => 'mala'])->assertStatus(422);
        }
        $this->postJson(route('bloqueo.desbloquear'), ['password' => 'mala'])->assertStatus(401)->assertJsonPath('cerrar_sesion', true);
        $this->assertGuest();
    }

    public function test_volver_tras_mucho_tiempo_inactivo_bloquea_la_pantalla(): void
    {
        $this->actingAs($this->admin)->withSession([Bloqueo::ACTIVIDAD => now()->subMinutes(30)->timestamp]);
        $this->getJson(route('admin.pos.ventas'))->assertStatus(423);
    }

    // ---------------- POS y facturacion ----------------

    public function test_cobro_con_boleta_simulada_desglosa_igv_y_descuenta_stock(): void
    {
        $r = $this->actingAs($this->admin)->postJson(route('admin.pos.cobrar'), [
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 2], ['producto_id' => $this->producto->id, 'cantidad' => 1]],
            'metodo_pago' => 'efectivo', 'recibido' => 20, 'tipo_comprobante' => 'boleta',
        ])->assertOk();

        $r->assertJsonPath('venta.total', 17.7)->assertJsonPath('venta.vuelto', 2.3)
            ->assertJsonPath('venta.comprobante.estado', 'aceptado')->assertJsonPath('venta.comprobante.simulado', true);

        $venta = VentaPos::withoutGlobalScopes()->first();
        $this->assertEqualsWithDelta(15.0, $venta->gravada, 0.01);
        $this->assertEqualsWithDelta(2.7, $venta->igv, 0.01);
        $this->assertSame(17, $this->producto->fresh()->stock);

        $c = ComprobanteElectronico::withoutGlobalScopes()->first();
        $this->assertSame('B001-00000001', $c->numero_completo);
        $this->assertSame($venta->id, $c->venta_pos_id);
        Http::assertNothingSent();

        $this->get(route('admin.pos.ticket', $venta->id))->assertOk()->assertSee('B001-00000001')->assertSee('MODO SIMULADO');
        $this->get(route('admin.comprobantes.ver', $c->id))->assertOk()->assertSee('SIMULADO');
    }

    public function test_factura_exige_ruc_valido_y_razon_social(): void
    {
        $base = ['items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]], 'metodo_pago' => 'yape', 'tipo_comprobante' => 'factura'];

        $this->actingAs($this->admin)->postJson(route('admin.pos.cobrar'), $base + ['cliente_documento' => '123'])
            ->assertStatus(422)->assertJsonValidationErrors(['cliente_nombre']);
        $this->postJson(route('admin.pos.cobrar'), $base + ['cliente_documento' => '12345678901', 'cliente_nombre' => 'ACME SAC'])
            ->assertStatus(422)->assertJsonValidationErrors(['cliente_documento']);
        $this->assertSame(20, $this->producto->fresh()->stock);

        $this->postJson(route('admin.pos.cobrar'), $base + ['cliente_documento' => '20601234567', 'cliente_nombre' => 'ACME SAC'])
            ->assertOk()->assertJsonPath('venta.comprobante.numero', 'F001-00000001');
    }

    public function test_metodo_no_permitido_y_efectivo_insuficiente(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.pos.cobrar'), [
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]], 'metodo_pago' => 'bitcoin', 'tipo_comprobante' => 'ticket',
        ])->assertStatus(422)->assertJsonValidationErrors(['metodo_pago']);

        $this->postJson(route('admin.pos.cobrar'), [
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]], 'metodo_pago' => 'efectivo', 'recibido' => 2, 'tipo_comprobante' => 'ticket',
        ])->assertStatus(422)->assertJsonValidationErrors(['recibido']);
        $this->assertSame(20, $this->producto->fresh()->stock);
    }

    public function test_anular_venta_emite_nota_de_credito_y_devuelve_stock(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.pos.cobrar'), [
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 4]], 'metodo_pago' => 'tarjeta', 'tipo_comprobante' => 'boleta',
        ])->assertOk();
        $venta = VentaPos::withoutGlobalScopes()->first();

        $this->postJson(route('admin.pos.anular', $venta->id), ['motivo' => 'Cliente devolvió'])->assertOk()->assertJsonPath('venta.estado', 'anulada');

        $this->assertSame(20, $this->producto->fresh()->stock);
        $nota = ComprobanteElectronico::withoutGlobalScopes()->where('tipo', '07')->first();
        $this->assertNotNull($nota);
        $this->assertTrue($nota->aceptado);
        $this->assertSame($venta->id, $nota->venta_pos_id);
        $this->assertEqualsWithDelta(23.6, (float) $nota->total, 0.01);
        $this->assertSame(0, VentaPos::query()->vigentes()->count());

        $this->postJson(route('admin.pos.anular', $venta->id), ['motivo' => 'otra vez'])->assertStatus(422);
    }

    public function test_paginas_del_modulo_cargan(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('admin.pos'))->assertOk()->assertSee('Venta actual')->assertSee('Gaseosa 1L')->assertSee('animejs/3.2.2');
        $this->get(route('admin.comprobantes'))->assertOk()->assertSee('Conexión de facturación');
    }

    public function test_cajero_puede_usar_el_pos(): void
    {
        $cajero = User::create(['tenant_id' => $this->admin->tenant_id, 'name' => 'Caja', 'email' => 'caja@test.pe', 'password' => 'Secreto123!']);
        $cajero->assignRole('cajero');
        $this->actingAs($cajero)->get(route('admin.pos'))->assertOk();
    }
}
