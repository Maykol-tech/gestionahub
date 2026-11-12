<?php
namespace App\Http\Controllers;
use App\Models\Pedido;
class ComprobanteController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        $comprobantes = Pedido::whereIn('estado', ['entregado', 'pagado'])->orderBy('created_at', 'desc')->take(20)->get();
        return view('admin.modulos.comprobantes', compact('comprobantes'));
    }
}