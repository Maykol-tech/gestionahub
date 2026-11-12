<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class PersonalizarController extends Controller
{
    public function index() {
        return view('admin.modulos.personalizar');
    }
    public function pagina() {
        return $this->index();
    }
}