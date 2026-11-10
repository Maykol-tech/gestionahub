<?php

namespace App\Http\Controllers;

use App\Models\CrmCliente;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Panel de Pedidos:
 *  GEST-21 CRUD de pedidos con cambio de estado (descuenta y devuelve stock)
 *  GEST-22 Vista kanban de pedidos por estado
 */
class PedidosPanelController extends DashboardController
{
    /** Columnas del tablero: clave => [titulo, badge, estados que agrupa, estado al soltar] */
    private const COLUMNAS = [
        'pendiente'  => ['Pendiente', 'b-a', ['pendiente'], 'pendiente'],
        'preparando' => ['En preparación', 'b-n', ['confirmado', 'preparando', 'listo'], 'preparando'],
        'en_camino'  => ['En camino', 'b-g', ['en_camino'], 'en_camino'],
        'entregado'  => ['Entregado', 'b-ok', ['entregado'], 'entregado'],
    ];

    private const ESTADOS = ['pendiente', 'confirmado', 'preparando', 'listo', 'en_camino', 'entregado', 'cancelado'];
    private const PAGOS = ['efectivo', 'yape', 'plin', 'tarjeta', 'mercadopago', 'transferencia'];

    // =================================================================
    // VISTAS Y LISTADOS
    // =================================================================

    public function pedidos()
    {
        $this->compartirLayout();

        return view('admin.pedidos', [
            'pedidos' => $this->listado(),
            'columnas' => $this->columnas(),
            'clientes' => $this->clientesParaPedido(),
            'catalogo' => $this->catalogo(),
            'hayTabla' => $this->hayPedidos(),
        ]);
    }

    public function pedidosLista()
    {
        return response()->json(['data' => $this->listado()]);
    }

    public function detalle($id)
    {
        $pedido = Pedido::with(['items', 'cliente'])->findOrFail($id);

        return response()->json(['data' => $this->formatearDetalle($pedido)]);
    }

    // =================================================================
    // CREAR, EDITAR, MOVER Y CANCELAR
    // =================================================================

    public function guardar(Request $request)
    {
        return $this->persistir($request, null);
    }

    public function actualizar(Request $request, $id)
    {
        $pedido = Pedido::with('items')->findOrFail($id);
        abort_if(in_array($pedido->estado, ['entregado', 'cancelado'], true), 422, 'Un pedido entregado o cancelado ya no se puede editar.');

        return $this->persistir($request, $pedido);
    }

    /**
     * Cambia el estado: acepta una columna del tablero (arrastrar y soltar o selector
     * rapido) o un estado exacto. Puede saltar etapas (de Pendiente a Entregado).
     *
     * Efectos en la base de datos:
     *  - El stock ya se descontó al registrar el pedido; aquí no se vuelve a descontar.
     *    Si el pedido se suelta en "Cancelar" se delega en cancelar(), que lo devuelve.
     *  - confirmado_at / entregado_at alimentan el historial y los KPI del panel.
     *  - Al entregar, un pago que no es en efectivo queda como pagado.
     *  - Pedido::updated suma o revierte los puntos de fidelización.
     */
    public function mover(Request $request, $id)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_merge(array_keys(self::COLUMNAS), self::ESTADOS))],
        ], self::MENSAJES);

        $estado = isset(self::COLUMNAS[$datos['estado']]) && ! in_array($datos['estado'], self::ESTADOS, true)
            ? self::COLUMNAS[$datos['estado']][3] : $datos['estado'];

        if ($estado === 'cancelado') {
            return $this->cancelar($request, $id);
        }

        $resultado = DB::transaction(function () use ($id, $estado) {
            // Bloquea la fila: dos personas moviendo el mismo pedido no se pisan
            $pedido = Pedido::lockForUpdate()->findOrFail($id);
            if ($pedido->estado === 'cancelado') {
                return [$pedido, 'cancelado'];
            }

            // Repetir el mismo estado (doble clic, dos pestañas abiertas) no cambia nada
            if ($pedido->estado === $estado) {
                return [$pedido, 'igual'];
            }

            $cambios = ['estado' => $estado];
            if ($estado !== 'pendiente' && ! $pedido->confirmado_at) {
                $cambios['confirmado_at'] = now();
            }
            $cambios['entregado_at'] = $estado === 'entregado' ? ($pedido->entregado_at ?? now()) : null;
            if ($estado === 'entregado' && $pedido->metodo_pago !== 'efectivo') {
                $cambios['pagado'] = true;
            }
            $pedido->update($cambios);

            return [$pedido, 'movido'];
        });

        [$pedido, $resultadoMovimiento] = $resultado;

        if ($resultadoMovimiento === 'cancelado') {
            return response()->json(['message' => 'El pedido está cancelado y no se puede mover.'], 422);
        }
        $pedido = $pedido->fresh(['cliente']);

        return response()->json([
            'message' => $resultadoMovimiento === 'igual'
                ? "Pedido {$pedido->numero} ya estaba en " . (self::ESTADOS_TEXTO[$pedido->estado] ?? $pedido->estado)
                : "Pedido {$pedido->numero} movido a " . (self::ESTADOS_TEXTO[$estado] ?? $estado),
            'data' => $this->formatearTarjeta($pedido),
        ]);
    }

    /** Cancela el pedido y devuelve el stock de sus productos. */
    public function cancelar(Request $request, $id)
    {
        $datos = $request->validate(['motivo' => ['nullable', 'string', 'max:200']], self::MENSAJES);
        $pedido = Pedido::with('items')->findOrFail($id);

        if ($pedido->estado === 'cancelado') {
            return response()->json(['message' => 'El pedido ya estaba cancelado.'], 422);
        }
        if ($pedido->estado === 'entregado') {
            return response()->json(['message' => 'Un pedido entregado no se puede cancelar.'], 422);
        }

        DB::transaction(function () use ($pedido, $datos) {
            $this->devolverStock($pedido->items);
            $notas = trim(($pedido->notas ?? '') . (! empty($datos['motivo']) ? "\nCancelado: " . $datos['motivo'] : ''));
            $pedido->update(['estado' => 'cancelado', 'cancelado_at' => now(), 'notas' => $notas ?: null]);
        });

        return response()->json([
            'message' => "Pedido {$pedido->numero} cancelado. El stock se devolvió al inventario.",
            'data' => $this->formatearTarjeta($pedido->fresh()),
            'catalogo' => $this->catalogo(),
        ]);
    }

    public function marcarPagado($id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['pagado' => ! $pedido->pagado]);

        return response()->json([
            'message' => $pedido->pagado ? "Pedido {$pedido->numero} marcado como pagado" : "Pedido {$pedido->numero} marcado como pendiente de pago",
            'data' => $this->formatearTarjeta($pedido->fresh()),
        ]);
    }

    // =================================================================
    // AYUDANTES
    // =================================================================

    private function persistir(Request $request, ?Pedido $pedido)
    {
        $tenantId = $this->tenantId();
        $clienteNuevo = $request->boolean('cliente_nuevo');

        $datos = $request->validate([
            'cliente_id'       => [$clienteNuevo ? 'nullable' : 'required', Rule::exists('crm_clientes', 'id')->where('tenant_id', $tenantId)],
            'cliente_nuevo'    => ['nullable', 'boolean'],
            'cliente_nombre'   => [$clienteNuevo ? 'required' : 'nullable', 'string', 'max:120'],
            'cliente_telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]{6,20}$/'],
            'tipo'             => ['required', Rule::in(['delivery', 'recojo', 'mesa'])],
            'canal'            => ['nullable', Rule::in(['web', 'whatsapp', 'pos', 'app', 'manual'])],
            'metodo_pago'      => ['required', Rule::in(self::PAGOS)],
            'pagado'           => ['nullable', 'boolean'],
            'direccion_entrega'=> ['nullable', 'required_if:tipo,delivery', 'string', 'max:255'],
            'costo_envio'      => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'descuento'        => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'notas'            => ['nullable', 'string', 'max:1000'],
            'items'            => ['required', 'array', 'min:1', 'max:50'],
            'items.*.producto_id' => ['required', 'integer'],
            'items.*.variante_id' => ['nullable', 'integer'],
            'items.*.cantidad'    => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.notas'       => ['nullable', 'string', 'max:255'],
        ], self::MENSAJES + [
            'cliente_id.required' => 'Elige un cliente o registra uno nuevo.',
            'cliente_nombre.required' => 'Escribe el nombre del cliente.',
            'cliente_telefono.regex' => 'Teléfono no válido.',
            'direccion_entrega.required_if' => 'Indica la dirección de entrega.',
            'items.required' => 'Agrega al menos un producto.',
            'items.min' => 'Agrega al menos un producto.',
            'items.*.cantidad.min' => 'Mínimo 1.',
        ]);

        $resultado = DB::transaction(function () use ($datos, $pedido, $tenantId, $clienteNuevo) {
            // Al editar se devuelve primero el stock anterior y luego se descuenta el nuevo
            if ($pedido) {
                $this->devolverStock($pedido->items);
            }

            $lineas = [];
            foreach ($datos['items'] as $i => $item) {
                $producto = Producto::lockForUpdate()->find($item['producto_id']);
                if (! $producto) {
                    throw ValidationException::withMessages(["items.$i.producto_id" => 'El producto ya no existe.']);
                }
                $variante = null;
                if ($producto->tiene_variantes) {
                    $variante = ProductoVariante::lockForUpdate()->where('producto_id', $producto->id)->find($item['variante_id'] ?? 0);
                    if (! $variante) {
                        throw ValidationException::withMessages(["items.$i.variante_id" => "Elige la variante de {$producto->nombre}."]);
                    }
                }

                $disponible = $variante ? (int) $variante->stock : (int) $producto->stock;
                if ($item['cantidad'] > $disponible) {
                    $nombre = $producto->nombre . ($variante ? ' ' . $variante->nombre : '');
                    throw ValidationException::withMessages([
                        "items.$i.cantidad" => $disponible > 0 ? "Solo quedan {$disponible} de {$nombre}." : "{$nombre} está sin stock.",
                    ]);
                }

                $precio = $variante
                    ? (float) ($variante->precio_oferta ?? $variante->precio ?? $producto->precio_oferta ?? $producto->precio)
                    : (float) ($producto->precio_oferta ?? $producto->precio);

                $lineas[] = [
                    'producto' => $producto, 'variante' => $variante, 'cantidad' => (int) $item['cantidad'],
                    'nombre' => $producto->nombre . ($variante ? ' (' . $variante->nombre . ')' : ''),
                    'precio_unit' => $precio, 'subtotal' => round($precio * $item['cantidad'], 2),
                    'notas' => $item['notas'] ?? null,
                ];
            }

            if ($clienteNuevo) {
                $cliente = CrmCliente::create([
                    'tenant_id' => $tenantId, 'nombre' => trim($datos['cliente_nombre']),
                    'telefono' => $datos['cliente_telefono'] ?? null, 'fuente' => 'manual', 'activo' => true,
                    'direccion' => $datos['tipo'] === 'delivery' ? ($datos['direccion_entrega'] ?? null) : null,
                ]);
                $datos['cliente_id'] = $cliente->id;
            }

            $subtotal = round(array_sum(array_column($lineas, 'subtotal')), 2);
            $envio = $datos['tipo'] === 'delivery' ? (float) ($datos['costo_envio'] ?? 0) : 0;
            $descuento = min((float) ($datos['descuento'] ?? 0), $subtotal);
            $total = round($subtotal - $descuento + $envio, 2);

            $campos = [
                'cliente_id' => $datos['cliente_id'],
                'tipo' => $datos['tipo'],
                'metodo_pago' => $datos['metodo_pago'],
                'pagado' => (bool) ($datos['pagado'] ?? false),
                'direccion_entrega' => $datos['tipo'] === 'delivery' ? $datos['direccion_entrega'] : null,
                'notas' => $datos['notas'] ?? null,
                'subtotal' => $subtotal, 'descuento' => $descuento, 'costo_envio' => $envio,
                'igv' => round($total - $total / 1.18, 2), 'total' => $total,
            ];

            if ($pedido) {
                $pedido->update($campos);
                $pedido->items()->delete();
            } else {
                $pedido = Pedido::create($campos + [
                    'tenant_id' => $tenantId,
                    'numero' => Pedido::siguienteNumero($tenantId),
                    'canal' => $datos['canal'] ?? 'manual',
                    'estado' => 'pendiente',
                ]);
            }

            foreach ($lineas as $l) {
                PedidoItem::create([
                    'tenant_id' => $tenantId, 'pedido_id' => $pedido->id,
                    'producto_id' => $l['producto']->id, 'variante_id' => $l['variante']?->id,
                    'nombre' => $l['nombre'], 'cantidad' => $l['cantidad'],
                    'precio_unit' => $l['precio_unit'], 'subtotal' => $l['subtotal'], 'notas' => $l['notas'],
                ]);
            }

            // Descuenta el stock y junta los productos que llegaron a su minimo (GEST-20)
            $alertas = [];
            foreach ($lineas as $l) {
                if ($l['variante']) {
                    $l['variante']->decrement('stock', $l['cantidad']);
                    $l['producto']->refresh()->recalcularStock();
                    $v = $l['variante']->fresh();
                    if ($v->stock <= $v->stock_minimo) {
                        $alertas[$l['nombre']] = $this->textoAlerta($l['nombre'], $v->stock);
                    }
                } else {
                    $l['producto']->decrement('stock', $l['cantidad']);
                    $p = $l['producto']->fresh();
                    if ($p->stock <= $p->stock_minimo) {
                        $alertas[$p->nombre] = $this->textoAlerta($p->nombre, $p->stock);
                    }
                }
            }

            return [$pedido, array_values($alertas)];
        });

        [$pedido, $alertas] = $resultado;
        $pedido = $pedido->fresh(['items', 'cliente']);
        $creado = $request->route('id') === null;

        return response()->json([
            'message' => $creado ? "Pedido {$pedido->numero} registrado por " . number_format((float) $pedido->total, 2) . ' soles' : "Pedido {$pedido->numero} actualizado",
            'data' => $this->formatearTarjeta($pedido),
            'alertas' => $alertas,
            'catalogo' => $this->catalogo(),
            'clientes' => $clienteNuevo ? $this->clientesParaPedido() : null,
        ], $creado ? 201 : 200);
    }

    private function textoAlerta(string $nombre, int $stock): string
    {
        return $stock <= 0 ? "{$nombre} se quedó sin stock" : "{$nombre} llegó a su stock mínimo (quedan {$stock})";
    }

    /** Devuelve al inventario lo que tenian los items de un pedido. */
    private function devolverStock($items): void
    {
        $productos = [];
        foreach ($items as $item) {
            if ($item->variante_id) {
                ProductoVariante::where('id', $item->variante_id)->increment('stock', $item->cantidad);
                $productos[$item->producto_id] = true;
            } elseif ($item->producto_id) {
                Producto::where('id', $item->producto_id)->where('tiene_variantes', false)->increment('stock', $item->cantidad);
            }
        }
        foreach (array_keys($productos) as $id) {
            Producto::find($id)?->recalcularStock();
        }
    }

    private function columnas()
    {
        return collect(self::COLUMNAS)->map(fn ($c, $k) => ['clave' => $k, 'titulo' => $c[0], 'badge' => $c[1]])->values();
    }

    private function columnaDe(string $estado): ?string
    {
        foreach (self::COLUMNAS as $clave => $c) {
            if (in_array($estado, $c[2], true)) {
                return $clave;
            }
        }

        return null;
    }

    /** Pedidos de los ultimos 30 dias (el tablero oculta entregados de mas de 7 dias). */
    private function listado()
    {
        if (! $this->hayPedidos()) {
            return collect();
        }

        return Pedido::with('cliente')->withCount('items')
            ->where(function ($q) {
                $q->where('created_at', '>=', now()->subDays(30))->orWhereNotIn('estado', ['entregado', 'cancelado']);
            })
            ->orderByDesc('id')->limit(400)->get()
            ->map(fn ($p) => $this->formatearTarjeta($p))->values();
    }

    private function formatearTarjeta(Pedido $p): array
    {
        $estado = $p->estado ?? 'pendiente';
        $columna = $this->columnaDe($estado);
        $creado = Carbon::parse($p->created_at);
        $referencia = $p->entregado_at ?? $p->cancelado_at ?? $p->updated_at;

        return [
            'id' => $p->id,
            'numero' => $p->numero,
            'cliente_id' => $p->cliente_id,
            'cliente' => $p->cliente->nombre ?? 'Cliente sin registrar',
            'telefono' => $p->cliente->telefono ?? null,
            'distrito' => $p->cliente->distrito ?? null,
            'direccion' => $p->direccion_entrega,
            'total' => (float) $p->total,
            'canal' => $p->canal,
            'tipo' => $p->tipo,
            'metodo_pago' => $p->metodo_pago,
            'pagado' => (bool) $p->pagado,
            'estado' => $estado,
            'estado_texto' => self::ESTADOS_TEXTO[$estado] ?? ucfirst($estado),
            'columna' => $columna,
            'badge' => $estado === 'cancelado' ? 'b-r' : self::COLUMNAS[$columna][1],
            'items' => $p->items_count ?? $p->items()->count(),
            'hace' => $creado->diffForHumans(),
            'fecha' => $creado->format('d/m H:i'),
            'creado' => $creado->toIso8601String(),
            'reciente' => Carbon::parse($referencia)->gte(now()->subDays(7)),
        ];
    }

    private function formatearDetalle(Pedido $p): array
    {
        $linea = fn ($fecha, $texto) => $fecha ? ['fecha' => Carbon::parse($fecha)->format('d/m/Y H:i'), 'texto' => $texto] : null;

        return $this->formatearTarjeta($p) + [
            'subtotal' => (float) $p->subtotal,
            'descuento' => (float) $p->descuento,
            'costo_envio' => (float) $p->costo_envio,
            'igv' => (float) $p->igv,
            'notas' => $p->notas,
            'lineas' => $p->items->map(fn ($i) => [
                'producto_id' => $i->producto_id, 'variante_id' => $i->variante_id, 'nombre' => $i->nombre,
                'cantidad' => (int) $i->cantidad, 'precio_unit' => (float) $i->precio_unit,
                'subtotal' => (float) $i->subtotal, 'notas' => $i->notas,
            ])->values(),
            'historial' => array_values(array_filter([
                $linea($p->created_at, 'Pedido registrado'),
                $linea($p->confirmado_at, 'Confirmado por el negocio'),
                $linea($p->entregado_at, 'Entregado al cliente'),
                $linea($p->cancelado_at, 'Pedido cancelado'),
            ])),
        ];
    }

    private function clientesParaPedido()
    {
        return CrmCliente::where('activo', true)->orderBy('nombre')->limit(500)
            ->get(['id', 'nombre', 'telefono', 'direccion', 'distrito'])
            ->map(fn ($c) => [
                'id' => $c->id, 'nombre' => $c->nombre, 'telefono' => $c->telefono,
                'direccion' => trim(($c->direccion ?? '') . ($c->distrito ? ', ' . $c->distrito : ''), ', ') ?: null,
            ])->values();
    }

    /** Productos activos con sus variantes, precio de venta y stock disponible. */
    private function catalogo()
    {
        return Producto::with(['variantes' => fn ($q) => $q->where('activo', true)->orderBy('orden'), 'categoria'])
            ->where('activo', true)->orderBy('nombre')->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre ?? null,
                'imagen_url' => $p->imagen_url,
                'precio' => (float) ($p->precio_oferta ?? $p->precio),
                'stock' => (int) $p->stock,
                'stock_minimo' => (int) $p->stock_minimo,
                'variantes' => $p->tiene_variantes ? $p->variantes->map(fn ($v) => [
                    'id' => $v->id, 'nombre' => $v->nombre,
                    'precio' => (float) ($v->precio_oferta ?? $v->precio ?? $p->precio_oferta ?? $p->precio),
                    'stock' => (int) $v->stock, 'stock_minimo' => (int) $v->stock_minimo,
                ])->values() : [],
            ])->values();
    }
}
