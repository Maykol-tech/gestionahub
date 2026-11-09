<?php

namespace App\Http\Controllers;

use App\Models\FormaEntrega;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\TiendaContenido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Tienda publica del negocio: catalogo, carrito y registro del pedido sin login (GEST-57, GEST-62, GEST-77). */
class TiendaPublicaController extends Controller
{
    public function index(string $slug): View
    {
        $negocio = DB::table('tenants')->where('slug', $slug)->where('estado', 'activo')->first();
        abort_if(! $negocio, 404);

        $productos = Producto::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'precio', 'precio_oferta', 'stock']);

        $entregas = FormaEntrega::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('activa', true)->orderBy('orden')->orderBy('id')->get(['id', 'nombre', 'tipo', 'costo']);

        return view('publico.tienda', [
            'negocio' => $negocio,
            'contenido' => TiendaContenido::todo((int) $negocio->id),
            'color' => $negocio->color_primario ?? '#2B6EF6',
            'abierta' => \App\Support\Horarios::abiertoAhora(
                $negocio->horarios ? (is_array($negocio->horarios) ? $negocio->horarios : json_decode((string) $negocio->horarios, true)) : null
            ),
            'productos' => $productos->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'precio' => $this->precioVigente($p),
                'stock' => (int) $p->stock,
            ])->values(),
            'entregas' => $entregas->map(fn ($e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'tipo' => $e->tipo,
                'costo' => (float) $e->costo,
            ])->values(),
        ]);
    }

    public function pedido(Request $request, string $slug): RedirectResponse
    {
        $negocio = DB::table('tenants')->where('slug', $slug)->where('estado', 'activo')->first();
        abort_if(! $negocio, 404);

        // El carrito llega como JSON desde el formulario
        $request->merge(['carrito' => json_decode((string) $request->input('carrito'), true) ?: []]);

        $datos = $request->validate([
            'carrito' => ['required', 'array', 'min:1', 'max:50'],
            'carrito.*.producto_id' => ['required', 'integer'],
            'carrito.*.cantidad' => ['required', 'integer', 'between:1,50'],
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'max:20'],
            'entrega_id' => ['required', 'integer'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'metodo_pago' => ['required', 'in:efectivo,yape,plin,tarjeta,transferencia'],
            'notas' => ['nullable', 'string', 'max:300'],
        ]);

        $entrega = FormaEntrega::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('activa', true)->find($datos['entrega_id']);
        if (! $entrega) {
            throw ValidationException::withMessages(['entrega_id' => 'Elige una forma de entrega válida.']);
        }
        if ($entrega->tipo === 'delivery' && blank($request->input('direccion'))) {
            throw ValidationException::withMessages(['direccion' => 'Indica la dirección de entrega.']);
        }

        $numero = 'P-' . now()->format('ymdHis') . random_int(10, 99);

        $pedido = DB::transaction(function () use ($datos, $negocio, $entrega, $numero, $request) {
            $subtotal = 0.0;
            $lineas = [];

            foreach ($datos['carrito'] as $linea) {
                $producto = Producto::withoutGlobalScopes()->where('tenant_id', $negocio->id)
                    ->where('activo', true)->lockForUpdate()->find($linea['producto_id']);

                if (! $producto) {
                    throw ValidationException::withMessages(['carrito' => 'Un producto ya no está disponible.']);
                }
                if ((int) $producto->stock < (int) $linea['cantidad']) {
                    throw ValidationException::withMessages(['carrito' => "No hay stock suficiente de {$producto->nombre}."]);
                }

                $precio = $this->precioVigente($producto);
                $cantidad = (int) $linea['cantidad'];
                $lineaSubtotal = round($precio * $cantidad, 2);
                $subtotal += $lineaSubtotal;

                $producto->decrement('stock', $cantidad);

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $lineaSubtotal,
                ];
            }

            $costoEnvio = $entrega->tipo === 'delivery' ? (float) $entrega->costo : 0.0;
            $total = round($subtotal + $costoEnvio, 2);
            // Los precios incluyen IGV (18%)
            $igv = round($total - $total / 1.18, 2);

            $pedido = Pedido::withoutGlobalScopes()->create([
                'tenant_id' => $negocio->id,
                'numero' => $numero,
                'canal' => 'tienda',
                'tipo' => $entrega->tipo === 'delivery' ? 'delivery' : 'recojo',
                'estado' => 'pendiente',
                'subtotal' => $subtotal,
                'descuento' => 0,
                'costo_envio' => $costoEnvio,
                'igv' => $igv,
                'total' => $total,
                'direccion_entrega' => $request->input('direccion'),
                'notas' => trim('Cliente: ' . $datos['nombre'] . ' · Tel: ' . $datos['telefono'] . ' · Entrega: ' . $entrega->nombre
                    . ($datos['notas'] ? ' · ' . $datos['notas'] : '')),
                'metodo_pago' => $datos['metodo_pago'],
                'pagado' => false,
            ]);

            foreach ($lineas as $linea) {
                PedidoItem::create($linea + ['pedido_id' => $pedido->id]);
            }

            return $pedido;
        });

        return redirect()->route('tienda.gracias', ['slug' => $slug, 'numero' => $pedido->numero]);
    }

    public function gracias(string $slug, string $numero): View
    {
        $negocio = DB::table('tenants')->where('slug', $slug)->first();
        abort_if(! $negocio, 404);

        $pedido = Pedido::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('numero', $numero)->with('items')->firstOrFail();

        return view('publico.tienda_gracias', [
            'negocio' => $negocio,
            'pedido' => $pedido,
            'color' => $negocio->color_primario ?? '#2B6EF6',
        ]);
    }

    /** Precio de venta: el de oferta si existe y es menor que el normal. */
    private function precioVigente(Producto $producto): float
    {
        $precio = (float) $producto->precio;
        $oferta = (float) ($producto->precio_oferta ?? 0);

        return ($oferta > 0 && $oferta < $precio) ? $oferta : $precio;
    }
}
