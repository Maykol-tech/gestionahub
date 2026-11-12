<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\ComprobanteElectronico;
use App\Models\Producto;
use App\Models\SunatConfiguracion;
use App\Models\Tenant;
use App\Models\VentaPos;
use App\Support\Nubefact;
use App\Support\NubefactNotas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Modulo A - Punto de venta (POS).
 * Venta rapida de mostrador: busqueda de producto, carrito, desglose de IGV (precios con IGV incluido),
 * cobro en Efectivo, Tarjeta, Yape o Plin, y emision opcional de boleta o factura electronica.
 */
class PosController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $productos = Producto::query()->where('activo', true)->where('stock', '>', 0)
            ->orderBy('nombre')->limit(500)
            ->get(['id', 'nombre', 'sku', 'categoria_id', 'precio', 'precio_oferta', 'stock', 'imagen_url'])
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'sku' => $p->sku,
                'categoria_id' => $p->categoria_id,
                'precio' => (float) ($p->precio_oferta ?? $p->precio),
                'stock' => (int) $p->stock,
                'imagen' => $p->imagen_url,
            ])->values();

        $categorias = Categoria::query()->whereIn('id', $productos->pluck('categoria_id')->filter()->unique())
            ->orderBy('nombre')->get(['id', 'nombre']);

        $config = SunatConfiguracion::query()->first();

        return view('admin.modulos.pos', [
            'titulo' => 'Punto de venta',
            'modulo' => 'pos',
            'productos' => $productos,
            'categorias' => $categorias,
            'metodos' => collect(VentaPos::METODOS_POS)->mapWithKeys(fn ($m) => [$m => VentaPos::METODOS[$m]])->all(),
            'comprobantes' => VentaPos::COMPROBANTES,
            'tasaIgv' => Nubefact::TASA_IGV,
            'facturacion' => [
                'activa' => (bool) ($config?->activo ?? true),
                'simulado' => $config ? $config->esSimulado() : true,
            ],
            'stats' => $this->stats(),
            'ventas' => $this->ventasDelDia(),
        ]);
    }

    public function cobrar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.producto_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'integer', 'between:1,9999'],
            'metodo_pago' => ['required', Rule::in(VentaPos::METODOS_POS)],
            'recibido' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'tipo_comprobante' => ['required', Rule::in(array_keys(VentaPos::COMPROBANTES))],
            'cliente_nombre' => ['nullable', 'string', 'max:150', 'required_if:tipo_comprobante,factura'],
            'cliente_documento' => ['nullable', 'string', 'max:11'],
            'cliente_direccion' => ['nullable', 'string', 'max:255'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'items.required' => 'Agrega al menos un producto a la venta.',
            'cliente_nombre.required_if' => 'Para factura escribe la razón social.',
        ]);

        $documento = preg_replace('/\D/', '', (string) ($datos['cliente_documento'] ?? ''));
        $this->validarDocumento($datos['tipo_comprobante'], $documento);

        $venta = DB::transaction(function () use ($request, $datos, $documento) {
            $lineas = [];

            foreach ($this->agrupar($datos['items']) as $productoId => $cantidad) {
                $producto = Producto::query()->lockForUpdate()->find($productoId);

                if (! $producto || ! $producto->activo) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos ya no está disponible. Actualiza la pantalla.']);
                }
                if ((int) $producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente de {$producto->nombre} (disponible: " . (int) $producto->stock . ').',
                    ]);
                }

                $precio = round((float) ($producto->precio_oferta ?? $producto->precio), 2);
                $producto->decrement('stock', $cantidad);

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'codigo' => $producto->sku ?: 'P' . $producto->id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => round($precio * $cantidad, 2),
                ];
            }

            // Mismo calculo que el comprobante electronico: gravada + IGV = total
            $calculo = Nubefact::calcular($this->itemsComprobante($lineas));
            $total = $calculo['total'];

            if ($datos['tipo_comprobante'] === 'boleta' && $total >= 700 && strlen($documento) !== 8) {
                throw ValidationException::withMessages(['cliente_documento' => 'Desde S/ 700 la boleta necesita el DNI del cliente (8 dígitos).']);
            }

            $efectivo = $datos['metodo_pago'] === 'efectivo';
            $recibido = $efectivo ? round((float) ($datos['recibido'] ?? $total), 2) : null;

            if ($efectivo && $recibido + 0.001 < $total) {
                throw ValidationException::withMessages(['recibido' => 'El efectivo recibido no alcanza para cubrir el total.']);
            }

            $venta = VentaPos::query()->create([
                'tenant_id' => $request->user()->tenant_id,
                'user_id' => $request->user()->id,
                'metodo_pago' => $datos['metodo_pago'],
                'gravada' => $calculo['gravada'],
                'igv' => $calculo['igv'],
                'total' => $total,
                'recibido' => $recibido,
                'vuelto' => $efectivo ? round($recibido - $total, 2) : null,
                'cliente_nombre' => $datos['cliente_nombre'] ?? null,
                'cliente_documento' => $documento ?: null,
                'tipo_comprobante' => $datos['tipo_comprobante'],
                'items' => $lineas,
                'estado' => 'pagada',
            ]);

            $venta->update(['numero' => 'POS-' . str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT)]);

            return $venta;
        });

        // El comprobante se emite fuera de la transaccion: si SUNAT falla la venta igual queda cobrada
        $comprobante = null;
        $avisoComprobante = null;
        if ($venta->tipo_comprobante !== 'ticket') {
            try {
                $comprobante = $this->emitirComprobante($venta, $datos['cliente_direccion'] ?? null);
                if (! $comprobante->aceptado) {
                    $avisoComprobante = 'La venta se cobró, pero el comprobante quedó en estado "' . $comprobante->estado . '". Reintenta desde Comprobantes.';
                }
            } catch (ValidationException $e) {
                $avisoComprobante = 'La venta se cobró, pero no se emitió el comprobante: ' . collect($e->errors())->flatten()->first();
            }
        }

        $mensaje = 'Venta ' . $venta->numero . ' cobrada'
            . ($venta->metodo_pago === 'efectivo' ? '. Vuelto S/ ' . number_format((float) $venta->vuelto, 2) : '')
            . ($comprobante?->aceptado ? ' · ' . $comprobante->nombre_tipo . ' ' . $comprobante->numero_completo : '') . '.';

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'aviso' => $avisoComprobante,
            'venta' => $this->formatear($venta->fresh('comprobante')),
            'stats' => $this->stats(),
        ]);
    }

    /** Lista de ventas de hoy (para refrescar el panel lateral sin recargar). */
    public function ventas(): JsonResponse
    {
        return response()->json(['ventas' => $this->ventasDelDia(), 'stats' => $this->stats()]);
    }

    /**
     * Anula una venta: devuelve el stock y, si tenia boleta o factura aceptada,
     * emite la nota de credito por devolucion total.
     */
    public function anular(Request $request, int $venta): JsonResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:3', 'max:200']], [
            'motivo.required' => 'Indica el motivo de la anulación.',
            'motivo.min' => 'Describe un poco más el motivo.',
        ]);

        $venta = VentaPos::query()->with('comprobante')->findOrFail($venta);

        if ($venta->anulada) {
            return response()->json(['message' => 'La venta ya estaba anulada.'], 422);
        }

        // Primero la nota de credito: si SUNAT la rechaza no se toca el stock
        $nota = null;
        $comprobante = $venta->comprobante;
        if ($comprobante && $comprobante->estado === 'aceptado') {
            $config = SunatConfiguracion::delNegocio($venta->tenant_id);
            $nota = NubefactNotas::notaCredito($comprobante, $config, '06', $datos['motivo']);
            if (! $nota->aceptado) {
                return response()->json([
                    'message' => "La nota de crédito {$nota->numero_completo} no fue aceptada: " . ($nota->mensaje ?: $nota->estado) . '. La venta sigue vigente.',
                ], 422);
            }
        }

        DB::transaction(function () use ($venta) {
            $bloqueada = VentaPos::query()->lockForUpdate()->findOrFail($venta->id);
            if ($bloqueada->anulada) {
                return;
            }
            foreach ((array) $bloqueada->items as $linea) {
                Producto::query()->whereKey($linea['producto_id'] ?? 0)->increment('stock', (int) ($linea['cantidad'] ?? 0));
            }
            $bloqueada->update(['estado' => 'anulada', 'anulada_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Venta ' . $venta->numero . ' anulada y stock devuelto'
                . ($nota ? ' · Nota de crédito ' . $nota->numero_completo : '') . '.',
            'venta' => $this->formatear($venta->fresh('comprobante')),
            'stats' => $this->stats(),
        ]);
    }

    /** Ticket imprimible (80 mm) de la venta. */
    public function ticket(int $venta)
    {
        $venta = VentaPos::query()->with('comprobante')->findOrFail($venta);
        $tenant = Tenant::find($venta->tenant_id);

        return view('admin.pos.ticket', [
            'venta' => $venta,
            'comprobante' => $venta->comprobante,
            'negocio' => $tenant,
        ]);
    }

    // ----------------------------------------------------------------------------------------

    private function emitirComprobante(VentaPos $venta, ?string $direccion): ComprobanteElectronico
    {
        $config = SunatConfiguracion::delNegocio($venta->tenant_id);
        $items = collect((array) $venta->items)->map(fn ($l) => [
            'codigo' => (string) ($l['codigo'] ?? 'P' . ($l['producto_id'] ?? '')),
            'descripcion' => (string) $l['nombre'],
            'cantidad' => (float) $l['cantidad'],
            'precio_unitario' => (float) $l['precio'],
        ])->all();

        return Nubefact::emitir($config, $venta->tipo_comprobante === 'factura' ? '01' : '03', [
            'documento' => $venta->cliente_documento,
            'nombre' => $venta->cliente_nombre ?: 'CLIENTES VARIOS',
            'direccion' => $direccion,
        ], $items, null, $venta->id);
    }

    private function validarDocumento(string $tipo, string $documento): void
    {
        if ($tipo === 'factura') {
            if (strlen($documento) !== 11 || ! in_array(substr($documento, 0, 2), ['10', '15', '17', '20'], true)) {
                throw ValidationException::withMessages(['cliente_documento' => 'Para factura ingresa un RUC válido de 11 dígitos (empieza con 10 o 20).']);
            }
        } elseif ($documento !== '' && ! in_array(strlen($documento), [8, 11], true)) {
            throw ValidationException::withMessages(['cliente_documento' => 'El DNI tiene 8 dígitos y el RUC 11.']);
        }
    }

    /** Suma cantidades si el mismo producto llega en varias lineas. */
    private function agrupar(array $items): array
    {
        $agrupado = [];
        foreach ($items as $it) {
            $id = (int) $it['producto_id'];
            $agrupado[$id] = ($agrupado[$id] ?? 0) + (int) $it['cantidad'];
        }

        return $agrupado;
    }

    private function itemsComprobante(array $lineas): array
    {
        return array_map(fn ($l) => [
            'codigo' => $l['codigo'], 'descripcion' => $l['nombre'],
            'cantidad' => $l['cantidad'], 'precio_unitario' => $l['precio'],
        ], $lineas);
    }

    private function stats(): array
    {
        $hoy = VentaPos::query()->vigentes()->whereDate('created_at', today());

        $total = round((float) (clone $hoy)->sum('total'), 2);
        $cantidad = (clone $hoy)->count();

        return [
            'ventas_hoy' => $cantidad,
            'total_hoy' => $total,
            'igv_hoy' => round((float) (clone $hoy)->sum('igv'), 2),
            'ticket_promedio' => $cantidad ? round($total / $cantidad, 2) : 0,
            'por_metodo' => (clone $hoy)->selectRaw('metodo_pago, SUM(total) as total')->groupBy('metodo_pago')
                ->pluck('total', 'metodo_pago')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }

    private function ventasDelDia(): array
    {
        return VentaPos::query()->with('comprobante')->whereDate('created_at', today())
            ->orderByDesc('id')->limit(30)->get()
            ->map(fn (VentaPos $v) => $this->formatear($v))->all();
    }

    private function formatear(VentaPos $v): array
    {
        $c = $v->comprobante;

        return [
            'id' => $v->id,
            'numero' => $v->numero,
            'hora' => $v->created_at?->timezone('America/Lima')->format('H:i'),
            'total' => (float) $v->total,
            'gravada' => (float) $v->gravada,
            'igv' => (float) $v->igv,
            'vuelto' => $v->vuelto,
            'recibido' => $v->recibido,
            'metodo' => $v->metodo_pago,
            'metodo_nombre' => $v->nombre_metodo,
            'cliente' => $v->cliente_nombre,
            'estado' => $v->estado ?? 'pagada',
            'items' => count((array) $v->items),
            'tipo_comprobante' => $v->tipo_comprobante ?? 'ticket',
            'comprobante' => $c ? [
                'numero' => $c->numero_completo,
                'tipo' => $c->nombre_tipo,
                'estado' => $c->estado,
                'badge' => $c->badge,
                'simulado' => (bool) $c->simulado,
                'pdf' => $c->enlace_pdf,
            ] : null,
            'ticket_url' => route('admin.pos.ticket', $v->id),
        ];
    }
}
