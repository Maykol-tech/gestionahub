<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\OrdenProduccion;
use App\Models\Receta;
use App\Models\RecetaInsumo;
use App\Support\Produccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Pagina de Produccion: insumos, recetas con costo, y lotes que descuentan stock al terminarse. */
class ProduccionController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $insumos = Insumo::query()->orderBy('nombre')->get()->map(fn (Insumo $i) => [
            'id' => $i->id,
            'nombre' => $i->nombre,
            'unidad' => $i->unidad,
            'stock' => (float) $i->stock,
            'stock_minimo' => (float) $i->stock_minimo,
            'costo_unitario' => (float) $i->costo_unitario,
            'bajo' => $i->bajoMinimo(),
        ])->values();

        $recetas = Receta::query()->with('items.insumo')->orderBy('nombre')->get()->map(fn (Receta $r) => [
            'id' => $r->id,
            'nombre' => $r->nombre,
            'rendimiento' => (int) $r->rendimiento,
            'notas' => $r->notas,
            'costo_unitario' => Produccion::costoPorUnidad($r),
            'items' => $r->items->map(fn (RecetaInsumo $it) => [
                'insumo_id' => $it->insumo_id,
                'insumo' => optional($it->insumo)->nombre ?? 'Insumo eliminado',
                'unidad' => optional($it->insumo)->unidad,
                'cantidad' => (float) $it->cantidad,
            ])->values(),
        ])->values();

        $lotes = OrdenProduccion::query()->orderByDesc('fecha')->orderByDesc('id')->limit(100)->get()
            ->map(fn (OrdenProduccion $o) => [
                'id' => $o->id,
                'titulo' => $o->titulo,
                'receta_id' => $o->receta_id,
                'cantidad' => (int) $o->cantidad,
                'fecha' => $o->fecha->format('d/m/Y'),
                'estado' => $o->estado,
                'notas' => $o->notas,
                'costo_total' => $o->costo_total !== null ? (float) $o->costo_total : null,
            ])->values();

        $mes = [now()->startOfMonth(), now()->endOfMonth()];

        return view('admin.modulos.produccion', [
            'titulo' => 'Producción',
            'modulo' => 'produccion',
            'insumos' => $insumos,
            'recetas' => $recetas,
            'lotes' => $lotes,
            'stats' => [
                'en_proceso' => OrdenProduccion::query()->whereIn('estado', ['pendiente', 'en_proceso'])->count(),
                'bajo_minimo' => $insumos->where('bajo', true)->count(),
                'unidades_mes' => (int) OrdenProduccion::query()->where('estado', 'terminada')
                    ->whereBetween('fecha', $mes)->sum('cantidad'),
                'costo_mes' => (float) OrdenProduccion::query()->where('estado', 'terminada')
                    ->whereBetween('fecha', $mes)->sum('costo_total'),
            ],
        ]);
    }

    // ---------- Insumos ----------

    public function guardarInsumo(Request $request): JsonResponse
    {
        $datos = $request->validate($this->reglasInsumo(), $this->mensajes());
        Insumo::query()->create($datos + ['tenant_id' => $request->user()->tenant_id]);

        return response()->json(['success' => true, 'message' => 'Insumo guardado.']);
    }

    public function actualizarInsumo(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate($this->reglasInsumo(), $this->mensajes());
        Insumo::query()->findOrFail($id)->update($datos);

        return response()->json(['success' => true, 'message' => 'Insumo actualizado.']);
    }

    public function eliminarInsumo(int $id): JsonResponse
    {
        $insumo = Insumo::query()->findOrFail($id);

        if (RecetaInsumo::query()->where('insumo_id', $insumo->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Este insumo está en una receta. Quítalo de la receta antes de eliminarlo.',
            ], 422);
        }

        $insumo->delete();

        return response()->json(['success' => true, 'message' => 'Insumo eliminado.']);
    }

    // ---------- Recetas ----------

    public function guardarReceta(Request $request): JsonResponse
    {
        return $this->grabarReceta($request, null);
    }

    public function actualizarReceta(Request $request, int $id): JsonResponse
    {
        return $this->grabarReceta($request, Receta::query()->findOrFail($id));
    }

    public function eliminarReceta(int $id): JsonResponse
    {
        $receta = Receta::query()->findOrFail($id);

        if (OrdenProduccion::query()->where('receta_id', $receta->id)->where('estado', '!=', 'terminada')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Hay lotes pendientes con esta receta. Termínalos o elimínalos primero.',
            ], 422);
        }

        $receta->delete();

        return response()->json(['success' => true, 'message' => 'Receta eliminada.']);
    }

    private function grabarReceta(Request $request, ?Receta $receta): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'rendimiento' => ['required', 'integer', 'between:1,100000'],
            'notas' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.insumo_id' => ['required', 'integer', 'distinct'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ], $this->mensajes() + ['items.required' => 'Agrega al menos un insumo a la receta.', 'items.*.insumo_id.distinct' => 'Un insumo no puede repetirse en la receta.', 'items.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.']);

        // Solo insumos del tenant actual (el scope los filtra).
        $ids = collect($datos['items'])->pluck('insumo_id')->unique();
        if (Insumo::query()->whereIn('id', $ids)->count() !== $ids->count()) {
            throw ValidationException::withMessages(['items' => 'Uno de los insumos ya no existe.']);
        }

        $nueva = $receta === null;

        DB::transaction(function () use ($request, &$receta, $datos) {
            $cabecera = [
                'nombre' => $datos['nombre'],
                'rendimiento' => (int) $datos['rendimiento'],
                'notas' => $datos['notas'] ?? null,
            ];

            if ($receta) {
                $receta->update($cabecera);
                $receta->items()->delete();
            } else {
                $receta = Receta::query()->create($cabecera + ['tenant_id' => $request->user()->tenant_id]);
            }

            foreach ($datos['items'] as $item) {
                $receta->items()->create([
                    'tenant_id' => $receta->tenant_id,
                    'insumo_id' => $item['insumo_id'],
                    'cantidad' => (float) $item['cantidad'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $nueva ? 'Receta creada.' : 'Receta actualizada.',
        ]);
    }

    // ---------- Lotes ----------

    public function guardarLote(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'receta_id' => ['required', 'integer'],
            'titulo' => ['nullable', 'string', 'max:120'],
            'cantidad' => ['required', 'integer', 'between:1,100000'],
            'fecha' => ['required', 'date'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], $this->mensajes());

        $receta = Receta::query()->findOrFail($datos['receta_id']);

        OrdenProduccion::query()->create([
            'tenant_id' => $request->user()->tenant_id,
            'titulo' => filled($datos['titulo'] ?? null) ? $datos['titulo'] : $receta->nombre,
            'receta_id' => $receta->id,
            'cantidad' => (int) $datos['cantidad'],
            'fecha' => $datos['fecha'],
            'estado' => 'pendiente',
            'notas' => $datos['notas'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Lote programado.']);
    }

    public function estadoLote(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(OrdenProduccion::ESTADOS))],
        ]);

        $lote = OrdenProduccion::query()->findOrFail($id);

        if ($lote->estado === 'terminada') {
            return response()->json(['success' => false, 'message' => 'Un lote terminado ya no se puede cambiar.'], 422);
        }

        if ($datos['estado'] === 'terminada') {
            Produccion::terminar($lote);
        } else {
            $lote->update(['estado' => $datos['estado']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lote marcado como ' . strtolower(OrdenProduccion::ESTADOS[$datos['estado']]) . '.',
        ]);
    }

    public function eliminarLote(int $id): JsonResponse
    {
        $lote = OrdenProduccion::query()->findOrFail($id);

        if ($lote->estado === 'terminada') {
            return response()->json([
                'success' => false,
                'message' => 'Un lote terminado no se puede eliminar porque ya descontó sus insumos.',
            ], 422);
        }

        $lote->delete();

        return response()->json(['success' => true, 'message' => 'Lote eliminado.']);
    }

    // ---------- Reglas ----------

    private function reglasInsumo(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'unidad' => ['required', Rule::in(['und', 'kg', 'g', 'l', 'ml', 'paquete', 'caja'])],
            'stock' => ['required', 'numeric', 'min:0', 'max:999999'],
            'stock_minimo' => ['required', 'numeric', 'min:0', 'max:999999'],
            'costo_unitario' => ['required', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'unidad.in' => 'Elige una unidad válida.',
            'rendimiento.between' => 'El rendimiento debe estar entre 1 y 100000.',
        ];
    }
}
