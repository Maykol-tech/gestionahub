<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class SucursalesController extends Controller
{
    public function index() {
        return view('admin.modulos.sucursales');
    }
    public function pagina() {
        return $this->index();
    }
}