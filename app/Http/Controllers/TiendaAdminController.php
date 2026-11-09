<?php

namespace App\Http\Controllers;

use App\Models\FormaEntrega;
use App\Models\TiendaContenido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Formas de entrega y contenido de la tienda desde el panel (GEST-109 y GEST-110),
 * y la configuracion publica que usa la plantilla de la tienda (GEST-103).
 */
class TiendaAdminController extends DashboardController
{
    public function entregas()
    {
        $this->compartirLayout();

        return view('admin.modulos.entregas', [
            'titulo' => 'Formas de entrega',
            'modulo' => 'entregas',
            'formas' => FormaEntrega::query()->orderBy('orden')->orderBy('id')->get(),
            'tipos' => FormaEntrega::TIPOS,
        ]);
    }

    public function guardarEntrega(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'tipo' => ['required', 'in:' . implode(',', array_keys(FormaEntrega::TIPOS))],
            'costo' => ['required', 'numeric', 'min:0', 'max:9999'],
            'tiempo_estimado' => ['nullable', 'string', 'max:40'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        FormaEntrega::create($datos + ['tenant_id' => auth()->user()->tenant_id, 'activa' => true]);

        return back()->with('ok', 'Forma de entrega agregada.');
    }

    public function alternarEntrega(FormaEntrega $entrega): RedirectResponse
    {
        $entrega->update(['activa' => ! $entrega->activa]);

        return back()->with('ok', $entrega->activa ? 'Entrega activada.' : 'Entrega desactivada.');
    }

    public function eliminarEntrega(FormaEntrega $entrega): RedirectResponse
    {
        $entrega->delete();

        return back()->with('ok', 'Forma de entrega eliminada.');
    }

    public function contenido()
    {
        $this->compartirLayout();

        return view('admin.modulos.tienda_contenido', [
            'titulo' => 'Contenido de la tienda',
            'modulo' => 'tienda',
            'c' => TiendaContenido::todo((int) auth()->user()->tenant_id),
        ]);
    }

    public function guardarContenido(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'portada_titulo' => ['nullable', 'string', 'max:120'],
            'portada_subtitulo' => ['nullable', 'string', 'max:250'],
            'mensaje_confirmacion' => ['nullable', 'string', 'max:300'],
            'pie_texto' => ['nullable', 'string', 'max:200'],
            'pie_email' => ['nullable', 'email', 'max:150'],
            'seo_titulo' => ['nullable', 'string', 'max:60'],
            'seo_descripcion' => ['nullable', 'string', 'max:160'],
            'seo_imagen' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
        ]);

        $tenantId = (int) auth()->user()->tenant_id;

        $secciones = [
            'portada' => ['titulo' => $datos['portada_titulo'] ?? '', 'subtitulo' => $datos['portada_subtitulo'] ?? ''],
            'pedido' => ['mensaje_confirmacion' => $datos['mensaje_confirmacion'] ?? ''],
            'pie' => ['texto' => $datos['pie_texto'] ?? '', 'email' => $datos['pie_email'] ?? ''],
            'seo' => [
                'titulo' => $datos['seo_titulo'] ?? '',
                'descripcion' => $datos['seo_descripcion'] ?? '',
                'imagen' => $datos['seo_imagen'] ?? '',
            ],
        ];

        foreach ($secciones as $seccion => $contenido) {
            TiendaContenido::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'seccion' => $seccion],
                ['contenido' => $contenido]
            );
        }

        return back()->with('ok', 'Contenido de la tienda guardado.');
    }

    /**
     * Configuracion publica para la plantilla de la tienda (TENANT_CONFIG):
     * datos del negocio, horarios, formas de entrega, contenido y galería, en un solo JSON.
     */
    public function config(string $slug): JsonResponse
    {
        $negocio = DB::table('tenants')->where('slug', $slug)->where('estado', 'activo')->first();
        abort_if(! $negocio, 404);

        $horarios = $negocio->horarios ?? null;
        $horarios = $horarios ? (is_array($horarios) ? $horarios : json_decode((string) $horarios, true)) : null;
        $abierta = \App\Support\Horarios::abiertoAhora($horarios);

        $entregas = FormaEntrega::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('activa', true)->orderBy('orden')->orderBy('id')
            ->get(['nombre', 'tipo', 'costo', 'tiempo_estimado'])
            ->map(fn ($e) => [
                'nombre' => $e->nombre,
                'tipo' => $e->tipo,
                'costo' => (float) $e->costo,
                'tiempo' => $e->tiempo_estimado,
            ])->values();

        $galeria = DB::table('galeria_items')->where('tenant_id', $negocio->id)->where('activo', true)
            ->orderBy('orden')->get(['titulo', 'descripcion', 'imagen_url'])->values();

        return response()->json([
            'negocio' => [
                'nombre' => $negocio->nombre_negocio,
                'slug' => $negocio->slug,
                'telefono' => $negocio->telefono ?? null,
                'direccion' => $negocio->direccion ?? null,
                'descripcion' => $negocio->descripcion ?? null,
                'sitio_web' => $negocio->sitio_web ?? null,
                'ruc' => $negocio->ruc ?? null,
                'rubro' => $negocio->rubro ?? null,
                'color_primario' => $negocio->color_primario ?? '#2B6EF6',
            ],
            'horarios' => $horarios,
            'abierta' => $abierta,
            'entregas' => $entregas,
            'contenido' => TiendaContenido::todo((int) $negocio->id),
            'galeria' => $galeria,
        ]);
    }
}
