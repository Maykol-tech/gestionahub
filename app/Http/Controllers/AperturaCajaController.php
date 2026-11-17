<?php

namespace App\Http\Controllers;

use App\Models\AperturaCaja;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Registra la apertura de caja del dia con el monto inicial (GEST-67). */
class AperturaCajaController extends DashboardController
{
    public function guardar(Request $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'monto_inicial' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        $dia = Carbon::parse($datos['fecha'])->toDateString();

        if (AperturaCaja::query()->whereDate('fecha', $dia)->exists()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'La caja de ese día ya fue abierta.', 'errors' => ['monto_inicial' => ['La caja de ese día ya fue abierta.']]], 422);
            }

            return back()->withErrors(['monto_inicial' => 'La caja de ese día ya fue abierta.']);
        }

        AperturaCaja::create([
            'tenant_id' => $request->user()->tenant_id,
            'fecha' => $dia,
            'monto_inicial' => round((float) $datos['monto_inicial'], 2),
            'abierto_por' => $request->user()->name,
            'notas' => $datos['notas'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Caja abierta con S/ ' . number_format((float) $datos['monto_inicial'], 2) . '.']);
        }

        return back()->with('ok', 'Caja abierta con S/ ' . number_format((float) $datos['monto_inicial'], 2) . '.');
    }
}
