<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class RepartidoresController extends Controller
{
    public function index() {
        return view('admin.modulos.repartidores');
    }
    public function pagina() {
        return $this->index();
    }
}