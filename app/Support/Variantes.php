<?php

namespace App\Support;

use App\Models\Pedido;
use App\Models\PesoDespacho;
use App\Models\Producto;
use App\Models\ProductoKilo;
use App\Models\ProductoVariante;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Precios de variantes y de venta por kilo, y ajuste de peso real al despachar (GEST-105 y GEST-111). */
class Variantes
{
    /** Precio unitario de un producto según variante (si la hay). */
    public static function precioUnitario(Producto $producto, ?ProductoVariante $variante = null): float
    {
        if ($variante) {
            return (float) $variante->precio;
        }

        return (float) ($producto->precio_oferta ?? $producto->precio);
    }

    /** Monto de una linea por kilo a partir del peso en kg. */
    public static function montoPorKilo(float $precioKg, float $pesoKg): float
    {
        return round($precioKg * $pesoKg, 2);
    }

    /**
     * Ajusta el pedido con el peso real. La diferencia se suma o resta al total del pedido.
     * Devuelve el registro del ajuste.
     */
    public static function ajustarPeso(Pedido $pedido, int $productoId, float $pesoRealKg, float $pesoEstimadoKg): PesoDespacho
    {
        if ($pesoRealKg <= 0) {
            throw ValidationException::withMessages(['peso_real_kg' => 'El peso real debe ser mayor que cero.']);
        }

        $kilo = ProductoKilo::query()->where('producto_id', $productoId)->where('activo', true)->first();
        if (! $kilo) {
            throw ValidationException::withMessages(['producto_id' => 'Este producto no se vende por kilo.']);
        }

        return DB::transaction(function () use ($pedido, $productoId, $pesoRealKg, $pesoEstimadoKg, $kilo) {
            $pedidoBloqueado = Pedido::query()->whereKey($pedido->id)->lockForUpdate()->first();

            $precioEstimado = self::montoPorKilo((float) $kilo->precio_kg, $pesoEstimadoKg);
            $precioReal = self::montoPorKilo((float) $kilo->precio_kg, $pesoRealKg);
            $diferencia = round($precioReal - $precioEstimado, 2);

            $pedidoBloqueado->update(['total' => round((float) $pedidoBloqueado->total + $diferencia, 2)]);

            return PesoDespacho::query()->create([
                'tenant_id' => $pedido->tenant_id,
                'pedido_id' => $pedido->id,
                'producto_id' => $productoId,
                'peso_estimado_kg' => $pesoEstimadoKg,
                'peso_real_kg' => $pesoRealKg,
                'precio_kg' => $kilo->precio_kg,
                'diferencia' => $diferencia,
            ]);
        });
    }

    /** Usa DB para leer el precio de variantes sin cargar productos completos. */
    public static function variantesActivas(int $productoId)
    {
        return ProductoVariante::query()->where('producto_id', $productoId)->where('activo', true)
            ->orderBy('precio')->get(['id', 'nombre', 'precio', 'stock']);
    }

    /**
     * GEST-111: registra el peso real de una línea vendida por kilo (pedido de la tienda) y recalcula
     * el subtotal de la línea y el total del pedido. Se puede corregir varias veces: siempre parte del peso pedido.
     */
    public static function ajustarPesoItem(\App\Models\PedidoItem $item, float $pesoRealKg): \App\Models\PedidoItem
    {
        if ($pesoRealKg <= 0 || $pesoRealKg > 999) {
            throw ValidationException::withMessages(['peso_real_kg' => 'Escribe el peso real en kilos (por ejemplo 0.48).']);
        }
        $pesoPedido = (float) $item->peso_kg;
        if ($pesoPedido <= 0) {
            throw ValidationException::withMessages(['peso_real_kg' => 'Esta línea no se vende por kilo.']);
        }

        return DB::transaction(function () use ($item, $pesoRealKg, $pesoPedido) {
            $pedido = Pedido::query()->whereKey($item->pedido_id)->lockForUpdate()->firstOrFail();
            $item = \App\Models\PedidoItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            $precioKg = round((float) $item->precio_unit / $pesoPedido, 4);
            $nuevoUnit = self::montoPorKilo($precioKg, $pesoRealKg);
            $nuevoSubtotal = round($nuevoUnit * (int) $item->cantidad, 2);
            $diferencia = round($nuevoSubtotal - (float) $item->subtotal, 2);

            $item->forceFill(['peso_real_kg' => $pesoRealKg, 'subtotal' => $nuevoSubtotal])->save();
            $pedido->update([
                'subtotal' => round((float) $pedido->subtotal + $diferencia, 2),
                'total' => round(max(0, (float) $pedido->total + $diferencia), 2),
            ]);

            PesoDespacho::query()->create([
                'tenant_id' => $pedido->tenant_id, 'pedido_id' => $pedido->id, 'producto_id' => $item->producto_id,
                'peso_estimado_kg' => $pesoPedido, 'peso_real_kg' => $pesoRealKg, 'precio_kg' => round($precioKg, 2), 'diferencia' => $diferencia,
            ]);

            return $item;
        });
    }
}
