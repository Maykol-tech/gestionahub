<?php
namespace App\Http\Controllers;
use App\Models\CrmCliente;
use Illuminate\Http\Request;
class FidelizacionController extends Controller {
    public function pagina() { return $this->index(); } public function index() {
        // Simulamos puntos para los clientes existentes
        $clientes = CrmCliente::take(5)->get();
        return view('admin.fidelizacion', compact('clientes'));
    }
}