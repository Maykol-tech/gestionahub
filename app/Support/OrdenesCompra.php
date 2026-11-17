<?php

namespace App\Support;

use App\Models\Insumo;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

/** Reglas de las ordenes de compra: al recibirla, el stock de cada insumo vinculado sube y su costo se actualiza. */
class OrdenesCompra
{
    /** Transiciones permitidas por estado. */
    public const TRANSICIONES = [
        'borrador' => ['enviada', 'cancelada'],
        'enviada' => ['recibida', 'cancelada'],
        'recibida' => [],
        'cancelada' => [],
    ];

    public static function recibir(OrdenCompra $orden): void
    {
        DB::transaction(function () use ($orden) {
            $lote = OrdenCompra::query()->with('items')->lockForUpdate()->findOrFail($orden->id);

            if ($lote->estado === 'recibida') {
                return;
            }

            foreach ($lote->items as $item) {
                if (! $item->insumo_id) {
                    continue;
                }

                $insumo = Insumo::query()->lockForUpdate()->find($item->insumo_id);

                if (! $insumo) {
                    continue;
                }

                // Costo promedio ponderado: (stock actual x costo actual + cantidad recibida x precio) / stock nuevo
                $stockActual = max(0, (float) $insumo->stock);
                $cantidad = (float) $item->cantidad;
                $nuevoStock = $stockActual + $cantidad;
                $promedio = $nuevoStock > 0
                    ? round(($stockActual * (float) $insumo->costo_unitario + $cantidad * (float) $item->precio_unitario) / $nuevoStock, 4)
                    : (float) $item->precio_unitario;
                $insumo->update(['stock' => $nuevoStock, 'costo_unitario' => $promedio]);
            }

            // Compra a credito: queda como cuenta por pagar con fecha de vencimiento
            $dias = optional($lote->proveedor)->diasCredito() ?? 0;
            $lote->update(['estado' => 'recibida', 'recibida_at' => now()] + ($dias > 0 ? ['vence_at' => now()->addDays($dias)->toDateString()] : []));
        });
    }
}
