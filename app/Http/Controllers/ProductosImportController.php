<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Importar productos desde Excel o CSV.
 * Paso 1: se sube el archivo y se muestra una vista previa con lo que está bien y lo que falta.
 * Paso 2: se confirman las filas buenas. Si el SKU o el nombre ya existe, se actualiza; si no, se crea.
 */
class ProductosImportController extends Controller
{
    public const MAX_FILAS = 2000;

    /** Encabezados aceptados (en minúsculas y sin tildes) => campo. */
    private const COLUMNAS = [
        'nombre' => 'nombre', 'producto' => 'nombre', 'descripcion corta' => 'nombre',
        'precio' => 'precio', 'precio venta' => 'precio', 'precio de venta' => 'precio', 'pvp' => 'precio',
        'precio oferta' => 'precio_oferta', 'oferta' => 'precio_oferta',
        'stock' => 'stock', 'cantidad' => 'stock', 'existencias' => 'stock',
        'stock minimo' => 'stock_minimo', 'minimo' => 'stock_minimo',
        'categoria' => 'categoria', 'rubro' => 'categoria', 'familia' => 'categoria',
        'sku' => 'sku', 'codigo' => 'sku', 'cod' => 'sku', 'codigo de barras' => 'sku',
        'descripcion' => 'descripcion', 'detalle' => 'descripcion',
        'activo' => 'activo', 'publicado' => 'activo', 'visible' => 'activo',
    ];

    public function plantilla(): StreamedResponse
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet()->setTitle('Productos');
        $hoja->fromArray([
            ['Nombre', 'Precio', 'Stock', 'Stock mínimo', 'Categoría', 'SKU', 'Descripción', 'Precio oferta', 'Activo'],
            ['Arroz Costeño 5 kg', 24.90, 30, 5, 'Abarrotes', 'ARR-5KG', 'Arroz extra', '', 'si'],
            ['Gaseosa 1.5 L', 7.50, 48, 12, 'Bebidas', 'GAS-15', '', 6.90, 'si'],
        ]);
        $hoja->getStyle('A1:I1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle('A1:I1')->getFill()->setFillType('solid')->getStartColor()->setRGB('1F2937');
        foreach (range('A', 'I') as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }
        $hoja->freezePane('A2');

        return response()->streamDownload(fn () => (new Xlsx($libro))->save('php://output'), 'plantilla_productos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Paso 1: lee el archivo y devuelve la vista previa. */
    public function previa(Request $request): JsonResponse
    {
        $request->validate(['archivo' => ['required', 'file', 'max:5120', 'mimes:xlsx,xls,csv,txt']], [
            'archivo.required' => 'Elige un archivo de Excel.',
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx o .xls) o CSV.',
            'archivo.max' => 'El archivo pesa más de 5 MB.',
        ]);

        try {
            $filas = IOFactory::load($request->file('archivo')->getRealPath())->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'No pudimos leer el archivo. Guárdalo como .xlsx e inténtalo de nuevo.'], 422);
        }

        $filas = array_values(array_filter($filas, fn ($f) => collect($f)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty()));
        if (count($filas) < 2) {
            return response()->json(['message' => 'El archivo no tiene productos. La primera fila debe tener los títulos (Nombre, Precio…).'], 422);
        }

        $mapa = [];
        foreach (array_shift($filas) as $i => $titulo) {
            $clave = Str::of(Str::ascii((string) $titulo))->lower()->replace(['_', '*', ':'], ' ')->squish()->value();
            if (isset(self::COLUMNAS[$clave]) && ! in_array(self::COLUMNAS[$clave], $mapa, true)) {
                $mapa[$i] = self::COLUMNAS[$clave];
            }
        }
        if (! in_array('nombre', $mapa, true) || ! in_array('precio', $mapa, true)) {
            return response()->json(['message' => 'Falta la columna "Nombre" o "Precio" en la primera fila. Descarga la plantilla para ver el formato.'], 422);
        }
        if (count($filas) > self::MAX_FILAS) {
            return response()->json(['message' => 'Puedes importar hasta ' . self::MAX_FILAS . ' productos por archivo.'], 422);
        }

        $productos = [];
        foreach ($filas as $n => $f) {
            $p = ['fila' => $n + 2];
            foreach ($mapa as $i => $campo) {
                $p[$campo] = is_string($f[$i] ?? null) ? trim($f[$i]) : ($f[$i] ?? null);
            }
            $productos[] = $this->revisar($p);
        }

        return response()->json(['filas' => $productos, 'resumen' => $this->resumen($productos)]);
    }

    /** Paso 2: guarda las filas sin errores. */
    public function importar(Request $request): JsonResponse
    {
        $datos = $request->validate(['filas' => ['required', 'array', 'max:' . self::MAX_FILAS]]);
        $tenantId = (int) $request->user()->tenant_id;
        $creados = $actualizados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($datos, $tenantId, &$creados, &$actualizados, &$omitidos) {
            $categorias = Categoria::query()->where('tenant_id', $tenantId)->get()->keyBy(fn ($c) => Str::lower(Str::ascii($c->nombre)));

            foreach ($datos['filas'] as $bruto) {
                $p = $this->revisar(is_array($bruto) ? $bruto : []);
                if ($p['errores']) {
                    $omitidos++;
                    continue;
                }

                $categoriaId = null;
                if ($p['categoria'] !== '') {
                    $k = Str::lower(Str::ascii($p['categoria']));
                    if (! isset($categorias[$k])) {
                        $categorias[$k] = Categoria::query()->create([
                            'tenant_id' => $tenantId, 'nombre' => Str::limit($p['categoria'], 80, ''),
                            'slug' => Str::slug($p['categoria']) . '-' . Str::lower(Str::random(4)), 'activo' => true,
                        ]);
                    }
                    $categoriaId = $categorias[$k]->id;
                }

                $existente = ($p['sku'] !== '' ? Producto::query()->where('tenant_id', $tenantId)->where('sku', $p['sku'])->first() : null)
                    ?? Producto::query()->where('tenant_id', $tenantId)->whereRaw('lower(nombre) = ?', [Str::lower($p['nombre'])])->first();

                $campos = [
                    'nombre' => $p['nombre'], 'precio' => $p['precio'], 'precio_oferta' => $p['precio_oferta'],
                    'stock_minimo' => $p['stock_minimo'], 'sku' => $p['sku'] ?: null, 'activo' => $p['activo'],
                ] + ($categoriaId ? ['categoria_id' => $categoriaId] : []) + ($p['descripcion'] !== '' ? ['descripcion' => $p['descripcion']] : []);

                if ($existente) {
                    // En productos con variantes el stock y precio salen de las variantes: no se pisan
                    if ($existente->tiene_variantes) {
                        unset($campos['precio']);
                    } else {
                        $campos['stock'] = $p['stock'];
                    }
                    $existente->update($campos);
                    $actualizados++;
                } else {
                    Producto::query()->create($campos + ['tenant_id' => $tenantId, 'stock' => $p['stock'], 'tiene_variantes' => false]);
                    $creados++;
                }
            }
        });

        $partes = array_filter([
            $creados ? "{$creados} " . ($creados === 1 ? 'producto nuevo' : 'productos nuevos') : null,
            $actualizados ? "{$actualizados} " . ($actualizados === 1 ? 'actualizado' : 'actualizados') : null,
            $omitidos ? "{$omitidos} con errores no se importaron" : null,
        ]);

        return response()->json([
            'message' => $partes ? 'Listo: ' . implode(', ', $partes) . '.' : 'No había productos para importar.',
            'creados' => $creados, 'actualizados' => $actualizados, 'omitidos' => $omitidos,
        ]);
    }

    /** Limpia una fila y anota lo que está mal. */
    private function revisar(array $p): array
    {
        $num = function ($v) {
            if ($v === null || $v === '') {
                return null;
            }
            if (is_numeric($v)) {
                return (float) $v;
            }
            $v = str_replace(['S/', 's/', ' '], '', (string) $v);
            $v = preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $v) ? str_replace(['.', ','], ['', '.'], $v) : str_replace(',', '', $v);

            return is_numeric($v) ? (float) $v : false;
        };

        $errores = [];
        $nombre = Str::limit(trim((string) ($p['nombre'] ?? '')), 150, '');
        if ($nombre === '') {
            $errores[] = 'Falta el nombre';
        }
        $precio = $num($p['precio'] ?? null);
        if ($precio === null || $precio === false || $precio < 0) {
            $errores[] = 'Precio no válido';
        }
        $oferta = $num($p['precio_oferta'] ?? null);
        if ($oferta === false || ($oferta !== null && $precio && $oferta >= $precio)) {
            $errores[] = 'La oferta debe ser menor al precio';
        }
        $stock = $num($p['stock'] ?? null);
        if ($stock === false || ($stock !== null && $stock < 0)) {
            $errores[] = 'Stock no válido';
        }
        $minimo = $num($p['stock_minimo'] ?? null);
        $activo = Str::lower(Str::ascii(trim((string) ($p['activo'] ?? ''))));

        return [
            'fila' => (int) ($p['fila'] ?? 0),
            'nombre' => $nombre,
            'precio' => is_float($precio) ? round($precio, 2) : null,
            'precio_oferta' => is_float($oferta) && $oferta > 0 ? round($oferta, 2) : null,
            'stock' => is_float($stock) ? (int) round($stock) : 0,
            'stock_minimo' => is_float($minimo) ? max(0, (int) round($minimo)) : 0,
            'categoria' => Str::limit(trim((string) ($p['categoria'] ?? '')), 80, ''),
            'sku' => Str::limit(trim((string) ($p['sku'] ?? '')), 60, ''),
            'descripcion' => Str::limit(trim((string) ($p['descripcion'] ?? '')), 2000, ''),
            'activo' => ! in_array($activo, ['no', '0', 'false', 'oculto', 'inactivo'], true),
            'errores' => $errores,
        ];
    }

    private function resumen(array $filas): array
    {
        $buenas = array_filter($filas, fn ($f) => ! $f['errores']);

        return ['total' => count($filas), 'buenas' => count($buenas), 'con_errores' => count($filas) - count($buenas)];
    }
}
