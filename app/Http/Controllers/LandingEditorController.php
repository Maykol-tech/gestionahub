<?php

namespace App\Http\Controllers;

use App\Models\LandingContenido;
use App\Models\LandingLead;
use App\Services\SeoIaService;
use App\Support\BlogWordpress;
use App\Support\Cms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Editor de la landing SaaS y CRM de leads, solo para el superadmin. */
class LandingEditorController extends Controller
{
    public function pagina(): Response
    {
        $c = LandingContenido::todo();

        return response()->view('superadmin.landing_editor', [
            'c' => $c,
            // GEST-98: listas editables (si aún no hay filas en las tablas CMS, se parte de los textos actuales)
            'cms' => [
                'faqs' => Cms::lista('faqs', soloVisibles: false) ?: array_map(fn ($f) => $f + ['visible' => true], $c['faq']['items'] ?? []),
                'testimonios' => Cms::lista('testimonios', soloVisibles: false) ?: array_map(fn ($t) => $t + ['visible' => true, 'estrellas' => 5, 'foto_url' => ''], $c['testimonios']['items'] ?? []),
                'logos' => Cms::lista('logos', soloVisibles: false),
                'secciones' => collect(Cms::secciones())->map(fn ($s, $k) => $s + ['clave' => $k])->values(),
                'blog' => $c['blog'],
            ],
            'leadsNuevos' => LandingLead::where('estado', 'nuevo')->count(),
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
            // GEST-101: cifras y datos reales de la empresa
            'cifras_mostrar' => ['sometimes', 'boolean'],
            'cifra' => ['sometimes', 'array', 'size:4'],
            'cifra.*.valor' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'cifra.*.sufijo' => ['nullable', 'string', 'max:8'],
            'cifra.*.texto' => ['nullable', 'string', 'max:60'],
            'razon_social' => ['nullable', 'string', 'max:150'],
            'ruc' => ['nullable', 'digits:11'],
            'whatsapp' => ['nullable', 'regex:/^\+?[0-9 ]{9,16}$/'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'terminos_url' => ['nullable', 'url', 'max:255'],
            'privacidad_url' => ['nullable', 'url', 'max:255'],
        ], [
            'ruc.digits' => 'El RUC debe tener 11 dígitos.',
            'whatsapp.regex' => 'Escribe el WhatsApp solo con números, por ejemplo 51987654321.',
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

        if (isset($datos['cifra'])) {
            LandingContenido::guardar('cifras', [
                'mostrar' => (bool) ($datos['cifras_mostrar'] ?? false),
                'items' => array_map(fn ($c) => [
                    'valor' => (int) ($c['valor'] ?? 0), 'sufijo' => (string) ($c['sufijo'] ?? ''), 'texto' => (string) ($c['texto'] ?? ''),
                ], array_values($datos['cifra'])),
            ]);
        }
        LandingContenido::guardar('empresa', [
            'razon_social' => $datos['razon_social'] ?? '',
            'ruc' => $datos['ruc'] ?? '',
            // Solo digitos, listo para wa.me
            'whatsapp' => preg_replace('/\D/', '', (string) ($datos['whatsapp'] ?? '')),
            'video_url' => $datos['video_url'] ?? '',
            'terminos_url' => $datos['terminos_url'] ?? '',
            'privacidad_url' => $datos['privacidad_url'] ?? '',
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

    /** GEST-98: guarda una lista del CMS (preguntas, testimonios, logos), el orden de secciones o el blog. */
    public function guardarCms(Request $request): JsonResponse
    {
        $tipo = $request->validate(['tipo' => ['required', 'in:faqs,testimonios,logos,secciones,blog']])['tipo'];

        if ($tipo === 'blog') {
            $d = $request->validate([
                'url' => ['nullable', 'url', 'max:255'],
                'titulo' => ['nullable', 'string', 'max:120'],
                'cantidad' => ['required', 'integer', 'min:1', 'max:6'],
            ]);
            BlogWordpress::limpiar(LandingContenido::todo()['blog']['url'] ?? '', (int) (LandingContenido::todo()['blog']['cantidad'] ?? 3));
            LandingContenido::guardar('blog', ['url' => $d['url'] ?? '', 'titulo' => $d['titulo'] ?: 'Consejos para tu negocio', 'cantidad' => (int) $d['cantidad']]);
            $entradas = BlogWordpress::entradas($d['url'] ?? '', (int) $d['cantidad']);

            return response()->json(['ok' => true, 'message' => $d['url']
                ? (count($entradas) ? 'Blog conectado: ' . count($entradas) . ' artículos.' : 'Guardado, pero no pudimos leer artículos de ese blog. Revisa que sea WordPress y que la dirección esté bien.')
                : 'Blog desactivado.', 'entradas' => $entradas]);
        }

        $reglas = [
            'faqs' => ['items.*.pregunta' => ['required', 'string', 'max:255'], 'items.*.respuesta' => ['required', 'string', 'max:2000']],
            'testimonios' => ['items.*.nombre' => ['required', 'string', 'max:120'], 'items.*.negocio' => ['nullable', 'string', 'max:160'],
                'items.*.texto' => ['required', 'string', 'max:600'], 'items.*.foto_url' => ['nullable', 'url', 'max:255'], 'items.*.estrellas' => ['nullable', 'integer', 'min:1', 'max:5']],
            'logos' => ['items.*.nombre' => ['required', 'string', 'max:120'], 'items.*.imagen_url' => ['nullable', 'url', 'max:255'], 'items.*.enlace' => ['nullable', 'url', 'max:255']],
            'secciones' => ['items.*.clave' => ['required', 'in:' . implode(',', array_keys(Cms::SECCIONES_SAAS))],
                'items.*.titulo' => ['nullable', 'string', 'max:160'], 'items.*.subtitulo' => ['nullable', 'string', 'max:255']],
        ][$tipo];
        $d = $request->validate(['items' => ['present', 'array', 'max:40'], 'items.*.visible' => ['sometimes', 'boolean']] + $reglas, [], [
            'items.*.pregunta' => 'pregunta', 'items.*.respuesta' => 'respuesta', 'items.*.nombre' => 'nombre', 'items.*.texto' => 'texto',
            'items.*.foto_url' => 'foto', 'items.*.imagen_url' => 'imagen', 'items.*.enlace' => 'enlace',
        ]);

        if ($tipo === 'secciones') {
            Cms::guardarSecciones(collect($d['items'])->keyBy('clave')->all());
        } else {
            Cms::reemplazar($tipo, $d['items']);
        }

        return response()->json(['ok' => true, 'message' => 'Guardado. Ya se ve en la landing.']);
    }

    public function estadoLead(Request $request, LandingLead $lead): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', 'in:' . implode(',', LandingLead::ESTADOS)]]);

        $lead->update(['estado' => $datos['estado']]);

        return back()->with('ok', 'Estado del lead actualizado.');
    }
}
