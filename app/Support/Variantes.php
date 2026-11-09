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
}
