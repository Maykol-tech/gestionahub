<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class TiendaPublicaController extends Controller
{
    public function index() {
        return view('admin.modulos.tienda');
    }
    public function pagina() {
        return $this->index();
    }
}