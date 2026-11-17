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
            'google' => \App\Support\GoogleResenas::config($this->tenantId()),
            'googleDatos' => \App\Support\GoogleResenas::datos($this->tenantId()),
            'slug' => \App\Models\Tenant::query()->whereKey($this->tenantId())->value('slug'),
        ]);
    }

    /** Conectar las reseñas de Google: ID del lugar y clave de Google (opcional). */
    public function guardarGoogle(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'google_place_id' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+$/'],
            'google_api_key' => ['nullable', 'string', 'max:120', 'regex:/^AIza[0-9A-Za-z_-]{20,}$/'],
            'google_mostrar' => ['nullable', 'boolean'],
            'quitar_clave' => ['nullable', 'boolean'],
        ], [
            'google_place_id.regex' => 'El ID del lugar no parece válido. Suele empezar con "ChIJ".',
            'google_api_key.regex' => 'La clave de Google no parece válida. Empieza con "AIza".',
        ]);
        $tenantId = $this->tenantId();
        $guardar = [
            'google_place_id' => trim((string) ($datos['google_place_id'] ?? '')),
            'google_mostrar' => $request->boolean('google_mostrar'),
        ];
        if (! empty($datos['google_api_key'])) {
            $guardar['google_api_key'] = \Illuminate\Support\Facades\Crypt::encryptString($datos['google_api_key']);
        } elseif ($request->boolean('quitar_clave')) {
            $guardar['google_api_key'] = '';
        }
        \App\Support\AjustesNegocio::guardar($tenantId, $guardar);
        \Illuminate\Support\Facades\Cache::forget(\App\Support\GoogleResenas::claveCache($tenantId));

        return back()->with('ok', 'Datos de Google guardados.');
    }

    public function estado(Request $request, Resena $resena): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', 'in:' . implode(',', array_keys(Resena::ESTADOS))]]);

        $resena->update(['estado' => $datos['estado']]);

        return back()->with('ok', 'Reseña actualizada.');
    }
}
