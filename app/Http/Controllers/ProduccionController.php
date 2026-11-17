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
            'proveedor_id' => $i->proveedor_id,
            'bajo' => $i->bajoMinimo(),
        ])->values();

        $recetas = Receta::query()->with('items.insumo')->orderBy('nombre')->get()->map(fn (Receta $r) => [
            'id' => $r->id,
            'nombre' => $r->nombre,
            'rendimiento' => (int) $r->rendimiento,
            'notas' => $r->notas,
            'producto_id' => $r->producto_id,
            'pasos' => $r->pasos,
            'alergenos' => $r->alergenos,
            'imagen_url' => $r->imagen_url,
            'costo_unitario' => Produccion::costoPorUnidad($r),
            'items' => $r->items->map(fn (RecetaInsumo $it) => [
                'insumo_id' => $it->insumo_id,
                'insumo' => optional($it->insumo)->nombre ?? 'Insumo eliminado',
                'unidad' => optional($it->insumo)->unidad,
                'cantidad' => (float) $it->cantidad,
                'merma_pct' => (float) ($it->merma_pct ?? 0),
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
                'codigo' => $o->codigo ?? 'L-' . str_pad((string) $o->id, 5, '0', STR_PAD_LEFT),
                'vence' => $o->vence_at ? \Carbon\Carbon::parse($o->vence_at)->format('d/m/Y') : null,
                'por_vencer' => $o->vence_at && \Carbon\Carbon::parse($o->vence_at)->lte(now()->addDays(3)),
                'vencido' => $o->vence_at && \Carbon\Carbon::parse($o->vence_at)->lt(today()),
                'responsable' => $o->responsable,
            ])->values();

        $ajustes = \App\Support\AjustesNegocio::leer($this->tenantId());
        $precios = \App\Models\Producto::query()->pluck('precio', 'id');

        $mes = [now()->startOfMonth(), now()->endOfMonth()];

        return view('admin.modulos.produccion', [
            'titulo' => 'Producción',
            'modulo' => 'produccion',
            'insumos' => $insumos,
            'recetas' => $recetas,
            'margenes' => ['objetivo' => (float) ($ajustes['margen_objetivo'] ?? 60), 'minimo' => (float) ($ajustes['margen_minimo'] ?? 30)],
            'proveedores' => \App\Models\Proveedor::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'productos' => \App\Models\Producto::query()->orderBy('nombre')->get(['id', 'nombre', 'precio']),
            'lotes' => $lotes,
            'stats' => [
                'en_proceso' => OrdenProduccion::query()->whereIn('estado', ['pendiente', 'en_proceso'])->count(),
                'bajo_minimo' => $insumos->where('bajo', true)->count(),
                'por_vencer' => OrdenProduccion::query()->where('estado', 'terminada')->whereNotNull('vence_at')
                    ->whereBetween('vence_at', [today(), today()->addDays(3)])->count(),
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
            'producto_id' => ['nullable', 'integer'],
            'pasos' => ['nullable', 'string', 'max:4000'],
            'alergenos' => ['nullable', 'string', 'max:255'],
            'imagen_url' => ['nullable', 'url', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.insumo_id' => ['required', 'integer', 'distinct'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.merma_pct' => ['nullable', 'numeric', 'min:0', 'max:90'],
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
                'producto_id' => $datos['producto_id'] ?? null,
                'pasos' => $datos['pasos'] ?? null,
                'alergenos' => $datos['alergenos'] ?? null,
                'imagen_url' => $datos['imagen_url'] ?? null,
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
                    'merma_pct' => (float) ($item['merma_pct'] ?? 0),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $nueva ? 'Receta creada.' : 'Receta actualizada.',
        ]);
    }

    /** GEST-74: trazabilidad de un lote (insumos consumidos). */
    public function trazabilidad(int $id): JsonResponse
    {
        $lote = OrdenProduccion::query()->findOrFail($id);
        $consumos = \Illuminate\Support\Facades\Schema::hasTable('lote_consumos')
            ? \Illuminate\Support\Facades\DB::table('lote_consumos')->where('orden_produccion_id', $lote->id)->get(['insumo', 'unidad', 'cantidad', 'costo'])
            : collect();

        return response()->json(['success' => true, 'consumos' => $consumos]);
    }

    /** GEST-73: margen objetivo y mínimo del negocio. */
    public function guardarMargenes(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'objetivo' => ['required', 'numeric', 'between:1,95'],
            'minimo' => ['required', 'numeric', 'between:0,95', 'lte:objetivo'],
        ], ['minimo.lte' => 'El mínimo no puede ser mayor que el objetivo.']);
        \App\Support\AjustesNegocio::guardar($this->tenantId(), ['margen_objetivo' => (float) $datos['objetivo'], 'margen_minimo' => (float) $datos['minimo']]);

        return response()->json(['success' => true, 'message' => 'Márgenes guardados.']);
    }

    /** GEST-72: ficha técnica imprimible de la receta. */
    public function ficha(int $id)
    {
        $receta = Receta::query()->with('items.insumo')->findOrFail($id);
        $producto = $receta->producto_id ? \App\Models\Producto::query()->find($receta->producto_id) : null;

        return view('admin.modulos.ficha_tecnica', [
            'r' => $receta,
            'producto' => $producto,
            'costo' => Produccion::costoPorUnidad($receta),
            'negocio' => \App\Models\Tenant::find(auth()->user()->tenant_id),
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
            'vence_at' => ['nullable', 'date', 'after_or_equal:fecha'],
            'responsable' => ['nullable', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], $this->mensajes() + ['vence_at.after_or_equal' => 'El vencimiento no puede ser antes de la fecha de producción.']);

        $receta = Receta::query()->findOrFail($datos['receta_id']);

        $creado = OrdenProduccion::query()->create([
            'tenant_id' => $request->user()->tenant_id,
            'titulo' => filled($datos['titulo'] ?? null) ? $datos['titulo'] : $receta->nombre,
            'receta_id' => $receta->id,
            'cantidad' => (int) $datos['cantidad'],
            'fecha' => $datos['fecha'],
            'estado' => 'pendiente',
            'notas' => $datos['notas'] ?? null,
            'vence_at' => $datos['vence_at'] ?? null,
            'responsable' => filled($datos['responsable'] ?? null) ? $datos['responsable'] : $request->user()->name,
        ]);
        $creado->update(['codigo' => 'L-' . \Carbon\Carbon::parse($datos['fecha'])->format('ymd') . '-' . str_pad((string) $creado->id, 4, '0', STR_PAD_LEFT)]);

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
            'proveedor_id' => ['nullable', 'integer'],
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
