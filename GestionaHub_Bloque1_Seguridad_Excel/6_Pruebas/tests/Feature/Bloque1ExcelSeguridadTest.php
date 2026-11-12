<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PantallaBloqueoController;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Bloque 1: exportacion a Excel con estilo, limites de validacion y ajustes de seguridad. */
class Bloque1ExcelSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'vendedor', 'cajero', 'repartidor'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $tenant = Tenant::create(['nombre_negocio' => 'Bodega Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $this->admin = User::create(['tenant_id' => $tenant->id, 'name' => 'Ana', 'email' => 'ana@test.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
    }

    private function hoja(\Illuminate\Testing\TestResponse $r): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xl') . '.xlsx';
        file_put_contents($ruta, $r->streamedContent() ?: file_get_contents($r->baseResponse->getFile()->getPathname()));

        return IOFactory::load($ruta)->getActiveSheet();
    }

    public function test_excel_con_cabecera_azul_moneda_totales_y_sin_formulas_inyectadas(): void
    {
        $r = $this->actingAs($this->admin)->postJson(route('admin.exportar.tabla'), [
            'titulo' => 'Productos',
            'columnas' => [['titulo' => 'Producto'], ['titulo' => 'Precio', 'tipo' => 'moneda'], ['titulo' => 'Stock', 'tipo' => 'numero']],
            'filas' => [['Gaseosa', 5.9, 10], ['=HYPERLINK("http://malo")', '12.50', 3]],
        ]);
        $r->assertOk();
        $this->assertStringContainsString('spreadsheetml', $r->headers->get('Content-Type'));
        $this->assertStringContainsString('productos-bodega-prueba-', $r->headers->get('Content-Disposition'));

        $h = $this->hoja($r);
        $this->assertSame('Producto', $h->getCell('A1')->getValue());
        $this->assertSame('2B6EF6', $h->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertSame('FFFFFF', $h->getStyle('A1')->getFont()->getColor()->getRGB());
        $this->assertSame('"S/ "#,##0.00', $h->getStyle('B2')->getNumberFormat()->getFormatCode());
        $this->assertSame('thin', $h->getStyle('A2')->getBorders()->getLeft()->getBorderStyle());
        $this->assertSame('A2', $h->getFreezePane());
        // La celda maliciosa queda como texto, no como formula
        $this->assertSame('s', $h->getCell('A3')->getDataType());
        $this->assertSame(12.5, $h->getCell('B3')->getValue());
        // Fila de totales
        $this->assertSame('TOTAL', $h->getCell('A4')->getValue());
        $this->assertEqualsWithDelta(18.4, $h->getCell('B4')->getCalculatedValue(), 0.001);
    }

    public function test_validacion_estricta_de_la_exportacion(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.exportar.tabla'), [
            'titulo' => 'X', 'columnas' => array_fill(0, 31, ['titulo' => 'c']), 'filas' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('columnas');

        $this->postJson(route('admin.exportar.tabla'), [
            'titulo' => 'X', 'columnas' => [['titulo' => 'c', 'tipo' => 'php']], 'filas' => [],
        ])->assertStatus(422);

        $this->postJson(route('admin.exportar.tabla'), [
            'titulo' => 'X', 'columnas' => [['titulo' => 'c']], 'filas' => [[['anidado']]],
        ])->assertStatus(422);
    }

    public function test_exportaciones_del_servidor(): void
    {
        $this->actingAs($this->admin)->get(route('admin.exportar.comprobantes'))->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->get(route('admin.clientes.exportar'))->assertOk();
    }

    public function test_sin_sesion_no_exporta(): void
    {
        $this->postJson(route('admin.exportar.tabla'), [])->assertUnauthorized();
    }

    public function test_inactividad_por_defecto_es_10_minutos(): void
    {
        $this->assertSame(10, PantallaBloqueoController::minutos());
        $this->actingAs($this->admin)->withSession([PantallaBloqueoController::ACTIVIDAD => now()->subMinutes(9)->timestamp])
            ->getJson(route('admin.pos.ventas'))->assertOk();
        $this->withSession([PantallaBloqueoController::ACTIVIDAD => now()->subMinutes(11)->timestamp])
            ->getJson(route('admin.pos.ventas'))->assertStatus(423);
    }

    public function test_login_valida_con_form_request(): void
    {
        $this->from('/login')->post('/login', ['email' => 'no-es-correo', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_errores_json_amigables_y_pagina_419(): void
    {
        config(['app.debug' => false]);
        $this->actingAs($this->admin)->getJson('/admin/pos/ventas/999999/ticket')->assertNotFound()
            ->assertJsonMissing(['exception']);
        $this->get('/ruta-que-no-existe')->assertNotFound()->assertSee('Página no encontrada');
    }
}
