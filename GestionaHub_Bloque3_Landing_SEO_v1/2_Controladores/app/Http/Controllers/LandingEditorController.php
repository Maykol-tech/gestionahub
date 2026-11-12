<?php

namespace App\Http\Controllers;

use App\Models\LandingContenido;
use App\Models\LandingLead;
use App\Services\SeoIaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Editor de la landing SaaS y CRM de leads, solo para el superadmin. */
class LandingEditorController extends Controller
{
    public function pagina(): Response
    {
        return response()->view('superadmin.landing_editor', [
            'c' => LandingContenido::todo(),
            'leads' => LandingLead::query()->orderByDesc('id')->limit(100)->get(),
            'estados' => LandingLead::ESTADOS,
            'ok' => session('ok'),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'hero_titulo' => ['required', 'string', 'max:160'],
            'hero_subtitulo' => ['required', 'string', 'max:400'],
            'hero_cta' => ['required', 'string', 'max:60'],
            'beneficio' => ['required', 'array', 'size:3'],
            'beneficio.*.titulo' => ['required', 'string', 'max:80'],
            'beneficio.*.texto' => ['required', 'string', 'max:250'],
            'pie_texto' => ['required', 'string', 'max:200'],
            'pie_email' => ['nullable', 'email', 'max:150'],
            'gtm_id' => ['nullable', 'regex:/^GTM-[A-Z0-9]{4,12}$/'],
            'utm_activo' => ['sometimes', 'boolean'],
            'popup_activo' => ['sometimes', 'boolean'],
            'popup_titulo' => ['nullable', 'string', 'max:120'],
            'popup_texto' => ['nullable', 'string', 'max:250'],
            'popup_segundos' => ['nullable', 'integer', 'min:5', 'max:120'],
            'popup_oferta' => ['nullable', 'string', 'max:40'],
        ]);

        LandingContenido::guardar('hero', [
            'titulo' => $datos['hero_titulo'],
            'subtitulo' => $datos['hero_subtitulo'],
            'cta' => $datos['hero_cta'],
        ]);
        LandingContenido::guardar('beneficios', ['items' => array_values($datos['beneficio'])]);
        LandingContenido::guardar('pie', ['texto' => $datos['pie_texto'], 'email' => $datos['pie_email'] ?? '']);
        LandingContenido::guardar('analitica', [
            'gtm_id' => $datos['gtm_id'] ?? '',
            'utm_activo' => (bool) ($datos['utm_activo'] ?? false),
        ]);
        LandingContenido::guardar('popup', [
            'activo' => (bool) ($datos['popup_activo'] ?? false),
            'titulo' => $datos['popup_titulo'] ?? '',
            'texto' => $datos['popup_texto'] ?? '',
            'segundos' => (int) ($datos['popup_segundos'] ?? 30),
            'oferta' => $datos['popup_oferta'] ?? 'POPUP50',
        ]);

        return back()->with('ok', 'Landing actualizada.');
    }

    /** Genera el SEO de la landing con IA (o con el texto del hero si no hay clave) y lo guarda. */
    public function seo(SeoIaService $seo): RedirectResponse
    {
        $hero = LandingContenido::todo()['hero'];
        $resultado = $seo->generar($hero);

        LandingContenido::guardar('seo', $resultado);

        $mensaje = $resultado['origen'] === 'openai'
            ? 'SEO generado con IA.'
            : 'SEO generado a partir del titular (sin clave de OpenAI o sin respuesta).';

        return back()->with('ok', $mensaje);
    }

    public function estadoLead(Request $request, LandingLead $lead): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', 'in:' . implode(',', LandingLead::ESTADOS)]]);

        $lead->update(['estado' => $datos['estado']]);

        return back()->with('ok', 'Estado del lead actualizado.');
    }
}
