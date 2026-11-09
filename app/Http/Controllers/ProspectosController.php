<?php

namespace App\Http\Controllers;

use App\Models\Prospecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Listado y gestion de prospectos: alta manual, filtro por estado y cambio de estado. */
class ProspectosController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $estado = $request->query('estado');

        $query = Prospecto::query()->orderByDesc('id');
        if ($estado && array_key_exists($estado, Prospecto::ESTADOS)) {
            $query->where('estado', $estado);
        }

        return view('admin.modulos.prospectos', [
            'titulo' => 'Prospectos',
            'modulo' => 'prospectos',
            'prospectos' => $query->limit(200)->get(),
            'filtro' => $estado,
            'conteos' => Prospecto::query()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado'),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'rubro' => ['nullable', 'string', 'max:80'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        Prospecto::create($datos + ['tenant_id' => auth()->user()->tenant_id, 'fuente' => 'manual']);

        return back()->with('ok', 'Prospecto agregado.');
    }

    public function estado(Request $request, Prospecto $prospecto): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', 'in:' . implode(',', array_keys(Prospecto::ESTADOS))]]);

        $prospecto->update(['estado' => $datos['estado']]);

        return back()->with('ok', 'Estado actualizado.');
    }
}
