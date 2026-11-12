<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ResenasController extends Controller
{
    public function index() {
        return view('admin.modulos.resenas');
    }
    public function pagina() {
        return $this->index();
    }
}