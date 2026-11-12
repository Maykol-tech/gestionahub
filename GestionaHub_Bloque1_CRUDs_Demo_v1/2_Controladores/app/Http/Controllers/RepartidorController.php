<?php

namespace App\Http\Controllers;

use App\Http\Requests\RepartidorRequest;
use App\Models\Pedido;
use App\Models\Repartidor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CRUD de repartidores (GEST-23 a 25): listado paginado, alta, edicion, activar/desactivar,
 * eliminar (borrado logico; no si tiene pedidos en curso) y asignar un pedido abierto.
 * Todas las consultas pasan por TenantScope: un negocio nunca ve ni cambia repartidores de otro.
 */
class RepartidorController extends DashboardController
{
    public const POR_PAGINA = 8;

    /** Pedidos que todavia se pueden asignar a un repartidor. */
    public const ESTADOS_ASIGNABLES = ['pendiente', 'confirmado', 'preparando', 'listo'];

    public function pagina(Request $request)
    {
        $this->compartirLayout();
        $tenantId = $this->tenantId();
        $q = trim((string) $request->query('q', ''));

        $repartidores = Repartidor::query()->where('tenant_id', $tenantId)
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
                ->where('nombre', 'like', "%{$q}%")
                ->orWhere('telefono', 'like', "%{$q}%")
                ->orWhere('placa', 'like', "%{$q}%")))
            ->withCount(['pedidos as en_ruta' => fn ($b) => $b->whereNotIn('estado', ['entregado', 'cancelado'])])
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $base = fn () => Repartidor::query()->where('tenant_id', $tenantId);

        return view('admin.modulos.repartidores', [
            'modulo' => 'repartidores',
            'titulo' => 'Repartidores',
            'icono' => 'truck',
            'grupo' => 'Control',
            'repartidores' => $repartidores,
            'q' => $q,
            'resumen' => [
                'total' => $base()->count(),
                'activos' => $base()->where('activo', true)->count(),
                'disponibles' => $base()->where('activo', true)->where('disponible', true)->count(),
                'en_ruta' => Pedido::query()->whereNotNull('repartidor_id')->whereNotIn('estado', ['entregado', 'cancelado'])->count(),
            ],
            'sinAsignar' => Pedido::query()->whereNull('repartidor_id')
                ->whereIn('estado', self::ESTADOS_ASIGNABLES)->where('tipo', 'delivery')
                ->orderBy('created_at')->limit(50)
                ->get(['id', 'numero', 'total', 'estado', 'direccion_entrega'])
                ->map(fn (Pedido $p) => [
                    'id' => $p->id, 'numero' => $p->numero, 'total' => (float) $p->total,
                    'estado' => $p->estado, 'direccion' => $p->direccion_entrega,
                ])->values()->all(),
        ]);
    }

    public function store(RepartidorRequest $request): JsonResponse
    {
        $repartidor = Repartidor::query()->create(
            $request->only(['nombre', 'telefono', 'dni', 'vehiculo', 'placa'])
            + ['tenant_id' => (int) $request->user()->tenant_id, 'activo' => true, 'disponible' => true]
        );

        return response()->json([
            'success' => true,
            'message' => "{$repartidor->nombre} se agregó al equipo.",
            'data' => $this->formatear($repartidor),
        ], 201);
    }

    public function update(RepartidorRequest $request, int $id): JsonResponse
    {
        $repartidor = $this->buscar($id);
        $repartidor->update($request->only(['nombre', 'telefono', 'dni', 'vehiculo', 'placa']));

        return response()->json([
            'success' => true,
            'message' => 'Datos de ' . $repartidor->nombre . ' actualizados.',
            'data' => $this->formatear($repartidor->fresh()),
        ]);
    }

    /** Activa o desactiva. Un repartidor inactivo no aparece como disponible ni recibe asignaciones. */
    public function estado(int $id): JsonResponse
    {
        $repartidor = $this->buscar($id);
        $activar = ! $repartidor->activo;
        $repartidor->update(['activo' => $activar, 'disponible' => $activar]);

        return response()->json([
            'success' => true,
            'message' => $repartidor->nombre . ($activar ? ' está activo de nuevo.' : ' quedó inactivo.'),
            'data' => $this->formatear($repartidor->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $repartidor = $this->buscar($id);

        // Borrado logico: el historial de entregas se conserva. Solo se bloquea si tiene pedidos abiertos.
        if ($repartidor->pedidos()->whereNotIn('estado', ['entregado', 'cancelado'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tiene pedidos en curso. Espera a que se entreguen o desactívalo en su lugar.',
            ], 422);
        }

        $repartidor->delete();

        return response()->json(['success' => true, 'message' => 'Repartidor eliminado.']);
    }

    public function asignar(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'pedido_id' => ['required', 'integer', Rule::exists('pedidos', 'id')->where('tenant_id', (int) $request->user()->tenant_id)],
        ], [
            'pedido_id.required' => 'Elige un pedido.',
            'pedido_id.exists' => 'Ese pedido no existe.',
        ]);

        $repartidor = $this->buscar($id);
        if (! $repartidor->activo) {
            return response()->json(['success' => false, 'message' => 'Activa a este repartidor antes de asignarle pedidos.'], 422);
        }

        $pedido = DB::transaction(function () use ($datos, $repartidor) {
            $pedido = Pedido::query()->lockForUpdate()->find($datos['pedido_id']);
            if (! $pedido || $pedido->repartidor_id !== null || ! in_array($pedido->estado, self::ESTADOS_ASIGNABLES, true)) {
                return null;
            }

            $pedido->forceFill(['repartidor_id' => $repartidor->id, 'asignado_at' => now()])->save();

            return $pedido;
        });

        if (! $pedido) {
            return response()->json(['success' => false, 'message' => 'Ese pedido ya tiene repartidor o ya no está pendiente.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Pedido {$pedido->numero} asignado a {$repartidor->nombre}.",
        ]);
    }

    private function buscar(int $id): Repartidor
    {
        return Repartidor::query()->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function formatear(Repartidor $r): array
    {
        return [
            'id' => $r->id,
            'nombre' => $r->nombre,
            'telefono' => $r->telefono,
            'dni' => $r->dni,
            'vehiculo' => $r->vehiculo,
            'vehiculo_texto' => Repartidor::VEHICULOS[$r->vehiculo] ?? ucfirst((string) $r->vehiculo),
            'placa' => $r->placa,
            'activo' => (bool) $r->activo,
            'disponible' => (bool) $r->disponible,
        ];
    }
}
