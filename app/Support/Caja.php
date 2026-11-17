<?php

namespace App\Support;

use App\Models\Gasto;
use App\Models\Pedido;
use App\Models\VentaPos;
use Carbon\Carbon;

/**
 * Resumen de caja de un dia. Efectivo = metodo "efectivo"; todo lo demas (Yape, Plin, tarjeta, transferencia)
 * cuenta como digital y no entra al arqueo. Las ventas cuentan los pedidos pagados creados ese dia.
 */
class Caja
{
    public static function resumen(Carbon $dia): array
    {
        $pedidos = Pedido::query()->where('pagado', true)
            ->whereDate('created_at', $dia)
            ->get(['metodo_pago', 'total']);

        $ventasPos = VentaPos::query()->vigentes()->whereDate('created_at', $dia)->get(['metodo_pago', 'total']);

        $gastos = Gasto::query()->whereDate('fecha', $dia)->get(['metodo', 'monto']);

        $porMetodo = [];
        foreach ($pedidos->concat($ventasPos) as $v) {
            $m = $v->metodo_pago ?: 'efectivo';
            $porMetodo[$m]['ventas'] = round(($porMetodo[$m]['ventas'] ?? 0) + (float) $v->total, 2);
            $porMetodo[$m]['operaciones'] = ($porMetodo[$m]['operaciones'] ?? 0) + 1;
        }
        foreach ($gastos as $g) {
            $m = $g->metodo ?: 'efectivo';
            $porMetodo[$m]['gastos'] = round(($porMetodo[$m]['gastos'] ?? 0) + (float) $g->monto, 2);
        }
        foreach ($porMetodo as $m => $v) {
            $porMetodo[$m] += ['ventas' => 0, 'gastos' => 0, 'operaciones' => 0];
            $porMetodo[$m]['neto'] = round($porMetodo[$m]['ventas'] - $porMetodo[$m]['gastos'], 2);
        }

        return [
            'por_metodo' => $porMetodo,
            'pedidos' => $pedidos->count() + $ventasPos->count(),
            'ventas_efectivo' => round(
                (float) $pedidos->where('metodo_pago', 'efectivo')->sum('total')
                + (float) $ventasPos->where('metodo_pago', 'efectivo')->sum('total'),
                2
            ),
            'ventas_digitales' => round(
                (float) $pedidos->where('metodo_pago', '!=', 'efectivo')->sum('total')
                + (float) $ventasPos->where('metodo_pago', '!=', 'efectivo')->sum('total'),
                2
            ),
            'gastos_efectivo' => round((float) $gastos->where('metodo', 'efectivo')->sum('monto'), 2),
            'gastos_digitales' => round((float) $gastos->where('metodo', '!=', 'efectivo')->sum('monto'), 2),
        ];
    }

    /** Estado de la caja de un dia (GEST-67): abierta, cerrada o sin_abrir. */
    public static function estado(?Carbon $dia = null): string
    {
        $dia ??= now();
        if (\App\Models\CierreCaja::query()->whereDate('fecha', $dia->toDateString())->exists()) {
            return 'cerrada';
        }

        return \App\Models\AperturaCaja::query()->whereDate('fecha', $dia->toDateString())->exists() ? 'abierta' : 'sin_abrir';
    }

    /** Efectivo que deberia haber en caja: monto inicial, mas ventas en efectivo, menos gastos en efectivo. */
    public static function esperado(float $montoInicial, array $resumen): float
    {
        return round($montoInicial + $resumen['ventas_efectivo'] - $resumen['gastos_efectivo'], 2);
    }
}
