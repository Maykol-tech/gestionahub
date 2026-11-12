<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class PublicidadController extends Controller {
    public function pagina() { return $this->index(); } public function index() { return view('admin.modulos.publicidad'); }
}