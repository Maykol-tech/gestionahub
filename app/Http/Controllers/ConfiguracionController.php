<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function pagina() { return $this->index(); } public function index()
    {
        return view('admin.configuracion');
    }
}