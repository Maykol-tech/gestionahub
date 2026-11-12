<?php
namespace App\Http\Controllers;
use App\Models\Pedido;
class IngresosManualesController extends Controller {
    public function pagina() {
        $ingresos = Pedido::where('canal', 'manual')->orderBy('created_at', 'desc')->take(20)->get();
        return view('admin.modulos.ingresos_manuales', compact('ingresos'));
    }
    public function index() { return $this->pagina(); }
}