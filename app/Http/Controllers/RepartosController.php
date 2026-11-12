<?php
namespace App\Http\Controllers;
use App\Models\Repartidor;
use App\Models\Pedido;
class RepartosController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        $repartidores = Repartidor::all();
        $pedidos = Pedido::whereIn('estado', ['preparando', 'en_camino'])->get();
        return view('admin.modulos.repartos', compact('repartidores', 'pedidos'));
    }
}