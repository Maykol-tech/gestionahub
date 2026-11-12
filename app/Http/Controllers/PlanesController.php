<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class PlanesController extends Controller
{
    public function index() {
        return view('admin.modulos.planes');
    }
    public function pagina() {
        return $this->index();
    }
}