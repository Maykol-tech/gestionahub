<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    // Método que exige tu ruta
    public function pagina()
    {
        $servicios = Servicio::all();
        return view('admin.servicios', compact('servicios'));
    }

    // Alias por si alguna ruta usa index()
    public function index()
    {
        return $this->pagina();
    }
}