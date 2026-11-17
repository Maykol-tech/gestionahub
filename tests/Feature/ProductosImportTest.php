<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Importar productos desde Excel: vista previa, errores, crear y actualizar. */
class ProductosImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $t = Tenant::create(['nombre_negocio' => 'Bodega', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');

        return $u;
    }

    private function excel(array $filas): UploadedFile
    {
        $libro = new Spreadsheet();
        $libro->getActiveSheet()->fromArray($filas);
        $ruta = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($libro))->save($ruta);

        return new UploadedFile($ruta, 'productos.xlsx', null, null, true);
    }

    public function test_previa_marca_errores_e_importa_creando_y_actualizando(): void
    {
        $u = $this->admin();
        $existente = Producto::withoutGlobalScopes()->create(['tenant_id' => $u->tenant_id, 'nombre' => 'Gaseosa 1.5 L', 'precio' => 5, 'stock' => 1, 'sku' => 'GAS-15', 'activo' => true]);

        $archivo = $this->excel([
            ['Producto', 'Precio venta', 'Cantidad', 'Categoría', 'Código', 'Activo'],
            ['Arroz 5 kg', 'S/ 24,90', 30, 'Abarrotes', 'ARR-5', 'si'],
            ['Gaseosa grande', 7.5, 48, 'Bebidas', 'GAS-15', 'si'],
            ['', 3, 1, '', '', ''],
            ['Sin precio', 'abc', 1, '', '', 'no'],
        ]);
        $r = $this->actingAs($u)->post(route('admin.productos.importar.previa'), ['archivo' => $archivo], ['Accept' => 'application/json'])->assertOk()->json();
        $this->assertSame(['total' => 4, 'buenas' => 2, 'con_errores' => 2], $r['resumen']);
        $this->assertSame(24.9, $r['filas'][0]['precio']);

        $this->actingAs($u)->postJson(route('admin.productos.importar'), ['filas' => $r['filas']])
            ->assertOk()->assertJsonPath('creados', 1)->assertJsonPath('actualizados', 1)->assertJsonPath('omitidos', 2);

        $this->assertSame('Gaseosa grande', $existente->fresh()->nombre);
        $this->assertSame(48, $existente->fresh()->stock);
        $arroz = Producto::withoutGlobalScopes()->where('sku', 'ARR-5')->firstOrFail();
        $this->assertSame($u->tenant_id, $arroz->tenant_id);
        $this->assertSame('Abarrotes', Categoria::withoutGlobalScopes()->find($arroz->categoria_id)->nombre);
    }

    public function test_archivo_sin_columnas_y_plantilla(): void
    {
        $u = $this->admin();
        $this->actingAs($u)->post(route('admin.productos.importar.previa'), ['archivo' => $this->excel([['Cosa', 'Otra'], ['a', 'b']])], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonFragment(['message' => 'Falta la columna "Nombre" o "Precio" en la primera fila. Descarga la plantilla para ver el formato.']);
        $this->actingAs($u)->get(route('admin.productos.importar.plantilla'))->assertOk()->assertDownload('plantilla_productos.xlsx');
        $this->actingAs($u)->get(route('admin.productos'))->assertOk()->assertSee('Importar Excel');
    }
}
