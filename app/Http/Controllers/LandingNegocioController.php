<?php

namespace App\Http\Controllers;

use App\Models\LandingLead;
use App\Models\Tenant;
use App\Models\TiendaContenido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Landing de cada negocio (GEST-49): portada, beneficios y formulario de contacto.
 * Sus textos se editan desde el panel y se guardan en tienda_contenido, seccion "landing".
 */
class LandingNegocioController extends DashboardController
{
    public const DEFECTO = [
        'titulo' => 'Bienvenido a nuestro negocio',
        'subtitulo' => 'Conoce lo que hacemos y escríbenos cuando quieras.',
        'boton' => 'Escríbenos',
        'beneficios' => [
            ['titulo' => 'Atención rápida', 'texto' => 'Respondemos por WhatsApp en el día.'],
            ['titulo' => 'Calidad', 'texto' => 'Trabajamos con productos y servicios de confianza.'],
            ['titulo' => 'Cercanía', 'texto' => 'Estamos para ayudarte en cada paso.'],
        ],
    ];

    public function editor()
    {
        $this->compartirLayout();

        $guardado = TiendaContenido::query()->where('seccion', 'landing')->value('contenido') ?? [];

        return view('admin.modulos.landing_negocio', [
            'titulo' => 'Landing de mi negocio',
            'modulo' => 'landing',
            'c' => array_replace_recursive(self::DEFECTO, $guardado),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'subtitulo' => ['required', 'string', 'max:250'],
            'boton' => ['required', 'string', 'max:40'],
            'beneficio' => ['required', 'array', 'size:3'],
            'beneficio.*.titulo' => ['required', 'string', 'max:80'],
            'beneficio.*.texto' => ['required', 'string', 'max:200'],
        ]);

        TiendaContenido::query()->updateOrCreate(
            ['seccion' => 'landing'],
            [
                'tenant_id' => auth()->user()->tenant_id,
                'contenido' => [
                    'titulo' => $datos['titulo'],
                    'subtitulo' => $datos['subtitulo'],
                    'boton' => $datos['boton'],
                    'beneficios' => array_values($datos['beneficio']),
                ],
            ]
        );

        return back()->with('ok', 'Landing actualizada.');
    }

    /** Pagina publica de la landing del negocio, con formulario que crea un lead en su CRM. */
    public function publica(string $slug): Response
    {
        $negocio = Tenant::query()->where('slug', $slug)->where('estado', 'activo')->firstOrFail();

        $guardado = TiendaContenido::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('seccion', 'landing')->value('contenido') ?? [];

        return response()->view('publico.landing_negocio', [
            'negocio' => $negocio,
            'c' => array_replace_recursive(self::DEFECTO, $guardado),
            'enviada' => session('ok'),
        ]);
    }

    public function contacto(Request $request, string $slug): RedirectResponse
    {
        $negocio = Tenant::query()->where('slug', $slug)->where('estado', 'activo')->firstOrFail();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:30'],
            'mensaje' => ['nullable', 'string', 'max:500'],
        ]);

        // Va al CRM de prospectos del negocio (tabla prospectos, fuente "landing del negocio")
        \App\Models\Prospecto::withoutGlobalScopes()->create([
            'tenant_id' => $negocio->id,
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'fuente' => 'landing_negocio',
            'estado' => 'nuevo',
            'notas' => $datos['mensaje'] ?? null,
        ]);

        return redirect()->route('publico.landing_negocio', $slug)->with('ok', 'Gracias, te escribiremos pronto.');
    }
}
