<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Gasto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** GEST-66: registro de egresos (gastos) del negocio. */
class GastoController extends Controller
{
    public function guardar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'concepto' => ['required', 'string', 'max:150'],
            'categoria' => ['required', Rule::in(array_keys(Gasto::CATEGORIAS))],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'metodo' => ['required', Rule::in(array_keys(Gasto::METODOS))],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'comprobante' => ['nullable', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a cero.',
            'fecha.before_or_equal' => 'No puedes registrar gastos con fecha futura.',
        ]);

        if ($datos['metodo'] === 'efectivo' && CierreCaja::query()->whereDate('fecha', $datos['fecha'])->exists()) {
            return response()->json(['message' => 'La caja de ese día ya se cerró. Registra el gasto con otra fecha o método.', 'errors' => ['fecha' => ['La caja de ese día ya está cerrada.']]], 422);
        }

        $gasto = Gasto::create($datos + [
            'tenant_id' => $request->user()->tenant_id,
            'monto' => round((float) $datos['monto'], 2),
            'registrado_por' => $request->user()->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Gasto registrado.', 'gasto' => self::presentar($gasto)], 201);
    }

    public function eliminar(int $id): JsonResponse
    {
        $gasto = Gasto::query()->findOrFail($id);
        if ($gasto->metodo === 'efectivo' && CierreCaja::query()->whereDate('fecha', $gasto->fecha)->exists()) {
            return response()->json(['message' => 'No se puede eliminar: la caja de ese día ya está cerrada.'], 422);
        }
        $gasto->delete();

        return response()->json(['success' => true, 'message' => 'Gasto eliminado.']);
    }

    public static function presentar(Gasto $g): array
    {
        return [
            'id' => $g->id,
            'hora' => $g->created_at?->format('H:i') ?? '—',
            'concepto' => $g->concepto,
            'detalle' => (Gasto::CATEGORIAS[$g->categoria ?? 'otros'] ?? 'Otros') . ($g->comprobante ? ' · ' . $g->comprobante : ''),
            'metodo' => $g->metodo,
            'metodo_texto' => Gasto::METODOS[$g->metodo] ?? ucfirst((string) $g->metodo),
            'tipo' => 'egreso',
            'monto' => (float) $g->monto,
            'gasto_id' => $g->id,
        ];
    }
}
