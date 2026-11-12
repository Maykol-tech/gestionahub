<?php

namespace App\Support;

use App\Models\Gasto;
use App\Models\IngresoManual;
use App\Models\Pedido;
use App\Models\VentaPos;
use Carbon\Carbon;

/** Calculos financieros: ingresos (pedidos pagados + POS), gastos y utilidad por periodo. */
class Finanzas
{
    /** Ingresos cobrados entre dos fechas: pedidos pagados mas ventas del POS. */
    public static function ingresos(Carbon $desde, Carbon $hasta): float
    {
        $pedidos = (float) Pedido::query()->where('pagado', true)
            ->whereBetween('created_at', [$desde, $hasta])->sum('total');

        $pos = (float) VentaPos::query()->vigentes()->whereBetween('created_at', [$desde, $hasta])->sum('total');

        // Ingresos registrados a mano (GEST-66)
        $manuales = (float) IngresoManual::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->sum('monto');

        return round($pedidos + $pos + $manuales, 2);
    }

    public static function gastos(Carbon $desde, Carbon $hasta): float
    {
        return round((float) Gasto::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->sum('monto'), 2);
    }

    /** Ultimos $meses meses, del mas antiguo al actual. */
    public static function rentabilidadMensual(int $meses = 12): array
    {
        $filas = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $inicio = now()->subMonths($i)->startOfMonth();
            $fin = $inicio->copy()->endOfMonth();

            $ingresos = self::ingresos($inicio, $fin);
            $gastos = self::gastos($inicio, $fin);
            $utilidad = round($ingresos - $gastos, 2);

            $filas[] = [
                'mes' => $inicio->format('m/Y'),
                'ingresos' => $ingresos,
                'gastos' => $gastos,
                'utilidad' => $utilidad,
                'margen' => $ingresos > 0 ? round($utilidad / $ingresos * 100, 1) : null,
            ];
        }

        return $filas;
    }

    /** Mes actual frente al mes anterior, hasta el mismo dia. */
    public static function comparativoMes(): array
    {
        $hoy = now();
        $actualIni = $hoy->copy()->startOfMonth();
        $actualFin = $hoy->copy()->endOfDay();
        $anteriorIni = $hoy->copy()->subMonthNoOverflow()->startOfMonth();
        $anteriorFin = $hoy->copy()->subMonthNoOverflow()->endOfDay();

        $actual = self::ingresos($actualIni, $actualFin);
        $anterior = self::ingresos($anteriorIni, $anteriorFin);

        return [
            'actual' => $actual,
            'anterior' => $anterior,
            'variacion' => $anterior > 0 ? round(($actual - $anterior) / $anterior * 100, 1) : null,
            'periodo_actual' => $actualIni->format('d/m') . ' a ' . $actualFin->format('d/m'),
            'periodo_anterior' => $anteriorIni->format('d/m') . ' a ' . $anteriorFin->format('d/m'),
        ];
    }

    /** Tiempos de entrega: confirmado a asignado, asignado a entregado y total, por repartidor. */
    public static function tiemposDelivery(Carbon $desde, Carbon $hasta): array
    {
        $pedidos = Pedido::query()->whereNotNull('entregado_at')
            ->whereBetween('entregado_at', [$desde, $hasta])
            ->with('repartidor:id,nombre')->get();

        $minutos = fn ($a, $b) => $a && $b ? ($b->timestamp - $a->timestamp) / 60 : null;
        $promedio = fn ($lista) => ($v = array_filter($lista, fn ($x) => $x !== null)) ? round(array_sum($v) / count($v), 1) : null;

        $porRepartidor = $pedidos->groupBy('repartidor_id')->map(function ($grupo) use ($minutos, $promedio) {
            return [
                'nombre' => optional($grupo->first()->repartidor)->nombre ?? 'Sin repartidor',
                'entregas' => $grupo->count(),
                'asignado_a_entrega' => $promedio($grupo->map(fn ($p) => $minutos($p->asignado_at, $p->entregado_at))->all()),
                'confirmado_a_entrega' => $promedio($grupo->map(fn ($p) => $minutos($p->confirmado_at, $p->entregado_at))->all()),
            ];
        })->sortByDesc('entregas')->values()->all();

        return [
            'entregas' => $pedidos->count(),
            'asignado_a_entrega' => $promedio($pedidos->map(fn ($p) => $minutos($p->asignado_at, $p->entregado_at))->all()),
            'confirmado_a_entrega' => $promedio($pedidos->map(fn ($p) => $minutos($p->confirmado_at, $p->entregado_at))->all()),
            'por_repartidor' => $porRepartidor,
        ];
    }
}
