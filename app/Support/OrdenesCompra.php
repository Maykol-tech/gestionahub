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

                $insumo->increment('stock', (float) $item->cantidad);
                $insumo->update(['costo_unitario' => (float) $item->precio_unitario]);
            }

            $lote->update(['estado' => 'recibida', 'recibida_at' => now()]);
        });
    }
}
