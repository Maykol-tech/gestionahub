<?php

namespace App\Http\Controllers;

use App\Models\Prospecto;
use App\Services\ProspeccionIaService;
use RuntimeException;
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

    /** Pide prospectos al microservicio de IA y los guarda sin repetidos. */
    public function buscarIa(Request $request, ProspeccionIaService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'rubro' => ['required', 'string', 'max:80'],
            'zona' => ['required', 'string', 'max:80'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            $items = $servicio->buscar($datos['rubro'], $datos['zona'], (int) ($datos['limite'] ?? 20));
        } catch (RuntimeException $e) {
            return back()->withErrors(['ia' => $e->getMessage()])->withInput();
        }

        $resultado = $servicio->importar((int) auth()->user()->tenant_id, $items);

        return back()->with('ok', "Prospección lista: {$resultado['guardados']} nuevos, {$resultado['repetidos']} ya existían.");
    }

    public function estado(Request $request, Prospecto $prospecto): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', 'in:' . implode(',', array_keys(Prospecto::ESTADOS))]]);

        $prospecto->update(['estado' => $datos['estado']]);

        return back()->with('ok', 'Estado actualizado.');
    }
}
