<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ModulosController extends Controller
{
    public function mostrar(Request $request, string $modulo)
    {
        $vista = 'admin.modulos.' . $modulo;
        
        if (!view()->exists($vista)) {
            // Si no existe la vista, devolver una página simple "en preparación"
            return response()->view('admin.preparacion', ['modulo' => $modulo], 200);
        }
        
        return view($vista, ['titulo' => ucfirst($modulo)]);
    }
}