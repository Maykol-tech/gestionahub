<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cambiar precios de varios productos a la vez: todos, una categoría o los elegidos.
 * Subir o bajar un porcentaje, sumar o restar soles, o poner un precio fijo. Incluye las variantes.
 */
class ProductosMasivoController extends Controller
{
    public function precios(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'alcance' => ['required', 'in:todos,categoria,elegidos'],
            'categoria_id' => ['required_if:alcance,categoria', 'nullable', 'integer'],
            'ids' => ['required_if:alcance,elegidos', 'array', 'max:2000'],
            'ids.*' => ['integer'],
            'operacion' => ['required', 'in:subir_pct,bajar_pct,sumar,restar,fijar'],
            'valor' => ['required', 'numeric', 'min:0', 'max:999999'],
            'redondear' => ['nullable', 'in:no,entero,decima,90'],
            'simular' => ['nullable', 'boolean'],
        ], [
            'ids.required_if' => 'Marca al menos un producto.',
            'categoria_id.required_if' => 'Elige la categoría.',
        ], ['valor' => 'el valor']);

        if ($datos['operacion'] === 'bajar_pct' && $datos['valor'] > 90) {
            return response()->json(['message' => 'No puedes bajar más del 90%.'], 422);
        }

        $q = Producto::query()
            ->when($datos['alcance'] === 'categoria', fn ($b) => $b->where('categoria_id', $datos['categoria_id']))
            ->when($datos['alcance'] === 'elegidos', fn ($b) => $b->whereIn('id', $datos['ids']));
        $productos = $q->get(['id', 'nombre', 'precio', 'precio_oferta', 'tiene_variantes']);

        $nuevo = function ($precio) use ($datos) {
            $p = (float) $precio;
            $v = (float) $datos['valor'];
            $r = match ($datos['operacion']) {
                'subir_pct' => $p * (1 + $v / 100),
                'bajar_pct' => $p * (1 - $v / 100),
                'sumar' => $p + $v,
                'restar' => $p - $v,
                'fijar' => $v,
            };
            $r = match ($datos['redondear'] ?? 'no') {
                'entero' => round($r),
                'decima' => round($r, 1),
                '90' => floor($r) + 0.9,
                default => round($r, 2),
            };

            return max(0, round($r, 2));
        };

        $ejemplos = [];
        $cambios = 0;
        foreach ($productos as $p) {
            $antes = (float) $p->precio;
            $despues = $nuevo($antes);
            if (count($ejemplos) < 6) {
                $ejemplos[] = ['nombre' => $p->nombre, 'antes' => $antes, 'despues' => $despues];
            }
        }

        if ($request->boolean('simular')) {
            return response()->json(['total' => $productos->count(), 'ejemplos' => $ejemplos]);
        }

        DB::transaction(function () use ($productos, $nuevo, &$cambios) {
            foreach ($productos as $p) {
                $precio = $nuevo($p->precio);
                $campos = ['precio' => $precio];
                // Si la oferta queda igual o mayor que el nuevo precio, se quita para no confundir
                if ($p->precio_oferta !== null && (float) $p->precio_oferta >= $precio) {
                    $campos['precio_oferta'] = null;
                }
                Producto::query()->whereKey($p->id)->update($campos);
                if ($p->tiene_variantes) {
                    foreach (ProductoVariante::query()->where('producto_id', $p->id)->whereNotNull('precio')->get(['id', 'precio']) as $v) {
                        ProductoVariante::query()->whereKey($v->id)->update(['precio' => $nuevo($v->precio)]);
                    }
                }
                $cambios++;
            }
        });

        return response()->json(['message' => "Listo: cambiamos el precio de {$cambios} " . ($cambios === 1 ? 'producto' : 'productos') . '.', 'total' => $cambios]);
    }
}
