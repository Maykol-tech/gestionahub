<?php
namespace App\Http\Controllers;
use App\Models\Pedido;
use Illuminate\Http\Request;
class CocinaController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        // Lógica real: Traer pedidos en estado "preparando"
        $pedidos = Pedido::with('cliente')->whereIn('estado', ['nuevo', 'preparando'])->orderBy('created_at', 'asc')->get();
        return view('admin.cocina', compact('pedidos'));
    }
}