<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class VitrinaController extends Controller
{
    public function index() {
        return view('admin.modulos.vitrina');
    }
    public function pagina() {
        return $this->index();
    }
}