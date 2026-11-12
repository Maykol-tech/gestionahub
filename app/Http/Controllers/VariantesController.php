<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class VariantesController extends Controller
{
    public function index() {
        return view('admin.modulos.variantes');
    }
    public function pagina() {
        return $this->index();
    }
}