<?php

namespace App\Http\Controllers;

use App\Models\AperturaCaja;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Registra la apertura de caja del dia con el monto inicial (GEST-67). */
class AperturaCajaController extends DashboardController
{
    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'monto_inicial' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        $dia = Carbon::parse($datos['fecha'])->toDateString();

        if (AperturaCaja::query()->whereDate('fecha', $dia)->exists()) {
            return back()->withErrors(['monto_inicial' => 'La caja de ese día ya fue abierta.']);
        }

        AperturaCaja::create([
            'tenant_id' => $request->user()->tenant_id,
            'fecha' => $dia,
            'monto_inicial' => round((float) $datos['monto_inicial'], 2),
            'abierto_por' => $request->user()->name,
            'notas' => $datos['notas'] ?? null,
        ]);

        return back()->with('ok', 'Caja abierta con S/ ' . number_format((float) $datos['monto_inicial'], 2) . '.');
    }
}
