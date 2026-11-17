<?php

namespace App\Support;

use App\Models\Insumo;
use App\Models\OrdenProduccion;
use App\Models\Receta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Reglas de produccion: costo de una receta y descuento de insumos al terminar un lote. */
class Produccion
{
    /** Costo de producir una unidad: suma de (cantidad x costo unitario) dividida entre el rendimiento. */
    public static function costoPorUnidad(Receta $receta): float
    {
        $receta->loadMissing('items.insumo');

        // La merma (GEST-72) encarece el insumo: para obtener 1 kg útil con 10 % de merma se compra 1.1 kg
        $total = $receta->items->sum(fn ($item) => (float) $item->cantidad * (1 + (float) ($item->merma_pct ?? 0) / 100) * (float) optional($item->insumo)->costo_unitario);

        return round($total / max(1, (int) $receta->rendimiento), 4);
    }

    /**
     * Marca un lote como terminado. Si tiene receta, descuenta los insumos del stock y guarda el costo.
     * Todo ocurre en una transaccion: si falta un insumo, no se descuenta nada.
     */
    public static function terminar(OrdenProduccion $orden): void
    {
        DB::transaction(function () use ($orden) {
            $lote = OrdenProduccion::query()->lockForUpdate()->findOrFail($orden->id);

            if ($lote->estado === 'terminada') {
                return;
            }

            if (! $lote->receta_id) {
                $lote->update(['estado' => 'terminada']);

                return;
            }

            $receta = Receta::query()->with('items')->findOrFail($lote->receta_id);
            $factor = $lote->cantidad / max(1, (int) $receta->rendimiento);

            $insumos = Insumo::query()
                ->whereIn('id', $receta->items->pluck('insumo_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $faltantes = [];
            $consumos = [];
            $costo = 0.0;

            foreach ($receta->items as $item) {
                // Se descuenta lo que realmente se usa, incluida la merma (GEST-72)
                $necesario = round((float) $item->cantidad * (1 + (float) ($item->merma_pct ?? 0) / 100) * $factor, 3);
                $insumo = $insumos->get($item->insumo_id);

                if (! $insumo || (float) $insumo->stock < $necesario) {
                    $faltantes[] = $insumo ? $insumo->nombre : 'insumo eliminado';

                    continue;
                }

                $insumo->decrement('stock', $necesario);
                $costo += $necesario * (float) $insumo->costo_unitario;
                $consumos[] = [
                    'tenant_id' => $lote->tenant_id, 'orden_produccion_id' => $lote->id, 'insumo_id' => $insumo->id,
                    'insumo' => $insumo->nombre, 'unidad' => $insumo->unidad, 'cantidad' => $necesario,
                    'costo' => round($necesario * (float) $insumo->costo_unitario, 2), 'created_at' => now(), 'updated_at' => now(),
                ];
            }

            if ($faltantes) {
                throw ValidationException::withMessages([
                    'estado' => 'Falta stock para terminar el lote: ' . implode(', ', $faltantes) . '.',
                ]);
            }

            // GEST-74: trazabilidad, qué insumos (y cuánto) consumió el lote
            if ($consumos && \Illuminate\Support\Facades\Schema::hasTable('lote_consumos')) {
                DB::table('lote_consumos')->insert($consumos);
            }

            $lote->update([
                'estado' => 'terminada',
                'costo_total' => round($costo, 2),
                'descontado_at' => now(),
            ]);
        });
    }
}
