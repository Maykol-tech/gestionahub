<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\VentaPos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Punto de venta: venta rapida de mostrador con busqueda de producto, cobro y descuento de stock. */
class PosController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $productos = Producto::query()->where('activo', true)->where('stock', '>', 0)
            ->orderBy('nombre')->limit(400)->get(['id', 'nombre', 'precio', 'precio_oferta', 'stock'])
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'precio' => (float) ($p->precio_oferta ?? $p->precio),
                'stock' => (int) $p->stock,
            ])->values();

        $hoy = VentaPos::query()->whereDate('created_at', today());

        return view('admin.modulos.pos', [
            'titulo' => 'Punto de venta',
            'modulo' => 'pos',
            'productos' => $productos,
            'metodos' => VentaPos::METODOS,
            'stats' => [
                'ventas_hoy' => (clone $hoy)->count(),
                'total_hoy' => round((float) (clone $hoy)->sum('total'), 2),
            ],
        ]);
    }

    public function cobrar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'integer', 'between:1,9999'],
            'metodo_pago' => ['required', Rule::in(array_keys(VentaPos::METODOS))],
            'recibido' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'cliente_nombre' => ['nullable', 'string', 'max:120'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'items.required' => 'Agrega al menos un producto a la venta.',
        ]);

        $venta = DB::transaction(function () use ($request, $datos) {
            $lineas = [];
            $total = 0.0;

            foreach ($datos['items'] as $item) {
                $producto = Producto::query()->lockForUpdate()->findOrFail($item['producto_id']);
                $cantidad = (int) $item['cantidad'];

                if (! $producto->activo || (int) $producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente de {$producto->nombre} (disponible: " . (int) $producto->stock . ').',
                    ]);
                }

                $precio = (float) ($producto->precio_oferta ?? $producto->precio);
                $subtotal = round($precio * $cantidad, 2);
                $total += $subtotal;

                $producto->decrement('stock', $cantidad);

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                ];
            }

            $total = round($total, 2);
            $efectivo = $datos['metodo_pago'] === 'efectivo';
            $recibido = $efectivo ? round((float) ($datos['recibido'] ?? $total), 2) : null;

            if ($efectivo && $recibido < $total) {
                throw ValidationException::withMessages([
                    'recibido' => 'El efectivo recibido no alcanza para cubrir el total.',
                ]);
            }

            $venta = VentaPos::query()->create([
                'tenant_id' => $request->user()->tenant_id,
                'metodo_pago' => $datos['metodo_pago'],
                'total' => $total,
                'recibido' => $recibido,
                'vuelto' => $efectivo ? round($recibido - $total, 2) : null,
                'cliente_nombre' => $datos['cliente_nombre'] ?? null,
                'items' => $lineas,
            ]);

            $venta->update(['numero' => 'POS-' . str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT)]);

            return $venta;
        });

        $mensaje = $venta->metodo_pago === 'efectivo'
            ? 'Venta ' . $venta->numero . ' cobrada. Vuelto S/ ' . number_format((float) $venta->vuelto, 2) . '.'
            : 'Venta ' . $venta->numero . ' cobrada.';

        return response()->json(['success' => true, 'message' => $mensaje, 'numero' => $venta->numero]);
    }
}
