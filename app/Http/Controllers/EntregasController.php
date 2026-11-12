<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class EntregasController extends Controller
{
    public function index() {
        return view('admin.modulos.entregas');
    }
    public function pagina() {
        return $this->index();
    }
}