<?php

namespace App\Support;

use App\Models\Cita;
use Carbon\Carbon;

/** Reglas de agenda compartidas por la agenda interna, la reserva publica y los recordatorios. */
class Citas
{
    /** Hay solape si una cita no cancelada del mismo dia ocupa parte del rango pedido. */
    public static function haySolape(int $tenantId, Carbon $inicio, Carbon $fin, ?int $excluirId = null): bool
    {
        return Cita::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->when($excluirId, fn ($q) => $q->where('id', '!=', $excluirId))
            ->where('estado', '!=', 'cancelada')
            ->whereBetween('inicio', [$inicio->copy()->startOfDay(), $inicio->copy()->endOfDay()])
            ->get()
            ->contains(fn (Cita $otra) => $otra->inicio < $fin && $otra->fin() > $inicio);
    }
}
