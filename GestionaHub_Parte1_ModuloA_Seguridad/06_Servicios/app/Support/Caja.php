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

        return [
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

    /** Efectivo que deberia haber en caja: monto inicial, mas ventas en efectivo, menos gastos en efectivo. */
    public static function esperado(float $montoInicial, array $resumen): float
    {
        return round($montoInicial + $resumen['ventas_efectivo'] - $resumen['gastos_efectivo'], 2);
    }
}
