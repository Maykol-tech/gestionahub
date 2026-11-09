<?php

namespace App\Http\Controllers;

use App\Models\Resena;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Pagina publica de reseñas del negocio: ver las publicadas y dejar una nueva. */
class PublicResenaController extends Controller
{
    public function mostrar(string $slug): Response
    {
        $negocio = $this->negocio($slug);

        $resenas = Resena::withoutGlobalScopes()->where('tenant_id', $negocio->id)->publicadas()
            ->orderByDesc('id')->limit(50)->get(['cliente_nombre', 'calificacion', 'comentario', 'created_at']);

        $promedio = $resenas->count() ? round($resenas->avg('calificacion'), 1) : null;

        return response()->view('publico.resenas', [
            'negocio' => $negocio,
            'resenas' => $resenas,
            'promedio' => $promedio,
            'enviada' => session('ok'),
        ]);
    }

    public function enviar(Request $request, string $slug): RedirectResponse
    {
        $negocio = $this->negocio($slug);

        $datos = $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:120'],
            'calificacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        Resena::withoutGlobalScopes()->create($datos + [
            'tenant_id' => $negocio->id,
            'estado' => 'pendiente',
            'ip' => $request->ip(),
        ]);

        return redirect()->route('publico.resenas', $slug)
            ->with('ok', '¡Gracias por tu reseña! Se publicará cuando la revisemos.');
    }

    private function negocio(string $slug): Tenant
    {
        return Tenant::query()->where('slug', $slug)->where('estado', 'activo')->firstOrFail();
    }
}
