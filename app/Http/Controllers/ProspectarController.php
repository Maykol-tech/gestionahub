<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ProspectarController extends Controller
{
    public function index() {
        return view('admin.modulos.prospectar');
    }
    public function pagina() {
        return $this->index();
    }
}