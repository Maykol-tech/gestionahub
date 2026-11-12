<?php
namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Pedido;
use Illuminate\Http\Request;

class PosController extends Controller {
    public function index() {
        $productos = Producto::where('activo', 1)->orderBy('nombre')->get();
        
        // KPIs reales del día
        $pedidosHoy = Pedido::whereDate('created_at', today())->where('canal', 'pos')->get();
        $ventasHoy = $pedidosHoy->count();
        $cobradoHoy = $pedidosHoy->sum('total');
        $ticketPromedio = $ventasHoy > 0 ? $cobradoHoy / $ventasHoy : 0;
        $igvHoy = $cobradoHoy * 0.18;
        
        return view('admin.modulos.pos', compact('productos', 'ventasHoy', 'cobradoHoy', 'ticketPromedio', 'igvHoy'));
    }

    public function cobrar(Request $request) {
        $data = $request->validate([
            'total' => 'required|numeric|min:0',
            'metodo_pago' => 'required|string',
            'items' => 'required|array|min:1',
        ]);
        
        $pedido = Pedido::create([
            'tenant_id' => auth()->user()->tenant_id,
            'cliente_id' => null,
            'numero' => 'POS-' . strtoupper(substr(uniqid(), -6)),
            'canal' => 'pos',
            'estado' => 'entregado',
            'subtotal' => $data['total'],
            'total' => $data['total'],
            'notas' => 'Pago: ' . $data['metodo_pago'],
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Venta registrada',
            'data' => ['pedido_id' => $pedido->id, 'numero' => $pedido->numero]
        ]);
    }
}