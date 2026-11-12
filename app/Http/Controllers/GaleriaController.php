<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class GaleriaController extends Controller
{
    public function index() {
        return view('admin.modulos.galeria');
    }
    public function pagina() {
        return $this->index();
    }
}