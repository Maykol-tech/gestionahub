<?php

namespace App\Http\Controllers;

use App\Support\PlantillasRubro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Onboarding y personalizacion basica: rubro, plantilla sugerida y color de marca (GEST-84). */
class PersonalizacionController extends DashboardController
{
    public function index() { return $this->pagina(); } public function pagina()
    {
        $this->compartirLayout();

        $negocio = DB::table('tenants')->where('id', auth()->user()->tenant_id)
            ->first(['rubro', 'color_primario']);

        return view('admin.modulos.personalizacion', [
            'titulo' => 'Personalización',
            'modulo' => 'personalizacion',
            'rubro' => $negocio->rubro ?? null,
            'color' => $negocio->color_primario ?? '#2B6EF6',
            'plantillas' => PlantillasRubro::CATALOGO,
            'plantilla' => PlantillasRubro::de($negocio->rubro ?? null),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'rubro' => ['required', 'in:' . implode(',', PlantillasRubro::claves())],
            'color_primario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        DB::table('tenants')->where('id', auth()->user()->tenant_id)->update([
            'rubro' => $datos['rubro'],
            'color_primario' => strtoupper($datos['color_primario']),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Personalización guardada.');
    }
}
