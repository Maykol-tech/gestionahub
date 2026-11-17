<?php

namespace Tests\Feature;

use App\Models\CrmCliente;
use App\Models\FidelizacionMovimiento;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VentaPos;
use App\Support\AjustesNegocio;
use App\Support\BoletinInactivos;
use App\Support\Nubefact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-75 a 79: programa de puntos, puntos y canje en POS, boletín a inactivos y ranking. */
class FidelizacionProgramaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    private CrmCliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('local');
        AjustesNegocio::limpiarCache();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->tenant = Tenant::create(['nombre_negocio' => 'Pollería Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo', 'ruc' => '20123456789']);
        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Ana', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
        Producto::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id, 'nombre' => 'Pollo entero', 'slug' => 'pollo', 'precio' => 100, 'stock' => 20, 'stock_minimo' => 0, 'activo' => true,
        ]);
        $this->cliente = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'nombre' => 'Luis Pérez', 'telefono' => '987654321', 'activo' => true]);
        \App\Models\AperturaCaja::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'fecha' => now()->toDateString(), 'monto_inicial' => 100, 'abierto_por' => 'Ana']);
        Http::fake();
        AjustesNegocio::guardar($this->tenant->id, ['fid_puntos_por_sol' => 1, 'fid_valor_punto' => 0.1, 'fid_tope_pct' => 30, 'fid_activo' => true]);
    }

    private function producto(): Producto
    {
        return Producto::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->first();
    }

    public function test_descuento_global_baja_base_e_igv(): void
    {
        $c = Nubefact::calcular([['descripcion' => 'X', 'cantidad' => 1, 'precio_unitario' => 118]], 18);
        $this->assertSame(100.0, $c['total']);
        $this->assertSame(84.75, $c['gravada']);
        $this->assertSame(15.25, $c['igv']);
        $this->assertSame(15.25, $c['descuento_global']);
    }

    public function test_venta_pos_con_cliente_suma_puntos_una_vez_y_anular_los_revierte(): void
    {
        $this->actingAs($this->admin);
        $r = $this->postJson(route('admin.pos.cobrar'), [
            'items' => [['producto_id' => $this->producto()->id, 'cantidad' => 1]],
            'metodo_pago' => 'yape', 'tipo_comprobante' => 'ticket', 'cliente_id' => $this->cliente->id,
        ])->assertOk();

        $this->assertSame(100, (int) $this->cliente->fresh()->puntos_saldo);
        $mov = FidelizacionMovimiento::withoutGlobalScopes()->where('cliente_id', $this->cliente->id)->first();
        $this->assertSame(100, (int) $mov->saldo_resultante);

        $id = $r->json('venta.id');
        \App\Support\Fidelizacion::alCobrarVentaPos(VentaPos::find($id)); // no duplica
        $this->assertSame(100, (int) $this->cliente->fresh()->puntos_saldo);

        $this->postJson(url("admin/pos/ventas/{$id}/anular"), ['motivo' => 'Error de cobro'])->assertOk();
        $this->assertSame(0, (int) $this->cliente->fresh()->puntos_saldo);
    }

    public function test_canje_en_pos_respeta_el_tope_y_aplica_descuento(): void
    {
        DB::table('crm_clientes')->where('id', $this->cliente->id)->update(['puntos_saldo' => 1000, 'puntos_historicos' => 1000]);
        $this->actingAs($this->admin);
        $base = ['items' => [['producto_id' => $this->producto()->id, 'cantidad' => 1]], 'metodo_pago' => 'yape', 'tipo_comprobante' => 'boleta', 'cliente_id' => $this->cliente->id];

        // Tope 30% de S/ 100 = S/ 30 = 300 puntos
        $this->postJson(route('admin.pos.cobrar'), $base + ['puntos_canje' => 301])->assertStatus(422)->assertJsonValidationErrors('puntos_canje');

        $r = $this->postJson(route('admin.pos.cobrar'), $base + ['puntos_canje' => 300])->assertOk();
        $this->assertEquals(70, $r->json('venta.total'));
        $this->assertEquals(30, $r->json('venta.descuento'));

        $venta = VentaPos::find($r->json('venta.id'));
        $payload = $venta->comprobante->payload;
        $this->assertEquals(70, $payload['total']);
        $this->assertGreaterThan(0, $payload['descuento_global']);

        // 1000 - 300 canjeados + 70 ganados (Plata x1.2 = 84 si llegó al nivel)
        $saldo = (int) $this->cliente->fresh()->puntos_saldo;
        $this->assertSame(700 + (int) round(70 * 1.2), $saldo);
    }

    public function test_boletin_registra_envios_y_no_repite_en_30_dias(): void
    {
        DB::table('crm_clientes')->where('id', $this->cliente->id)->update(['created_at' => now()->subDays(200)]);
        DB::table('tenants')->where('id', $this->tenant->id)->update(['boletin_activo' => true, 'boletin_texto' => 'Hola {nombre}, vuelve con {cupon}']);
        AjustesNegocio::guardar($this->tenant->id, ['boletin_dias' => 60, 'boletin_cupon' => 'VUELVE10']);

        $this->assertSame(1, BoletinInactivos::destinatarios($this->tenant->id)->count());
        [$registrados] = BoletinInactivos::enviar($this->tenant->id);
        $this->assertSame(1, $registrados);
        $this->assertSame(0, BoletinInactivos::destinatarios($this->tenant->id)->count());
        $this->assertSame("Hola Luis, vuelve con VUELVE10", BoletinInactivos::mensaje('Hola {nombre}, vuelve con {cupon}', 'Luis Pérez', 'VUELVE10'));

        // Vuelve a comprar → queda registrado
        $this->travel(1)->days();
        \App\Models\AperturaCaja::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'fecha' => now()->toDateString(), 'monto_inicial' => 50, 'abierto_por' => 'Ana']);
        $this->actingAs($this->admin)->postJson(route('admin.pos.cobrar'), [
            'items' => [['producto_id' => $this->producto()->id, 'cantidad' => 1]], 'metodo_pago' => 'yape', 'tipo_comprobante' => 'ticket', 'cliente_id' => $this->cliente->id,
        ])->assertOk();
        $this->assertSame(1, BoletinInactivos::resumen($this->tenant->id)['volvieron']);
    }

    public function test_paginas_de_fidelizacion_y_ranking_cargan_con_filtros(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('admin.fidelizacion'))->assertOk()->assertSee('Configurar programa de puntos')->assertSee('Reactivar clientes inactivos');
        $this->get(route('admin.clientes.ranking', ['top' => 10, 'orden' => 'pedidos', 'periodo' => '30']))->assertOk()->assertSee('Top 10');
        $this->get(route('admin.clientes.ranking', ['exportar' => 1]))->assertOk();
        $this->post(route('admin.fidelizacion.niveles'), [
            'fid_activo' => 1, 'fid_puntos_por_sol' => 2, 'fid_valor_punto' => 0.05, 'fid_tope_pct' => 20, 'fid_vence_meses' => 6,
            'niveles' => [
                ['clave' => 'bronce', 'nombre' => 'Bronce', 'desde' => 0, 'multiplicador' => 1, 'beneficio' => ''],
                ['clave' => 'plata', 'nombre' => 'Plata', 'desde' => 500, 'multiplicador' => 1.2, 'beneficio' => 'Delivery gratis'],
                ['clave' => 'oro', 'nombre' => 'Oro', 'desde' => 1500, 'multiplicador' => 1.5, 'beneficio' => 'Postre gratis'],
            ],
        ])->assertRedirect()->assertSessionHas('ok');
        $this->assertSame(2.0, (float) AjustesNegocio::leer($this->tenant->id)['fid_puntos_por_sol']);
    }

    protected function tearDown(): void
    {
        AjustesNegocio::limpiarCache();
        parent::tearDown();
    }
}
