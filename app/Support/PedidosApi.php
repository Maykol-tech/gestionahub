<?php

namespace App\Support;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * GEST-62: API pública de pedidos para que el negocio conecte su web, app u otro sistema.
 *  - Token por negocio (Sanctum, permiso "pedidos") que se crea y revoca en /admin/api.
 *  - Aviso (webhook) firmado a la URL del negocio cada vez que un pedido cambia de estado.
 */
class PedidosApi
{
    public const ESTADOS = ['pendiente', 'confirmado', 'preparando', 'listo', 'en_camino', 'entregado', 'cancelado'];
    public const METODOS = ['efectivo', 'yape', 'plin', 'tarjeta', 'mercadopago', 'transferencia'];
    public const PERMISO = 'pedidos';

    public static function formatear(Pedido $p): array
    {
        return [
            'id' => $p->id,
            'numero' => $p->numero,
            'estado' => $p->estado,
            'canal' => $p->canal,
            'tipo' => $p->tipo,
            'cliente' => ['nombre' => $p->cliente_nombre ?? optional($p->cliente)->nombre, 'telefono' => $p->cliente_telefono ?? optional($p->cliente)->telefono],
            'direccion' => $p->direccion_entrega,
            'notas' => $p->notas,
            'metodo_pago' => $p->metodo_pago,
            'pagado' => (bool) $p->pagado,
            'subtotal' => (float) $p->subtotal,
            'descuento' => (float) $p->descuento,
            'costo_envio' => (float) $p->costo_envio,
            'total' => (float) $p->total,
            'items' => $p->relationLoaded('items') ? $p->items->map(fn ($i) => [
                'producto_id' => $i->producto_id, 'variante_id' => $i->variante_id, 'nombre' => $i->nombre,
                'variante' => $i->variante_nombre, 'cantidad' => (float) $i->cantidad,
                'precio_unit' => (float) ($i->precio_unit ?? $i->precio_unitario), 'subtotal' => (float) $i->subtotal,
            ])->values()->all() : null,
            'creado' => $p->created_at?->toIso8601String(),
            'actualizado' => $p->updated_at?->toIso8601String(),
            'entregado' => $p->entregado_at?->toIso8601String(),
        ];
    }

    /** Crea un pedido desde otro sistema. Los precios salen del catálogo, nunca del que llama. */
    public static function crear(int $tenantId, array $d): Pedido
    {
        return DB::transaction(function () use ($tenantId, $d) {
            $lineas = [];
            $subtotal = 0.0;
            foreach ($d['items'] as $n => $it) {
                $q = Producto::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activo', true)->lockForUpdate();
                $producto = ! empty($it['producto_id']) ? $q->find((int) $it['producto_id']) : $q->where('sku', (string) ($it['sku'] ?? ''))->first();
                if (! $producto) {
                    throw ValidationException::withMessages(["items.$n" => 'El producto no existe o está inactivo.']);
                }
                $cantidad = (int) $it['cantidad'];
                $variante = null;
                if (! empty($it['variante_id'])) {
                    $variante = ProductoVariante::withoutGlobalScopes()->where('producto_id', $producto->id)->where('activo', true)->lockForUpdate()->find((int) $it['variante_id']);
                    if (! $variante) {
                        throw ValidationException::withMessages(["items.$n" => "La variante de {$producto->nombre} no existe."]);
                    }
                } elseif ($producto->tiene_variantes) {
                    throw ValidationException::withMessages(["items.$n" => "{$producto->nombre} tiene variantes: envía variante_id."]);
                }
                $stock = $variante ? (int) $variante->stock : (int) $producto->stock;
                if ($stock < $cantidad) {
                    throw ValidationException::withMessages(["items.$n" => "Solo quedan {$stock} de {$producto->nombre}."]);
                }
                [$unitario] = TiendaPedido::precios((float) ($variante?->precio ?? $producto->precio), (float) ($variante ? $variante->precio_oferta : $producto->precio_oferta));
                $lineas[] = compact('producto', 'variante', 'cantidad', 'unitario');
                $subtotal += $unitario * $cantidad;
            }

            $envio = round((float) ($d['costo_envio'] ?? 0), 2);
            $pedido = Pedido::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId, 'numero' => 'A-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
                'canal' => 'app', 'estado' => 'pendiente', 'tipo' => $d['tipo'] ?? 'recojo',
                'subtotal' => round($subtotal, 2), 'descuento' => 0, 'costo_envio' => $envio, 'igv' => 0, 'total' => round($subtotal + $envio, 2),
                'direccion_entrega' => $d['direccion'] ?? null, 'notas' => trim(($d['referencia_externa'] ?? null ? 'Ref. externa: ' . $d['referencia_externa'] . ' · ' : '') . ($d['notas'] ?? ''), ' ·') ?: null,
                'metodo_pago' => $d['metodo_pago'] ?? 'efectivo', 'pagado' => (bool) ($d['pagado'] ?? false),
                'cliente_nombre' => $d['cliente']['nombre'], 'cliente_telefono' => preg_replace('/\D/', '', (string) ($d['cliente']['telefono'] ?? '')) ?: null,
            ]);
            foreach ($lineas as $l) {
                PedidoItem::create([
                    'tenant_id' => $tenantId, 'pedido_id' => $pedido->id, 'producto_id' => $l['producto']->id, 'variante_id' => $l['variante']?->id,
                    'nombre' => $l['producto']->nombre, 'variante_nombre' => $l['variante']?->nombre, 'cantidad' => $l['cantidad'],
                    'precio_unit' => $l['unitario'], 'subtotal' => round($l['unitario'] * $l['cantidad'], 2),
                ]);
                if ($l['variante']) {
                    $l['variante']->decrement('stock', $l['cantidad']);
                    $l['producto']->recalcularStock();
                } else {
                    $l['producto']->decrement('stock', $l['cantidad']);
                }
            }

            return $pedido;
        });
    }

    /** Mismas reglas que el tablero de pedidos: confirmado_at, entregado_at, pagado al entregar y stock al cancelar. */
    public static function cambiarEstado(Pedido $pedido, string $estado, ?string $motivo = null): Pedido
    {
        return DB::transaction(function () use ($pedido, $estado, $motivo) {
            $p = Pedido::withoutGlobalScopes()->with('items')->lockForUpdate()->findOrFail($pedido->id);
            if ($p->estado === $estado) {
                return $p;
            }
            if ($p->estado === 'cancelado') {
                throw ValidationException::withMessages(['estado' => 'El pedido está cancelado y no se puede cambiar.']);
            }
            if ($estado === 'cancelado') {
                if ($p->estado === 'entregado') {
                    throw ValidationException::withMessages(['estado' => 'Un pedido entregado no se puede cancelar.']);
                }
                self::devolverStock($p);
                $p->update(['estado' => 'cancelado', 'cancelado_at' => now(), 'notas' => trim(($p->notas ?? '') . ($motivo ? "\nCancelado: {$motivo}" : '')) ?: null]);

                return $p;
            }
            $cambios = ['estado' => $estado, 'entregado_at' => $estado === 'entregado' ? ($p->entregado_at ?? now()) : null];
            if ($estado !== 'pendiente' && ! $p->confirmado_at) {
                $cambios['confirmado_at'] = now();
            }
            if ($estado === 'entregado' && $p->metodo_pago !== 'efectivo') {
                $cambios['pagado'] = true;
            }
            $p->update($cambios);

            return $p;
        });
    }

    private static function devolverStock(Pedido $p): void
    {
        foreach ($p->items as $i) {
            if ($i->variante_id) {
                ProductoVariante::withoutGlobalScopes()->where('id', $i->variante_id)->increment('stock', (int) $i->cantidad);
                Producto::withoutGlobalScopes()->find($i->producto_id)?->recalcularStock();
            } elseif ($i->producto_id) {
                Producto::withoutGlobalScopes()->where('id', $i->producto_id)->where('tiene_variantes', false)->increment('stock', (int) $i->cantidad);
            }
        }
    }

    // ---------------------------------------------------------------- Aviso (webhook)

    /** Se llama desde Pedido::updated. Sin URL configurada no hace nada. */
    public static function alCambiarEstado(Pedido $p, ?string $anterior): void
    {
        $a = AjustesNegocio::leer((int) $p->tenant_id);
        if (! $a['api_webhook_url']) {
            return;
        }
        // Se envía cuando el cambio ya quedó guardado (fuera de la transacción)
        DB::afterCommit(fn () => self::enviar((int) $p->tenant_id, [
            'evento' => 'pedido.estado', 'estado_anterior' => $anterior,
            'pedido' => self::formatear($p->loadMissing('items')),
        ]));
    }

    public static function enviar(int $tenantId, array $carga): ?int
    {
        $a = AjustesNegocio::leer($tenantId);
        if (! $a['api_webhook_url']) {
            return null;
        }
        $carga += ['enviado' => now()->toIso8601String()];
        $cuerpo = json_encode($carga, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $firma = hash_hmac('sha256', $cuerpo, (string) $a['api_webhook_secreto']);
        try {
            $r = Http::timeout(5)->withHeaders(['X-GestionaHub-Evento' => $carga['evento'], 'X-GestionaHub-Firma' => 'sha256=' . $firma])
                ->withBody($cuerpo, 'application/json')->post($a['api_webhook_url']);
            $codigo = $r->status();
        } catch (\Throwable) {
            $codigo = 0; // sin respuesta: el pedido sigue su curso igual
        }
        Cache::put("api_webhook_ultimo:{$tenantId}", ['codigo' => $codigo, 'evento' => $carga['evento'], 'fecha' => now()->format('d/m/Y H:i')], now()->addDays(30));

        return $codigo;
    }

    public static function ultimoEnvio(int $tenantId): ?array
    {
        return Cache::get("api_webhook_ultimo:{$tenantId}");
    }
}
