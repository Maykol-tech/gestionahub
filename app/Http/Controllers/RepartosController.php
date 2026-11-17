<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Repartidor;
use App\Models\ZonaDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Repartos: pedidos en camino, repartidores en el mapa y zonas de delivery (GEST-23, 24 y 25). */
class RepartosController extends DashboardController
{
    private function tenant(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function repartidores()
    {
        $enRuta = Pedido::query()->where('estado', 'en_camino')->whereNotNull('repartidor_id')
            ->selectRaw('repartidor_id, count(*) as n')->groupBy('repartidor_id')->pluck('n', 'repartidor_id');

        return Repartidor::query()->where('tenant_id', $this->tenant())->where('activo', true)->orderBy('nombre')->get()
            ->map(fn (Repartidor $r) => [
                'id' => $r->id, 'nombre' => $r->nombre, 'telefono' => $r->telefono, 'vehiculo' => $r->vehiculo,
                'disponible' => (bool) $r->disponible, 'en_ruta' => (int) ($enRuta[$r->id] ?? 0),
                'lat_actual' => $r->lat_actual, 'lng_actual' => $r->lng_actual, 'updated_location_at' => $r->updated_location_at,
            ])->values();
    }

    public function repartos()
    {
        $this->compartirLayout();
        $tenantId = $this->tenant();

        $repartidores = $this->repartidores();
        $nombres = $repartidores->pluck('nombre', 'id');
        $enCamino = Pedido::query()->where('tenant_id', $tenantId)->where('estado', 'en_camino')->with('cliente')->orderBy('updated_at')->get()
            ->map(fn (Pedido $p) => [
                'numero' => $p->numero,
                'cliente' => $p->cliente->nombre ?? ($p->cliente_nombre ?: 'Cliente'),
                'direccion' => $p->direccion_entrega,
                'distrito' => $p->cliente->distrito ?? null,
                'repartidor' => $p->repartidor_id ? ($nombres[$p->repartidor_id] ?? null) : null,
                'salida' => $p->updated_at?->diffForHumans(),
                'total' => (float) $p->total,
                'pagado' => (bool) $p->pagado,
            ]);

        return view('admin.modulos.repartos', [
            'titulo' => 'Repartos',
            'modulo' => 'repartos',
            'enCamino' => $enCamino,
            'porCobrarRuta' => $enCamino->where('pagado', false)->sum('total'),
            'entregadosHoy' => Pedido::query()->where('tenant_id', $tenantId)->where('estado', 'entregado')->whereDate('updated_at', today())->count(),
            'repartidores' => $repartidores,
            'hayRepartidores' => $repartidores->isNotEmpty(),
            'zonas' => ZonaDelivery::query()->where('tenant_id', $tenantId)->orderBy('orden')->orderBy('id')->get(),
        ]);
    }

    /** Ubicaciones actuales de los repartidores (para refrescar el mapa). */
    public function mapa(): JsonResponse
    {
        return response()->json(['data' => $this->repartidores()->filter(fn ($r) => $r['lat_actual'] !== null)->values()]);
    }

    // ---------- Zonas de delivery (GEST-25) ----------

    private function datosZona(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'costo' => ['required', 'numeric', 'min:0', 'max:9999'],
            'tiempo_min' => ['nullable', 'integer', 'min:1', 'max:600'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [], ['tiempo_min' => 'tiempo de entrega']);
    }

    public function guardarZona(Request $request): RedirectResponse
    {
        $datos = $this->datosZona($request);
        ZonaDelivery::query()->create($datos + ['tenant_id' => $this->tenant(), 'color' => $datos['color'] ?? '#2B6EF6', 'activo' => true,
            'orden' => (int) ZonaDelivery::query()->where('tenant_id', $this->tenant())->max('orden') + 1]);

        return redirect()->to(route('admin.repartos') . '#zonas')->with('ok', 'Zona "' . $datos['nombre'] . '" agregada.');
    }

    public function actualizarZona(Request $request, int $id): RedirectResponse
    {
        $zona = ZonaDelivery::query()->where('tenant_id', $this->tenant())->findOrFail($id);
        if ($request->has('alternar')) {
            $zona->update(['activo' => ! $zona->activo]);

            return redirect()->to(route('admin.repartos') . '#zonas')->with('ok', $zona->activo ? 'Zona activada.' : 'Zona pausada.');
        }
        $zona->update($this->datosZona($request));

        return redirect()->to(route('admin.repartos') . '#zonas')->with('ok', 'Zona actualizada.');
    }

    public function eliminarZona(int $id): RedirectResponse
    {
        ZonaDelivery::query()->where('tenant_id', $this->tenant())->findOrFail($id)->delete();

        return redirect()->to(route('admin.repartos') . '#zonas')->with('ok', 'Zona eliminada.');
    }

    // ---------- Repartidores (atajos; el alta completa está en Repartidores) ----------

    public function guardarRepartidor(): RedirectResponse
    {
        return redirect()->route('admin.repartidores.crud.index');
    }

    public function actualizarRepartidor(): RedirectResponse
    {
        return redirect()->route('admin.repartidores.crud.index');
    }

    public function estadoRepartidor(int $id): RedirectResponse
    {
        $r = Repartidor::query()->where('tenant_id', $this->tenant())->findOrFail($id);
        $r->update(['disponible' => ! $r->disponible]);

        return back()->with('ok', $r->nombre . ($r->disponible ? ' está disponible.' : ' quedó en pausa.'));
    }

    public function nuevoEnlace(): RedirectResponse
    {
        return redirect()->route('admin.repartidores.crud.index');
    }
}
