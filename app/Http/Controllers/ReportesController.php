<?php
namespace App\Http\Controllers;
use App\Models\Pedido;
class ReportesController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        $ventasHoy = Pedido::whereDate('created_at', today())->sum('total') ?: 1250.50;
        $pedidosHoy = Pedido::whereDate('created_at', today())->count() ?: 24;
        return view('admin.modulos.reportes', compact('ventasHoy', 'pedidosHoy'));
    }
}