<?php

namespace App\Support;

use App\Models\CampanaPublicidad;

/** Metricas de impacto de una campana: gasto real, progreso, CTR, CPC, CPA y ROAS. */
class Publicidad
{
    /** Estado segun activo y fechas: pausada, programada, activa o finalizada. */
    public static function estado(CampanaPublicidad $c): string
    {
        if (! $c->activo) {
            return 'pausada';
        }

        $hoy = now()->startOfDay();

        if ($c->fecha_inicio->gt($hoy)) {
            return 'programada';
        }

        if ($c->fecha_fin && $c->fecha_fin->lt($hoy)) {
            return 'finalizada';
        }

        return 'activa';
    }

    /** Requiere que la relacion "gastos" este cargada. */
    public static function metricas(CampanaPublicidad $c): array
    {
        $gastado = round((float) $c->gastos->sum('monto'), 2);
        $ventas = (float) ($c->ventas_atribuidas ?? 0);
        $alcance = (int) ($c->alcance ?? 0);
        $clics = (int) ($c->clics ?? 0);
        $conversiones = (int) ($c->conversiones ?? 0);

        return [
            'gastado' => $gastado,
            'progreso' => $c->inversion > 0 ? (int) min(100, round($gastado / (float) $c->inversion * 100)) : 0,
            'ctr' => $alcance > 0 ? round($clics / $alcance * 100, 2) : null,
            'cpc' => $clics > 0 && $gastado > 0 ? round($gastado / $clics, 2) : null,
            'cpa' => $conversiones > 0 && $gastado > 0 ? round($gastado / $conversiones, 2) : null,
            'roas' => $gastado > 0 ? round($ventas / $gastado, 2) : null,
        ];
    }
}
