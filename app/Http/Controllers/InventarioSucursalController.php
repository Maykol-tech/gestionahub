<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Sucursal;
use App\Support\InventarioSucursal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Inventario por sucursal: ver cuánto hay en cada local y trasladar mercadería. */
class InventarioSucursalController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();
        abort_unless(InventarioSucursal::disponible(), 404);

        $m = InventarioSucursal::matriz();
        $nombres = $m['sucursales']->pluck('nombre', 'id');
        $movs = DB::table('movimientos_sucursal')->where('tenant_id', $this->tenantId())
            ->where('motivo', 'traslado')->orderByDesc('id')->limit(15)->get();
        $prods = Producto::query()->whereIn('id', $movs->pluck('producto_id'))->pluck('nombre', 'id');

        return view('admin.inventario_sucursales', [
            'sucursales' => $m['sucursales'],
            'productos' => $m['productos'],
            'movimientos' => $movs->map(fn ($x) => [
                'fecha' => \Carbon\Carbon::parse($x->created_at)->format('d/m H:i'),
                'producto' => $prods[$x->producto_id] ?? 'Producto',
                'desde' => $x->desde_id ? ($nombres[$x->desde_id] ?? 'Sucursal') : 'Principal',
                'hacia' => $x->hacia_id ? ($nombres[$x->hacia_id] ?? 'Sucursal') : 'Principal',
                'cantidad' => (int) $x->cantidad,
                'usuario' => $x->usuario,
            ])->values(),
        ]);
    }

    public function mover(Request $request): JsonResponse
    {
        $ids = Sucursal::query()->where('activo', true)->pluck('id')->all();
        $datos = $request->validate([
            'producto_id' => ['required', 'integer'],
            'desde' => ['required', 'integer', 'in:' . implode(',', array_merge([0], $ids))],
            'hacia' => ['required', 'integer', 'in:' . implode(',', array_merge([0], $ids))],
            'cantidad' => ['required', 'integer', 'between:1,100000'],
        ], [
            'desde.in' => 'Elige de qué local sale.', 'hacia.in' => 'Elige a qué local va.',
            'cantidad.required' => 'Escribe cuántas unidades mueves.', 'cantidad.between' => 'La cantidad debe ser al menos 1.',
        ]);
        $p = Producto::query()->findOrFail($datos['producto_id']);
        InventarioSucursal::mover($p, (int) $datos['desde'], (int) $datos['hacia'], (int) $datos['cantidad'], 'traslado', $request->user()->name);

        return response()->json([
            'message' => "Moviste {$datos['cantidad']} de {$p->nombre} a " . InventarioSucursal::nombre((int) $datos['hacia'] ?: null) . '.',
            'locales' => collect(InventarioSucursal::matriz()['productos'])->firstWhere('id', $p->id)['locales'] ?? null,
        ]);
    }
}
