<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;

class PedidoApiController extends Controller {
    public function index(Request $request) {
        $query = Pedido::with('cliente');
        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('fecha')) $query->whereDate('created_at', $request->fecha);
        return response()->json(['ok' => true, 'data' => $query->paginate(20), 'message' => '']);
    }

    public function show($id) {
        $pedido = Pedido::with(['cliente', 'items'])->findOrFail($id);
        return response()->json(['ok' => true, 'data' => $pedido, 'message' => '']);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'cliente_id' => 'nullable|exists:crm_clientes,id',
            'total' => 'required|numeric|min:0',
            'estado' => 'required|in:nuevo,preparando,entregado,cancelado',
        ]);
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['numero'] = 'API-' . strtoupper(uniqid());
        $data['canal'] = 'api';
        $data['subtotal'] = $data['total'];
        $pedido = Pedido::create($data);
        return response()->json(['ok' => true, 'data' => $pedido, 'message' => 'Pedido creado'], 201);
    }

    public function updateEstado(Request $request, $id) {
        $request->validate(['estado' => 'required|in:nuevo,preparando,entregado,cancelado']);
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['estado' => $request->estado]);
        return response()->json(['ok' => true, 'data' => $pedido, 'message' => 'Estado actualizado']);
    }
}