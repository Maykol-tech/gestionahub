<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Support\PedidosApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * GEST-62: API de pedidos (/api/v1/pedidos). Se entra con el token creado en /admin/api.
 * El TenantScope filtra solo por el negocio dueño del token.
 */
class PedidosApiController extends Controller
{
    private function permitir(Request $request): void
    {
        abort_unless($request->user()?->tenant_id && $request->user()->tokenCan(PedidosApi::PERMISO), 403, 'Este token no tiene permiso para pedidos.');
    }

    private function buscar(string $pedido): Pedido
    {
        return Pedido::query()->with('items')
            ->where(fn ($q) => ctype_digit($pedido) ? $q->whereKey((int) $pedido)->orWhere('numero', $pedido) : $q->where('numero', $pedido))
            ->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $this->permitir($request);
        $f = $request->validate([
            'estado' => ['nullable', Rule::in(PedidosApi::ESTADOS)],
            'canal' => ['nullable', 'string', 'max:20'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'actualizado_desde' => ['nullable', 'date'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $q = Pedido::query()->with('items')
            ->when($f['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($f['canal'] ?? null, fn ($q, $v) => $q->where('canal', $v))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($f['actualizado_desde'] ?? null, fn ($q, $v) => $q->where('updated_at', '>=', \Carbon\Carbon::parse($v)))
            ->latest('id');
        $pag = $q->paginate($f['por_pagina'] ?? 25)->withQueryString();

        return response()->json([
            'data' => collect($pag->items())->map(fn ($p) => PedidosApi::formatear($p))->values(),
            'meta' => ['pagina' => $pag->currentPage(), 'por_pagina' => $pag->perPage(), 'total' => $pag->total(), 'ultima_pagina' => $pag->lastPage()],
        ]);
    }

    public function show(Request $request, string $pedido): JsonResponse
    {
        $this->permitir($request);

        return response()->json(['data' => PedidosApi::formatear($this->buscar($pedido))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->permitir($request);
        $d = $request->validate([
            'cliente.nombre' => ['required', 'string', 'max:120'],
            'cliente.telefono' => ['nullable', 'string', 'max:20'],
            'tipo' => ['nullable', Rule::in(['recojo', 'delivery'])],
            'direccion' => ['nullable', 'required_if:tipo,delivery', 'string', 'max:255'],
            'metodo_pago' => ['nullable', Rule::in(PedidosApi::METODOS)],
            'pagado' => ['nullable', 'boolean'],
            'costo_envio' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'notas' => ['nullable', 'string', 'max:500'],
            'referencia_externa' => ['nullable', 'string', 'max:60'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.producto_id' => ['nullable', 'integer', 'required_without:items.*.sku'],
            'items.*.sku' => ['nullable', 'string', 'max:60'],
            'items.*.variante_id' => ['nullable', 'integer'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
        ]);
        $pedido = PedidosApi::crear((int) $request->user()->tenant_id, $d);

        return response()->json(['message' => 'Pedido creado.', 'data' => PedidosApi::formatear($pedido->fresh('items'))], 201);
    }

    public function estado(Request $request, string $pedido): JsonResponse
    {
        $this->permitir($request);
        $d = $request->validate([
            'estado' => ['required', Rule::in(PedidosApi::ESTADOS)],
            'motivo' => ['nullable', 'string', 'max:200'],
        ]);
        $p = PedidosApi::cambiarEstado($this->buscar($pedido), $d['estado'], $d['motivo'] ?? null);

        return response()->json(['message' => 'Estado actualizado.', 'data' => PedidosApi::formatear($p->fresh('items'))]);
    }
}
