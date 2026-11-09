<?php

namespace App\Http\Controllers;

use App\Models\AperturaCaja;
use App\Models\CierreCaja;
use App\Support\Caja;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Cierre de caja y arqueo: compara el efectivo esperado del dia con el efectivo contado. */
class CajaCierreController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $dia = $this->diaSolicitado($request->query('fecha'));
        $cierre = CierreCaja::query()->whereDate('fecha', $dia)->first();
        $ultimo = CierreCaja::query()->orderByDesc('fecha')->first();
        $apertura = AperturaCaja::query()->whereDate('fecha', $dia)->first();

        return view('admin.modulos.caja_cierre', [
            'titulo' => 'Cierre de caja',
            'modulo' => 'caja',
            'dia' => $dia->toDateString(),
            'dia_label' => $dia->format('d/m/Y'),
            'resumen' => Caja::resumen($dia),
            'cierre' => $cierre ? $this->presentar($cierre) : null,
            'apertura' => $apertura ? ['monto_inicial' => (float) $apertura->monto_inicial, 'abierto_por' => $apertura->abierto_por ?? '—'] : null,
            // Si la caja se abrio con registro, ese monto manda; si no, se sugiere el contado del ultimo cierre
            'sugerido' => $apertura ? (float) $apertura->monto_inicial : ($ultimo ? (float) $ultimo->contado : 0),
            'historial' => CierreCaja::query()->orderByDesc('fecha')->limit(30)->get()
                ->map(fn (CierreCaja $c) => $this->presentar($c))->values(),
        ]);
    }

    public function cerrar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'monto_inicial' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'contado' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'contado.min' => 'El efectivo contado no puede ser negativo.',
        ]);

        $dia = Carbon::parse($datos['fecha'])->startOfDay();

        if (CierreCaja::query()->whereDate('fecha', $dia)->exists()) {
            return response()->json(['success' => false, 'message' => 'Esta caja ya fue cerrada.'], 422);
        }

        $resumen = Caja::resumen($dia);
        // Si la caja se abrio con registro, el monto inicial es el registrado (no el que llegue en el formulario)
        $apertura = AperturaCaja::query()->whereDate('fecha', $dia)->first();
        $montoInicial = $apertura ? (float) $apertura->monto_inicial : (float) $datos['monto_inicial'];
        $esperado = Caja::esperado($montoInicial, $resumen);
        $contado = round((float) $datos['contado'], 2);
        $diferencia = round($contado - $esperado, 2);

        CierreCaja::query()->create([
            'tenant_id' => $request->user()->tenant_id,
            'fecha' => $dia->toDateString(),
            'monto_inicial' => $montoInicial,
            'ventas_efectivo' => $resumen['ventas_efectivo'],
            'ventas_digitales' => $resumen['ventas_digitales'],
            'gastos_efectivo' => $resumen['gastos_efectivo'],
            'gastos_digitales' => $resumen['gastos_digitales'],
            'esperado' => $esperado,
            'contado' => $contado,
            'diferencia' => $diferencia,
            'cerrado_por' => $request->user()->name,
            'notas' => $datos['notas'] ?? null,
        ]);

        $mensaje = match (true) {
            $diferencia === 0.0 => 'Caja cerrada. Cuadra exacto.',
            $diferencia > 0 => 'Caja cerrada. Sobran S/ ' . number_format($diferencia, 2) . '.',
            default => 'Caja cerrada. Faltan S/ ' . number_format(abs($diferencia), 2) . '.',
        };

        return response()->json(['success' => true, 'message' => $mensaje]);
    }

    private function diaSolicitado(?string $fecha): Carbon
    {
        try {
            return $fecha ? Carbon::parse($fecha)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function presentar(CierreCaja $c): array
    {
        return [
            'id' => $c->id,
            'fecha' => $c->fecha->format('d/m/Y'),
            'monto_inicial' => (float) $c->monto_inicial,
            'ventas_efectivo' => (float) $c->ventas_efectivo,
            'ventas_digitales' => (float) $c->ventas_digitales,
            'gastos_efectivo' => (float) $c->gastos_efectivo,
            'gastos_digitales' => (float) $c->gastos_digitales,
            'esperado' => (float) $c->esperado,
            'contado' => (float) $c->contado,
            'diferencia' => (float) $c->diferencia,
            'cerrado_por' => $c->cerrado_por ?? '—',
            'notas' => $c->notas,
        ];
    }
}
