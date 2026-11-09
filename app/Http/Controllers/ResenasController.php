<?php

namespace App\Http\Controllers;

use App\Models\Resena;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Moderacion de reseñas de clientes desde el panel. */
class ResenasController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $estado = array_key_exists($request->query('estado'), Resena::ESTADOS) ? $request->query('estado') : null;

        $query = Resena::query()->orderByDesc('id');
        if ($estado) {
            $query->where('estado', $estado);
        }

        $publicadas = Resena::query()->publicadas();

        return view('admin.modulos.resenas', [
            'titulo' => 'Reseñas de clientes',
            'modulo' => 'resenas',
            'resenas' => $query->limit(200)->get(),
            'filtro' => $estado,
            'promedio' => $publicadas->count() ? round((float) $publicadas->avg('calificacion'), 1) : null,
            'total' => $publicadas->count(),
            'pendientes' => Resena::query()->where('estado', 'pendiente')->count(),
        ]);
    }

    public function estado(Request $request, Resena $resena): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', 'in:' . implode(',', array_keys(Resena::ESTADOS))]]);

        $resena->update(['estado' => $datos['estado']]);

        return back()->with('ok', 'Reseña actualizada.');
    }
}
