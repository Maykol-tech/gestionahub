<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\Cita;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function pagina() 
    {
        $servicios = Servicio::where('tenant_id', 2)->get();
        $citas = Cita::where('tenant_id', 2)->get();
        return view('admin.modulos.servicios', compact('servicios', 'citas'));
    }

    public function guardarServicio(Request $request)
    {
        $request->validate(['nombre' => 'required', 'precio' => 'required|numeric']);
        Servicio::create([
            'tenant_id' => 2, 'nombre' => $request->nombre, 
            'precio' => $request->precio, 'duracion_min' => $request->duracion ?? 30, 'activo' => true
        ]);
        return back()->with('success', 'Servicio creado');
    }
}