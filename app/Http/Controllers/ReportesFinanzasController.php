<?php

namespace App\Http\Controllers;

use App\Support\Finanzas;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Reportes financieros: tiempos de delivery, rentabilidad mensual y comparativo de ventas. */
class ReportesFinanzasController extends DashboardController
{
    private const TIPOS = ['delivery', 'rentabilidad', 'comparativo'];

    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $tipo = in_array($request->query('tipo'), self::TIPOS, true) ? $request->query('tipo') : 'comparativo';

        $desde = Carbon::parse($request->query('desde', now()->subDays(30)->toDateString()))->startOfDay();
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();

        $datos = match ($tipo) {
            'delivery' => Finanzas::tiemposDelivery($desde, $hasta),
            'rentabilidad' => Finanzas::rentabilidadMensual(12),
            default => Finanzas::comparativoMes(),
        };

        return view('admin.modulos.reportes_finanzas', [
            'titulo' => 'Reportes y finanzas',
            'modulo' => 'reportes',
            'tipo' => $tipo,
            'datos' => $datos,
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
        ]);
    }
}
