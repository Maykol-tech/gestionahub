<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Repartidor;
use App\Models\RepartidorUbicacion;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * App del repartidor (GEST-24). Se abre en el celular con su enlace privado /r/{token}, sin login.
 * Ve sus pedidos asignados, llama o escribe al cliente, abre la ruta en el mapa,
 * marca "Salí a entregar" y "Entregado", y comparte su ubicación para el mapa de Repartos.
 */
class RepartidorAppController extends Controller
{
    /** Estados que el repartidor ve en su lista. */
    private const VISIBLES = ['pendiente', 'confirmado', 'preparando', 'listo', 'en_camino'];

    private function repartidor(string $token): Repartidor
    {
        $r = Repartidor::withoutGlobalScopes()->whereNull('deleted_at')->where('token', $token)->first();
        abort_if(! $r || ! $r->activo, 404);

        return $r;
    }

    private function pedidosDe(Repartidor $r)
    {
        return Pedido::withoutGlobalScopes()->where('tenant_id', $r->tenant_id)->where('repartidor_id', $r->id)
            ->where(fn ($q) => $q->whereIn('estado', self::VISIBLES)
                ->orWhere(fn ($w) => $w->where('estado', 'entregado')->where('entregado_at', '>=', today())))
            ->with(['cliente' => fn ($q) => $q->withoutGlobalScopes(), 'items' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderByRaw("case estado when 'en_camino' then 0 when 'listo' then 1 when 'entregado' then 3 else 2 end")
            ->orderBy('asignado_at')->get()
            ->map(fn (Pedido $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'estado' => $p->estado,
                'cliente' => $p->cliente->nombre ?? ($p->cliente_nombre ?: 'Cliente'),
                'telefono' => preg_replace('/\D/', '', (string) ($p->cliente_telefono ?: ($p->cliente->telefono ?? ''))),
                'direccion' => $p->direccion_entrega,
                'referencia' => $p->cliente->referencia ?? null,
                'lat' => $p->lat_entrega !== null ? (float) $p->lat_entrega : null,
                'lng' => $p->lng_entrega !== null ? (float) $p->lng_entrega : null,
                'total' => (float) $p->total,
                'pagado' => (bool) $p->pagado,
                'metodo_pago' => $p->metodo_pago,
                'notas' => $p->notas,
                'items' => $p->items->map(fn ($i) => ['nombre' => $i->nombre . ($i->variante_nombre && ! str_contains((string) $i->nombre, (string) $i->variante_nombre) ? ' · ' . $i->variante_nombre : ''), 'cantidad' => (int) $i->cantidad])->values(),
                'hora' => $p->entregado_at?->format('H:i'),
            ])->values();
    }

    public function app(string $token)
    {
        $r = $this->repartidor($token);
        $negocio = Tenant::query()->find($r->tenant_id);

        return view('publico.repartidor_app', [
            'repartidor' => $r,
            'negocio' => $negocio,
            'pedidos' => $this->pedidosDe($r),
            'token' => $token,
        ]);
    }

    public function pedidos(string $token): JsonResponse
    {
        $r = $this->repartidor($token);

        return response()->json(['data' => $this->pedidosDe($r), 'disponible' => (bool) $r->disponible]);
    }

    /** El celular manda su ubicación (cada ~30 s mientras reparte). */
    public function ubicacion(Request $request, string $token): JsonResponse
    {
        $r = $this->repartidor($token);
        $datos = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'precision' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $r->forceFill(['lat_actual' => $datos['lat'], 'lng_actual' => $datos['lng'], 'updated_location_at' => now()])->save();

        if (Schema::hasTable('repartidor_ubicaciones')) {
            $pedidoId = Pedido::withoutGlobalScopes()->where('repartidor_id', $r->id)->where('estado', 'en_camino')->value('id');
            RepartidorUbicacion::query()->create([
                'tenant_id' => $r->tenant_id, 'repartidor_id' => $r->id, 'pedido_id' => $pedidoId,
                'lat' => $datos['lat'], 'lng' => $datos['lng'],
                'precision_m' => isset($datos['precision']) ? (int) round($datos['precision']) : null, 'created_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /** "Salí a entregar" (en_camino) o "Entregado". */
    public function estado(Request $request, string $token, int $id): JsonResponse
    {
        $r = $this->repartidor($token);
        $datos = $request->validate([
            'estado' => ['required', 'in:en_camino,entregado'],
            'cobrado' => ['nullable', 'boolean'],
        ]);

        $pedido = DB::transaction(function () use ($r, $id, $datos) {
            $p = Pedido::withoutGlobalScopes()->where('tenant_id', $r->tenant_id)->where('repartidor_id', $r->id)->lockForUpdate()->findOrFail($id);

            if ($datos['estado'] === 'en_camino') {
                abort_unless(in_array($p->estado, ['pendiente', 'confirmado', 'preparando', 'listo', 'en_camino'], true), 422, 'Este pedido ya no se puede sacar a reparto.');
                $p->forceFill(['estado' => 'en_camino', 'confirmado_at' => $p->confirmado_at ?? now()])->save();
            } else {
                abort_unless(in_array($p->estado, ['listo', 'en_camino', 'entregado'], true), 422, 'Primero marca "Salí a entregar".');
                $pagado = $p->pagado || $p->metodo_pago !== 'efectivo' || (bool) ($datos['cobrado'] ?? false);
                $p->forceFill(['estado' => 'entregado', 'entregado_at' => $p->entregado_at ?? now(), 'pagado' => $pagado])->save();
            }

            return $p;
        });

        // Si ya no le quedan pedidos en ruta, vuelve a estar disponible
        if ($pedido->estado === 'entregado' && ! Pedido::withoutGlobalScopes()->where('repartidor_id', $r->id)->where('estado', 'en_camino')->exists()) {
            $r->forceFill(['disponible' => true])->save();
        } elseif ($pedido->estado === 'en_camino') {
            $r->forceFill(['disponible' => false])->save();
        }

        return response()->json([
            'message' => $pedido->estado === 'entregado' ? "Pedido {$pedido->numero} entregado. ¡Buen trabajo!" : "Pedido {$pedido->numero} en camino.",
            'data' => $this->pedidosDe($r->fresh()),
        ]);
    }

    /** El repartidor se pone en pausa o disponible. */
    public function disponible(string $token): JsonResponse
    {
        $r = $this->repartidor($token);
        $r->forceFill(['disponible' => ! $r->disponible])->save();

        return response()->json(['disponible' => (bool) $r->disponible]);
    }
}
