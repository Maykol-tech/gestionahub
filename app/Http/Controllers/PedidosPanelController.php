<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\CrmCliente;
use App\Models\Producto;
use Illuminate\Http\Request;

class PedidosPanelController extends Controller
{
    public function pedidos()
    {
        $pedidos = Pedido::with('cliente')->orderBy('created_at', 'desc')->get();
        
        // Datos para el modal de nuevo pedido
        $clientes = CrmCliente::orderBy('nombre')->get();
        $productos = Producto::where('activo', 1)->orderBy('nombre')->get();

        return view('admin.pedidos', compact('pedidos', 'clientes', 'productos'));
    }

    public function actualizarEstado(Request $request, Pedido $pedido)
    {
        $request->validate(['estado' => 'required|in:nuevo,preparando,entregado,cancelado']);
        $pedido->update(['estado' => $request->estado]);
        return response()->json(['success' => true]);
    }
}