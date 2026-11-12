<?php
namespace App\Http\Controllers;
use App\Models\Pedido;
use App\Models\Repartidor;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function pagina() { return $this->index(); } public function index() {
        return response()->json(Pedido::with(['cliente', 'repartidor'])->latest()->get());
    }
    public function store(Request $request) {
        $datos = $request->validate([
            'cliente_id' => 'nullable|exists:crm_clientes,id',
            'total' => 'required|numeric',
            'direccion_entrega' => 'nullable|string',
        ]);
        $datos['tenant_id'] = 1;
        $datos['numero'] = 'PED-' . str_pad(Pedido::count() + 1, 4, '0', STR_PAD_LEFT);
        $pedido = Pedido::create($datos);
        return response()->json(['success' => true, 'data' => $pedido], 201);
    }
    public function asignarRepartidor(Request $request, $id) {
        $request->validate(['repartidor_id' => 'required|exists:repartidores,id']);
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['repartidor_id' => $request->repartidor_id, 'estado' => 'en_camino']);
        return response()->json(['success' => true, 'data' => $pedido]);
    }
    public function cambiarEstado(Request $request, $id) {
        $request->validate(['estado' => 'required|in:pendiente,confirmado,preparando,listo,en_camino,entregado,cancelado']);
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['estado' => $request->estado]);
        return response()->json(['success' => true, 'data' => $pedido]);
    }
}