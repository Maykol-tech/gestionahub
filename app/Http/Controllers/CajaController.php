<?php
namespace App\Http\Controllers;
use App\Models\Pedido;
class CajaController extends Controller {
    public function index() {
        $ventasHoy = Pedido::whereDate('created_at', today())->orderBy('created_at', 'desc')->get();
        $ingresos = $ventasHoy->sum('total');
        $egresos = 0;
        $porCobrar = 0;
        return view('admin.modulos.caja', compact('ventasHoy', 'ingresos', 'egresos', 'porCobrar'));
    }
    public function pagina() { return $this->index(); }
}