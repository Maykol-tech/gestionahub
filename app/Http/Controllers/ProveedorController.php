<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraItem;
use App\Models\Proveedor;
use App\Support\OrdenesCompra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Pagina de Proveedores: CRUD de proveedores (GEST-69) y ordenes de compra con su recepcion de stock. */
class ProveedorController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $proveedores = Proveedor::query()->orderBy('nombre')->get(['id', 'nombre', 'activo']);

        $proveedoresLista = Proveedor::query()->orderBy('nombre')->get();

        $insumos = Insumo::query()->orderBy('nombre')->get(['id', 'nombre', 'unidad', 'costo_unitario']);

        $ordenes = OrdenCompra::query()->with(['proveedor:id,nombre', 'items'])
            ->orderByDesc('fecha')->orderByDesc('id')->limit(100)->get()
            ->map(fn (OrdenCompra $o) => [
                'id' => $o->id,
                'numero' => $o->numero ?? 'OC-' . str_pad((string) $o->id, 5, '0', STR_PAD_LEFT),
                'proveedor' => optional($o->proveedor)->nombre ?? 'Proveedor eliminado',
                'fecha' => $o->fecha->format('d/m/Y'),
                'estado' => $o->estado,
                'total' => (float) $o->total,
                'items' => $o->items->count(),
                'notas' => $o->notas,
            ])->values();

        return view('admin.modulos.proveedores', [
            'titulo' => 'Proveedores',
            'modulo' => 'proveedores',
            'proveedores' => $proveedores,
            'proveedoresLista' => $proveedoresLista,
            'insumos' => $insumos,
            'ordenes' => $ordenes,
            'stats' => [
                'proveedores' => $proveedores->count(),
                'abiertas' => OrdenCompra::query()->whereIn('estado', ['borrador', 'enviada'])->count(),
                'gasto_mes' => (float) OrdenCompra::query()->where('estado', 'recibida')
                    ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
            ],
        ]);
    }

    /** GEST-69: crear proveedor. */
    public function guardarProveedor(Request $request): JsonResponse
    {
        $datos = $this->validarProveedor($request);

        $proveedor = Proveedor::query()->create($datos + ['tenant_id' => $request->user()->tenant_id]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor creado.',
            'proveedor' => $this->formatear($proveedor),
        ]);
    }

    /** GEST-69: editar proveedor. */
    public function actualizarProveedor(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::query()->findOrFail($id);
        $proveedor->update($this->validarProveedor($request));

        return response()->json([
            'success' => true,
            'message' => 'Proveedor actualizado.',
            'proveedor' => $this->formatear($proveedor),
        ]);
    }

    /** GEST-69: eliminar proveedor. No se elimina si tiene ordenes de compra; en ese caso se desactiva. */
    public function eliminarProveedor(int $id): JsonResponse
    {
        $proveedor = Proveedor::query()->findOrFail($id);

        if (OrdenCompra::query()->where('proveedor_id', $proveedor->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Este proveedor tiene órdenes de compra. Desactívalo en lugar de eliminarlo.',
            ], 422);
        }

        $proveedor->delete();

        return response()->json(['success' => true, 'message' => 'Proveedor eliminado.']);
    }

    public function guardarOrden(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'proveedor_id' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'notas' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.descripcion' => ['required', 'string', 'max:160'],
            'items.*.insumo_id' => ['nullable', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:999999'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'items.required' => 'Agrega al menos un producto a la orden.',
            'items.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.',
        ]);

        $proveedor = Proveedor::query()->findOrFail($datos['proveedor_id']);

        $insumoIds = collect($datos['items'])->pluck('insumo_id')->filter()->unique()->values();
        if ($insumoIds->isNotEmpty() && Insumo::query()->whereIn('id', $insumoIds)->count() !== $insumoIds->count()) {
            throw ValidationException::withMessages(['items' => 'Uno de los insumos ya no existe.']);
        }

        DB::transaction(function () use ($request, $datos, $proveedor) {
            $tenantId = $request->user()->tenant_id;

            $orden = OrdenCompra::query()->create([
                'tenant_id' => $tenantId,
                'proveedor_id' => $proveedor->id,
                'fecha' => $datos['fecha'],
                'estado' => 'borrador',
                'total' => 0,
                'notas' => $datos['notas'] ?? null,
            ]);

            $total = 0.0;

            foreach ($datos['items'] as $item) {
                $cantidad = (float) $item['cantidad'];
                $precio = (float) $item['precio_unitario'];
                $subtotal = round($cantidad * $precio, 2);
                $total += $subtotal;

                OrdenCompraItem::query()->create([
                    'tenant_id' => $tenantId,
                    'orden_compra_id' => $orden->id,
                    'insumo_id' => $item['insumo_id'] ?? null,
                    'descripcion' => trim($item['descripcion']),
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                ]);
            }

            $orden->update([
                'total' => round($total, 2),
                'numero' => 'OC-' . str_pad((string) $orden->id, 5, '0', STR_PAD_LEFT),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Orden de compra creada como borrador.']);
    }

    public function estadoOrden(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:enviada,recibida,cancelada'],
        ]);

        $orden = OrdenCompra::query()->findOrFail($id);
        $permitidos = OrdenesCompra::TRANSICIONES[$orden->estado] ?? [];

        if (! in_array($datos['estado'], $permitidos, true)) {
            return response()->json([
                'success' => false,
                'message' => "No se puede pasar una orden de " . strtolower(OrdenCompra::ESTADOS[$orden->estado]) . ' a ' . strtolower(OrdenCompra::ESTADOS[$datos['estado']]) . '.',
            ], 422);
        }

        if ($datos['estado'] === 'recibida') {
            OrdenesCompra::recibir($orden);
            $mensaje = 'Orden recibida. El stock de los insumos vinculados ya se actualizó.';
        } else {
            $orden->update(['estado' => $datos['estado']]);
            $mensaje = 'Orden marcada como ' . strtolower(OrdenCompra::ESTADOS[$datos['estado']]) . '.';
        }

        return response()->json(['success' => true, 'message' => $mensaje]);
    }

    public function eliminarOrden(int $id): JsonResponse
    {
        $orden = OrdenCompra::query()->findOrFail($id);

        if (! in_array($orden->estado, ['borrador', 'cancelada'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden eliminar órdenes en borrador o canceladas.',
            ], 422);
        }

        $orden->delete();

        return response()->json(['success' => true, 'message' => 'Orden eliminada.']);
    }

    private function validarProveedor(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'ruc' => ['nullable', 'string', 'max:11', 'regex:/^\d{11}$/'],
            'contacto' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'ruc.regex' => 'El RUC debe tener 11 dígitos.',
            'email' => 'Ingresa un correo válido.',
        ]);

        foreach (['ruc', 'contacto', 'telefono', 'email', 'notas'] as $campo) {
            $valor = isset($datos[$campo]) ? trim((string) $datos[$campo]) : '';
            $datos[$campo] = $valor === '' ? null : $valor;
        }

        $datos['nombre'] = trim($datos['nombre']);
        $datos['activo'] = $request->boolean('activo', true);

        return $datos;
    }

    private function formatear(Proveedor $p): array
    {
        return [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'ruc' => $p->ruc,
            'contacto' => $p->contacto,
            'telefono' => $p->telefono,
            'email' => $p->email,
            'notas' => $p->notas,
            'activo' => (bool) $p->activo,
        ];
    }
}
