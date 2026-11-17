<?php

namespace Tests\Feature;

use App\Models\IngresoManual;
use App\Models\Pedido;
use App\Models\Repartidor;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VentaPos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-40 (ventas por periodo y modulo) y GEST-41 (delivery: tiempos y repartidores). */
class ReportesGest40Gest41Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 15:00:00');
        foreach (['admin', 'vendedor', 'cajero', 'repartidor'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->tenant = Tenant::create(['nombre_negocio' => 'Bodega Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Ana', 'email' => 'ana@test.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pedido(array $datos, ?int $tenantId = null): Pedido
    {
        $p = new Pedido;
        $p->forceFill(array_merge([
            'tenant_id' => $tenantId ?? $this->tenant->id,
            'numero' => 'P-' . (++$this->n),
            'canal' => 'manual',
            'estado' => 'confirmado',
            'total' => 10,
        ], $datos));
        $p->save();

        return $p;
    }

    private function ventaPos(float $total, string $fecha, string $estado = 'pagada'): void
    {
        $v = new VentaPos;
        $v->forceFill(['tenant_id' => $this->tenant->id, 'numero' => 'V-' . (++$this->n), 'metodo_pago' => 'efectivo', 'total' => $total, 'items' => [], 'estado' => $estado, 'created_at' => $fecha, 'updated_at' => $fecha]);
        $v->save();
    }

    private function libro(\Illuminate\Testing\TestResponse $r): Spreadsheet
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xl') . '.xlsx';
        file_put_contents($ruta, $r->streamedContent() ?: file_get_contents($r->baseResponse->getFile()->getPathname()));

        return IOFactory::load($ruta);
    }

    private function sembrarVentas(): void
    {
        $hoy = '2026-11-10 10:00:00';
        $ayer = '2026-11-09 12:00:00';
        $this->pedido(['canal' => 'tienda', 'total' => 50, 'created_at' => $hoy]);
        $this->pedido(['canal' => 'web', 'total' => 25, 'created_at' => $hoy]);
        $this->pedido(['canal' => 'whatsapp', 'total' => 40, 'created_at' => $ayer]);
        $this->pedido(['canal' => 'manual', 'total' => 15, 'created_at' => $ayer]);
        $this->pedido(['canal' => 'tienda', 'total' => 999, 'estado' => 'cancelado', 'created_at' => $hoy]);   // no suma
        $this->pedido(['canal' => 'tienda', 'total' => 500, 'created_at' => '2026-10-01 10:00:00']);            // fuera de 7 y 30 dias
        $this->pedido(['canal' => 'tienda', 'total' => 777, 'created_at' => $hoy], Tenant::create(['nombre_negocio' => 'Otro', 'email' => uniqid() . '@o.pe', 'estado' => 'activo'])->id);
        $this->ventaPos(30, $hoy);
        $this->ventaPos(20, $hoy);
        $this->ventaPos(80, $hoy, 'anulada');   // no suma
        IngresoManual::query()->create(['tenant_id' => $this->tenant->id, 'fecha' => '2026-11-09', 'concepto' => 'Evento', 'monto' => 100]);
    }

    public function test_la_tienda_publica_puede_guardar_su_canal(): void
    {
        $p = $this->pedido(['canal' => 'tienda']);
        $this->assertSame('tienda', $p->fresh()->canal);
    }

    public function test_pantalla_desglosa_ventas_por_modulo_y_filtra_por_rango(): void
    {
        $this->sembrarVentas();

        $r = $this->actingAs($this->admin)->get('/admin/reportes?rango=7');
        $r->assertOk()->assertSee('Ventas POS')->assertSee('Tienda Pública')->assertSee('Ventas Manuales')->assertSee('Repartidores');
        $canales = collect($r->viewData('porCanal'))->keyBy('clave');
        $this->assertSame(50.0, $canales['pos']['monto']);
        $this->assertSame(2, $canales['pos']['cantidad']);
        $this->assertSame(75.0, $canales['tienda']['monto']);     // canal "tienda" + "web"
        $this->assertSame(40.0, $canales['whatsapp']['monto']);
        $this->assertSame(115.0, $canales['manual']['monto']);    // pedido manual + ingreso manual
        $this->assertSame(280.0, $r->viewData('ventas'));
        $this->assertSame(7, $r->viewData('cantidad'));

        // 90 dias ya incluye el pedido de octubre; un rango invalido vuelve a 7
        $this->assertSame(780.0, $this->get('/admin/reportes?rango=90')->viewData('ventas'));
        $this->assertSame(7, $this->get('/admin/reportes?rango=15')->viewData('rango'));
    }

    public function test_excel_de_ventas_tiene_las_columnas_exactas_y_el_estilo(): void
    {
        $this->sembrarVentas();

        $r = $this->actingAs($this->admin)->get('/admin/reportes/exportar?rango=7&hoja=ventas');
        $r->assertOk();
        $this->assertStringContainsString('ventas-por-canal-7-dias-bodega-prueba-', $r->headers->get('Content-Disposition'));
        $h = $this->libro($r)->getActiveSheet();

        $this->assertSame(['Fecha', 'Módulo/Canal', 'N° Pedidos', 'Total Ventas (S/)'], [$h->getCell('A1')->getValue(), $h->getCell('B1')->getValue(), $h->getCell('C1')->getValue(), $h->getCell('D1')->getValue()]);
        $this->assertSame('2B6EF6', $h->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertSame('FFFFFF', $h->getStyle('D1')->getFont()->getColor()->getRGB());
        $this->assertTrue($h->getStyle('A1')->getFont()->getBold());
        $this->assertSame('"S/ "#,##0.00', $h->getStyle('D2')->getNumberFormat()->getFormatCode());
        $this->assertSame('thin', $h->getStyle('B3')->getBorders()->getBottom()->getBorderStyle());

        $filas = [];
        for ($f = 2; $h->getCell("A{$f}")->getValue() !== 'TOTAL'; $f++) {
            $filas[] = [$h->getCell("A{$f}")->getValue(), $h->getCell("B{$f}")->getValue(), $h->getCell("C{$f}")->getValue(), $h->getCell("D{$f}")->getValue()];
        }
        $this->assertEquals([
            ['09/11/2026', 'WhatsApp', 1, 40],
            ['09/11/2026', 'Ventas Manuales', 2, 115],
            ['10/11/2026', 'Ventas POS', 2, 50],
            ['10/11/2026', 'Tienda Pública', 2, 75],
        ], $filas);
        $this->assertSame('=SUM(C2:C5)', $h->getCell('C6')->getValue());
        $this->assertSame('=SUM(D2:D5)', $h->getCell('D6')->getValue());
        $this->assertEquals(280, $h->getCell('D6')->getCalculatedValue());
    }

    public function test_rendimiento_de_repartidores_en_pantalla_y_excel(): void
    {
        $luis = Repartidor::query()->create(['tenant_id' => $this->tenant->id, 'nombre' => 'Luis', 'activo' => true]);
        $rosa = Repartidor::query()->create(['tenant_id' => $this->tenant->id, 'nombre' => 'Rosa', 'activo' => true]);
        Repartidor::query()->create(['tenant_id' => $this->tenant->id, 'nombre' => 'Zoe Nueva', 'activo' => true]);

        // Luis: 20 y 40 min (promedio 30). Rosa: 15 min, sin hora de asignacion (usa la de confirmacion)
        $this->pedido(['repartidor_id' => $luis->id, 'estado' => 'entregado', 'asignado_at' => '2026-11-10 10:00:00', 'entregado_at' => '2026-11-10 10:20:00']);
        $this->pedido(['repartidor_id' => $luis->id, 'estado' => 'entregado', 'asignado_at' => '2026-11-09 18:00:00', 'entregado_at' => '2026-11-09 18:40:00']);
        $this->pedido(['repartidor_id' => $rosa->id, 'estado' => 'entregado', 'confirmado_at' => '2026-11-10 11:00:00', 'entregado_at' => '2026-11-10 11:15:00']);
        $this->pedido(['repartidor_id' => $rosa->id, 'estado' => 'en_camino', 'asignado_at' => '2026-11-10 12:00:00']);   // aun no entregado
        $this->pedido(['repartidor_id' => $rosa->id, 'estado' => 'entregado', 'asignado_at' => '2026-09-01 10:00:00', 'entregado_at' => '2026-09-01 11:00:00']);   // fuera de rango

        $r = $this->actingAs($this->admin)->get('/admin/reportes?rango=7&vista=repartidores');
        $r->assertOk()->assertSee('Rendimiento por repartidor');
        $d = $r->viewData('delivery');
        $this->assertSame('repartidores', $r->viewData('vista'));
        $this->assertSame(3, $d['entregas']);
        $this->assertSame(['Luis', 'Rosa', 'Zoe Nueva'], array_column($d['lista'], 'nombre'));
        $this->assertSame([2, 1, 0], array_column($d['lista'], 'entregados'));
        $this->assertSame([30.0, 15.0, null], array_column($d['lista'], 'promedio'));
        $this->assertSame('Rosa', $d['masRapido']['nombre']);

        $h = $this->libro($this->get('/admin/reportes/exportar?rango=7&hoja=repartidores')->assertOk())->getActiveSheet();
        $this->assertSame(['Repartidor', 'Pedidos Entregados', 'Tiempo Promedio de Entrega (min)'], [$h->getCell('A1')->getValue(), $h->getCell('B1')->getValue(), $h->getCell('C1')->getValue()]);
        $this->assertSame('2B6EF6', $h->getStyle('C1')->getFill()->getStartColor()->getRGB());
        $this->assertEquals(['Luis', 2, 30], [$h->getCell('A2')->getValue(), $h->getCell('B2')->getValue(), $h->getCell('C2')->getValue()]);
        $this->assertEquals(['Rosa', 1, 15], [$h->getCell('A3')->getValue(), $h->getCell('B3')->getValue(), $h->getCell('C3')->getValue()]);
        $this->assertSame('Zoe Nueva', $h->getCell('A4')->getValue());
        $this->assertSame('=SUM(B2:B4)', $h->getCell('B5')->getValue());
    }

    public function test_libro_completo_trae_ambas_hojas(): void
    {
        $this->sembrarVentas();

        $libro = $this->libro($this->actingAs($this->admin)->get('/admin/reportes/exportar?rango=30')->assertOk());
        $this->assertSame(['Ventas por canal', 'Repartidores'], $libro->getSheetNames());
        $this->assertSame('Total Ventas (S/)', $libro->getSheet(0)->getCell('D1')->getValue());
        $this->assertSame('Tiempo Promedio de Entrega (min)', $libro->getSheet(1)->getCell('C1')->getValue());
    }

    public function test_validacion_estricta_y_permisos(): void
    {
        $this->actingAs($this->admin)->getJson('/admin/reportes/exportar?rango=15')->assertStatus(422)->assertJsonValidationErrors('rango');
        $this->getJson('/admin/reportes/exportar?rango=7&hoja=clientes')->assertStatus(422)->assertJsonValidationErrors('hoja');

        $vendedor = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Vero', 'email' => 'vero@test.pe', 'password' => 'Secreto123!']);
        $vendedor->assignRole('vendedor');
        $this->actingAs($vendedor)->get('/admin/reportes/exportar?rango=7')->assertForbidden();

        auth()->logout();
        $this->get('/admin/reportes/exportar?rango=7')->assertRedirect();
    }

    public function test_sin_columna_asignado_at_ni_tabla_repartidores_no_hay_error(): void
    {
        $luis = Repartidor::query()->create(['tenant_id' => $this->tenant->id, 'nombre' => 'Luis', 'activo' => true]);
        $this->pedido(['repartidor_id' => $luis->id, 'estado' => 'entregado', 'confirmado_at' => '2026-11-10 10:00:00', 'entregado_at' => '2026-11-10 10:25:00']);

        // Proyecto sin la migracion de tracking: no existe pedidos.asignado_at
        \Illuminate\Support\Facades\Schema::table('pedidos', fn ($t) => $t->dropColumn('asignado_at'));
        $d = $this->actingAs($this->admin)->get('/admin/reportes?vista=repartidores')->assertOk()->viewData('delivery');
        $this->assertSame(25.0, $d['lista'][0]['promedio']);

        // Proyecto sin el modulo de Repartos: la pestaña queda vacia y el Excel sale solo con cabeceras
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\Schema::drop('repartidores');
        $d = $this->get('/admin/reportes?vista=repartidores')->assertOk()->assertSee('Falta activar el módulo de Repartos')->viewData('delivery');
        $this->assertFalse($d['disponible']);
        $this->get('/admin/reportes/exportar?rango=7')->assertOk();
    }

    private function cajero(): User
    {
        $u = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Caja', 'email' => 'caja@test.pe', 'password' => 'Secreto123!']);
        $u->assignRole('cajero');

        return $u;
    }

    public function test_admin_agrega_un_repartidor_y_aparece_en_el_reporte(): void
    {
        $r = $this->actingAs($this->admin)->postJson('/admin/reportes/repartidores', ['nombre' => '  Pedro Gómez ', 'telefono' => '987 654 321', 'vehiculo' => 'bicicleta']);
        $r->assertStatus(201)->assertJsonPath('data.nombre', 'Pedro Gómez');

        $this->assertDatabaseHas('repartidores', ['tenant_id' => $this->tenant->id, 'nombre' => 'Pedro Gómez', 'vehiculo' => 'bicicleta', 'activo' => true]);
        $this->actingAs($this->admin)->get('/admin/reportes?vista=repartidores')->assertOk()->assertSee('Pedro Gómez');
    }

    public function test_alta_de_repartidor_valida_los_campos(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/reportes/repartidores', ['nombre' => '', 'vehiculo' => 'moto'])
            ->assertStatus(422)->assertJsonValidationErrors('nombre');
        $this->actingAs($this->admin)->postJson('/admin/reportes/repartidores', ['nombre' => 'Ana', 'vehiculo' => 'avion'])
            ->assertStatus(422)->assertJsonValidationErrors('vehiculo');
        $this->actingAs($this->admin)->postJson('/admin/reportes/repartidores', ['nombre' => 'Ana', 'telefono' => '<script>', 'vehiculo' => 'moto'])
            ->assertStatus(422)->assertJsonValidationErrors('telefono');
        $this->assertDatabaseCount('repartidores', 0);
    }

    public function test_cajero_no_puede_agregar_repartidores_ni_generar_datos_de_prueba(): void
    {
        $caja = $this->cajero();
        $this->actingAs($caja)->postJson('/admin/reportes/repartidores', ['nombre' => 'Ana', 'vehiculo' => 'moto'])->assertForbidden();
        $this->actingAs($caja)->postJson('/admin/reportes/datos-prueba')->assertForbidden();
        $this->assertDatabaseCount('repartidores', 0);

        // Pero si ve la pestaña, sin botones de gestion
        $this->actingAs($caja)->get('/admin/reportes?vista=repartidores')->assertOk()->assertDontSee('Agregar repartidor');
    }

    public function test_datos_de_prueba_crean_repartidores_con_entregas_y_no_se_duplican(): void
    {
        $r = $this->actingAs($this->admin)->postJson('/admin/reportes/datos-prueba')->assertOk();
        $this->assertSame(5, $r->json('data.repartidores'));
        $this->assertGreaterThan(50, $r->json('data.entregas'));
        $this->assertDatabaseCount('repartidores', 5);
        $this->assertSame(5, Pedido::query()->where('numero', 'like', 'DEMO-%')->whereNotNull('asignado_at')->distinct('repartidor_id')->count('repartidor_id'));

        // Segunda vez: no crea nada nuevo
        $this->actingAs($this->admin)->postJson('/admin/reportes/datos-prueba')->assertOk()->assertJsonPath('data.repartidores', 0);
        $this->assertDatabaseCount('repartidores', 5);

        // El reporte ya muestra datos
        $d = $this->actingAs($this->admin)->get('/admin/reportes?rango=30&vista=repartidores')->viewData('delivery');
        $this->assertGreaterThan(0, $d['entregas']);
        $this->assertCount(5, array_filter($d['lista'], fn ($x) => $x['entregados'] > 0));
    }

    public function test_excel_de_repartidores_sin_datos_no_falla(): void
    {
        $this->actingAs($this->admin)->get('/admin/reportes/exportar?rango=7&hoja=repartidores')->assertOk();
        $h = $this->libro($this->actingAs($this->admin)->get('/admin/reportes/exportar?rango=7&hoja=repartidores'))->getActiveSheet();
        $this->assertSame('Repartidor', $h->getCell('A1')->getValue());
    }
}
