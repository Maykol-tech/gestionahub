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

        // Inventario por sucursal: cuánto hay de cada producto en cada local.
        $sucursales = \App\Support\InventarioSucursal::disponible()
            ? \App\Models\Sucursal::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']) : collect();
        if ($sucursales->isNotEmpty()) {
            $repartos = \App\Models\StockSucursal::query()->whereIn('producto_id', $productos->pluck('id'))->get()->groupBy('producto_id');
            $productos = $productos->map(function ($p) use ($repartos, $sucursales) {
                $por = ($repartos[$p['id']] ?? collect())->pluck('stock', 'sucursal_id');
                $p['locales'] = ['0' => max(0, $p['stock'] - (int) $por->sum())];
                foreach ($sucursales as $s) {
                    $p['locales'][(string) $s->id] = (int) ($por[$s->id] ?? 0);
                }

                return $p;
            });
        }

        return view('admin.modulos.pos', [
            'sucursales' => $sucursales,
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
            'estadoCaja' => \App\Support\Caja::estado(),
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
            'cliente_id' => ['nullable', 'integer'],
            'puntos_canje' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'sucursal_id' => ['nullable', 'integer'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'items.required' => 'Agrega al menos un producto a la venta.',
            'cliente_nombre.required_if' => 'Para factura escribe la razón social.',
        ]);

        // GEST-67: no se vende sin caja abierta
        $estadoCaja = \App\Support\Caja::estado();
        if ($estadoCaja !== 'abierta') {
            return response()->json([
                'message' => $estadoCaja === 'cerrada'
                    ? 'La caja de hoy ya se cerró. No se pueden registrar más ventas hoy.'
                    : 'Abre la caja antes de vender: registra el monto inicial.',
                'caja' => $estadoCaja,
            ], 409);
        }

        $documento = preg_replace('/\D/', '', (string) ($datos['cliente_documento'] ?? ''));
        $this->validarDocumento($datos['tipo_comprobante'], $documento);

        $sucursalId = ! empty($datos['sucursal_id']) && \App\Support\InventarioSucursal::disponible()
            ? \App\Models\Sucursal::query()->where('activo', true)->whereKey($datos['sucursal_id'])->value('id') : null;

        $venta = DB::transaction(function () use ($request, $datos, $documento, $sucursalId) {
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

                if (\App\Support\InventarioSucursal::disponible() && \App\Models\Sucursal::query()->where('activo', true)->exists()) {
                    $hayLocal = \App\Support\InventarioSucursal::enLocal($producto, $sucursalId);
                    if ($hayLocal < $cantidad) {
                        throw ValidationException::withMessages([
                            'items' => "En " . \App\Support\InventarioSucursal::nombre($sucursalId) . " solo hay {$hayLocal} de {$producto->nombre}. Traslada mercadería en Sucursales > Inventario.",
                        ]);
                    }
                }

                $precio = round((float) ($producto->precio_oferta ?? $producto->precio), 2);
                $producto->decrement('stock', $cantidad);
                \App\Support\InventarioSucursal::descontarVenta($producto, $sucursalId, $cantidad);

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
            // GEST-77: canje de puntos como descuento global, con tope
            $cliente = null;
            $puntosUsados = 0;
            $descuento = 0.0;
            if (! empty($datos['cliente_id'])) {
                $cliente = \App\Models\CrmCliente::query()->lockForUpdate()->find($datos['cliente_id']);
                if (! $cliente) {
                    throw ValidationException::withMessages(['cliente_id' => 'El cliente ya no existe. Búscalo de nuevo.']);
                }
                $solicitados = (int) ($datos['puntos_canje'] ?? 0);
                if ($solicitados > 0) {
                    $programa = \App\Support\Fidelizacion::programa((int) $request->user()->tenant_id);
                    $bruto = Nubefact::calcular($this->itemsComprobante($lineas))['total'];
                    $max = \App\Support\Fidelizacion::maximoCanje((int) $cliente->puntos_saldo, $bruto, $programa);
                    if ($solicitados > $max['puntos']) {
                        throw ValidationException::withMessages(['puntos_canje' => "Puede usar hasta {$max['puntos']} puntos en esta venta (tope {$programa['tope_pct']}% del total)."]);
                    }
                    $puntosUsados = $solicitados;
                    $descuento = round($solicitados * $programa['valor_punto'], 2);
                }
            }

            $calculo = Nubefact::calcular($this->itemsComprobante($lineas), $descuento);
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
                'cliente_nombre' => ($datos['cliente_nombre'] ?? null) ?: $cliente?->nombre,
                'cliente_documento' => $documento ?: null,
                'cliente_id' => $cliente?->id,
                'descuento' => $calculo['descuento'],
                'puntos_usados' => $puntosUsados,
                'tipo_comprobante' => $datos['tipo_comprobante'],
                'items' => $lineas,
                'estado' => 'pagada',
            ] + ($sucursalId && \Illuminate\Support\Facades\Schema::hasColumn('ventas_pos', 'sucursal_id') ? ['sucursal_id' => $sucursalId] : []));

            $venta->update(['numero' => 'POS-' . str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT)]);
            \App\Support\Fidelizacion::alCobrarVentaPos($venta);

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

    /** Busca clientes por nombre, teléfono o DNI para sumar o canjear puntos (GEST-76/77). */
    public function buscarClientes(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $programa = \App\Support\Fidelizacion::programa((int) $request->user()->tenant_id);
        if (mb_strlen($q) < 2) {
            return response()->json(['clientes' => [], 'programa' => $programa]);
        }
        // Tarjeta digital: se escanea su QR (trae el enlace) o se escribe el código XXXX-XXXX.
        if (preg_match('~/tarjeta/([A-Za-z0-9]{20,48})~', $q, $m) || preg_match('~^[A-Za-z0-9]{32}$~', $q, $m)) {
            $token = $m[1] ?? $m[0];
            $porTarjeta = \App\Models\CrmCliente::query()->where('tarjeta_token', $token);
        } elseif (preg_match('~^([A-Za-z0-9]{4})-([A-Za-z0-9]{4})$~', $q, $m)) {
            $porTarjeta = \App\Models\CrmCliente::query()->whereNotNull('tarjeta_token')
                ->whereRaw('LOWER(SUBSTR(tarjeta_token, 1, 8)) = ?', [strtolower($m[1] . $m[2])]);
        }
        if (isset($porTarjeta)) {
            $clientes = $porTarjeta->limit(2)->get(['id', 'nombre', 'telefono', 'dni', 'puntos_saldo'])
                ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'telefono' => $c->telefono, 'dni' => $c->dni, 'saldo' => (int) $c->puntos_saldo]);

            return response()->json(['clientes' => $clientes, 'programa' => $programa, 'tarjeta' => true]);
        }

        $digitos = preg_replace('/\D/', '', $q);
        $clientes = \App\Models\CrmCliente::query()
            ->where(function ($w) use ($q, $digitos) {
                $w->where('nombre', 'like', "%{$q}%");
                if (strlen($digitos) >= 3) {
                    $w->orWhere('telefono', 'like', "%{$digitos}%")->orWhere('dni', 'like', "%{$digitos}%");
                }
            })
            ->orderBy('nombre')->limit(8)->get(['id', 'nombre', 'telefono', 'dni', 'puntos_saldo'])
            ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'telefono' => $c->telefono, 'dni' => $c->dni, 'saldo' => (int) $c->puntos_saldo]);

        return response()->json(['clientes' => $clientes, 'programa' => $programa]);
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
                // Si se vendió en una sucursal, las unidades vuelven a esa sucursal.
                if (! empty($bloqueada->sucursal_id) && \App\Support\InventarioSucursal::disponible()) {
                    \App\Models\StockSucursal::query()->where('producto_id', $linea['producto_id'] ?? 0)->where('sucursal_id', $bloqueada->sucursal_id)
                        ->increment('stock', (int) ($linea['cantidad'] ?? 0));
                }
            }
            $bloqueada->update(['estado' => 'anulada', 'anulada_at' => now()]);
            \App\Support\Fidelizacion::alAnularVentaPos($bloqueada);
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
        ], $items, null, $venta->id, (float) $venta->descuento);
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
            'descuento' => (float) ($v->descuento ?? 0),
            'puntos_usados' => (int) ($v->puntos_usados ?? 0),
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
