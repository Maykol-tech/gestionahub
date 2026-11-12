<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class PagosManualesController extends Controller
{
    public function index() {
        return view('admin.modulos.pagos_manuales');
    }
    public function pagina() {
        return $this->index();
    }
}