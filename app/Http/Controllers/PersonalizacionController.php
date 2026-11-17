<?php

namespace App\Http\Controllers;

use App\Models\GaleriaItem;
use App\Models\Tenant;
use App\Models\TiendaContenido;
use App\Support\AjustesNegocio;
use App\Support\DisenoNegocio;
use App\Support\Imagenes;
use App\Support\PlantillasRubro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Personalización de la web del negocio:
 *  - GEST-83: 3 plantillas por rubro con vista previa antes de aplicar (no borra contenido).
 *  - GEST-84: logo, colores con vista previa en vivo, tipografía, textos del hero y del pie, y favicon.
 */
class PersonalizacionController extends DashboardController
{
    private function textos(int $tenantId): array
    {
        $guardado = TiendaContenido::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('seccion', 'landing')->value('contenido') ?? [];

        return array_replace_recursive(LandingNegocioController::DEFECTO, is_array($guardado) ? $guardado : []);
    }

    public function pagina()
    {
        $this->compartirLayout();
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);
        $d = DisenoNegocio::de($tenant);
        $t = $this->textos($tenant->id);

        return view('admin.modulos.personalizacion', [
            'titulo' => 'Personalizar mi web',
            'modulo' => 'personalizacion',
            'cfg' => [
                'rubro' => $tenant->rubro ?? 'otro',
                'rubros' => collect(PlantillasRubro::CATALOGO)->map(fn ($r, $k) => ['clave' => $k, 'nombre' => $r['nombre'], 'icono' => $r['icono']])->values(),
                'plantillas' => collect(PlantillasRubro::claves())->mapWithKeys(fn ($k) => [$k => array_values(PlantillasRubro::plantillasDe($k))]),
                'fuentes' => array_keys(PlantillasRubro::FUENTES),
                'd' => [
                    'plantilla' => $d['plantilla'], 'color_primario' => $d['color_primario'], 'color_secundario' => $d['color_secundario'],
                    'fuente' => $d['fuente'], 'pie_texto' => $d['pie_texto'] ?? '', 'logo_url' => $d['logo_url'], 'favicon_url' => $d['favicon_url'],
                    'titulo' => $t['titulo'], 'subtitulo' => $t['subtitulo'], 'boton' => $t['boton'],
                    'beneficios' => array_values(array_slice(array_pad((array) ($t['beneficios'] ?? []), 3, ['titulo' => '', 'texto' => '']), 0, 3)),
                ],
                'slug' => $tenant->slug,
                'urls' => [
                    'guardar' => route('admin.personalizacion.guardar'),
                    'aplicar' => route('admin.personalizacion.plantilla'),
                    'vista' => route('admin.personalizacion.vista'),
                    'publica' => $tenant->slug ? route('publico.landing_negocio', $tenant->slug) : null,
                    'tienda' => $tenant->slug ? url('/tienda/' . $tenant->slug) : null,
                ],
            ],
        ]);
    }

    /** Vista previa de la landing con cambios sin guardar (para el iframe). */
    public function vistaPrevia(Request $request): Response
    {
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);
        $c = $this->textos($tenant->id);

        // Al previsualizar otra plantilla o rubro se muestran los textos de ejemplo de ese rubro
        if ($request->filled('rubro') && isset(PlantillasRubro::CATALOGO[$request->query('rubro')]) && $request->query('rubro') !== $tenant->rubro) {
            $c = array_replace($c, PlantillasRubro::CATALOGO[$request->query('rubro')]['textos']);
        }
        foreach (['titulo' => 120, 'subtitulo' => 255, 'boton' => 40] as $k => $max) {
            if ($request->filled($k)) {
                $c[$k] = mb_substr((string) $request->query($k), 0, $max);
            }
        }
        $beneficios = json_decode((string) $request->query('beneficios', ''), true);
        if (is_array($beneficios) && count($beneficios) === 3) {
            $c['beneficios'] = array_map(fn ($b) => [
                'titulo' => mb_substr((string) ($b['titulo'] ?? ''), 0, 80),
                'texto' => mb_substr((string) ($b['texto'] ?? ''), 0, 200),
            ], array_values($beneficios));
        }

        return response()->view('publico.landing_negocio', [
            'negocio' => $tenant,
            'c' => $c,
            'd' => DisenoNegocio::de($tenant, $request->only(['plantilla', 'color_primario', 'color_secundario', 'fuente', 'pie_texto'])),
            'enviada' => null,
            'preview' => true,
            'galeria' => GaleriaItem::query()->where('activo', true)->orderBy('orden')->limit(8)->get(),
        ])->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /** Aplica una plantilla: cambia diseño, colores y fuente; solo completa textos que estén vacíos. */
    public function aplicarPlantilla(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'rubro' => ['required', 'in:' . implode(',', PlantillasRubro::claves())],
            'plantilla' => ['required', 'in:' . implode(',', array_keys(PlantillasRubro::DISENOS))],
        ]);
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);
        $p = PlantillasRubro::plantillasDe($datos['rubro'])[$datos['plantilla']];

        $tenant->rubro = $datos['rubro'];
        $tenant->color_primario = $p['color_primario'];
        $tenant->save();
        AjustesNegocio::guardar($tenant->id, ['plantilla' => $p['clave'], 'color_secundario' => $p['color_secundario'], 'fuente' => $p['fuente']]);

        $registro = TiendaContenido::withoutGlobalScopes()->firstOrNew(['tenant_id' => $tenant->id, 'seccion' => 'landing']);
        $actual = is_array($registro->contenido) ? $registro->contenido : [];
        $ejemplo = PlantillasRubro::CATALOGO[$datos['rubro']]['textos'];
        foreach ($ejemplo as $k => $v) {
            if (empty($actual[$k])) {
                $actual[$k] = $v; // no se pisa lo que el negocio ya escribió
            }
        }
        $registro->contenido = $actual;
        $registro->save();

        return response()->json(['message' => "Plantilla {$p['nombre']} aplicada. Tus textos y fotos se mantienen.", 'd' => [
            'plantilla' => $p['clave'], 'color_primario' => $p['color_primario'], 'color_secundario' => $p['color_secundario'], 'fuente' => $p['fuente'],
            'titulo' => $actual['titulo'], 'subtitulo' => $actual['subtitulo'], 'boton' => $actual['boton'],
        ]]);
    }

    /** Guarda el diseño (GEST-84). Acepta logo y favicon como archivos. */
    public function guardar(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // Compatibilidad: formulario antiguo con solo rubro y color
        if (! $request->has('fuente') && ! $request->expectsJson()) {
            $datos = $request->validate([
                'rubro' => ['required', 'in:' . implode(',', PlantillasRubro::claves())],
                'color_primario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ]);
            Tenant::query()->whereKey(auth()->user()->tenant_id)->update(['rubro' => $datos['rubro'], 'color_primario' => strtoupper($datos['color_primario'])]);

            return back()->with('ok', 'Personalización guardada.');
        }

        $datos = $request->validate([
            'rubro' => ['nullable', 'in:' . implode(',', PlantillasRubro::claves())],
            'plantilla' => ['required', 'in:' . implode(',', array_keys(PlantillasRubro::DISENOS))],
            'color_primario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_secundario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'fuente' => ['required', 'in:' . implode(',', array_keys(PlantillasRubro::FUENTES))],
            'titulo' => ['required', 'string', 'max:120'],
            'subtitulo' => ['required', 'string', 'max:255'],
            'boton' => ['required', 'string', 'max:40'],
            'pie_texto' => ['nullable', 'string', 'max:200'],
            'beneficios' => ['nullable', 'array', 'size:3'],
            'beneficios.*.titulo' => ['required_with:beneficios', 'string', 'max:80'],
            'beneficios.*.texto' => ['required_with:beneficios', 'string', 'max:200'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'quitar_logo' => ['nullable', 'boolean'],
        ], [
            'titulo.required' => 'Escribe el título de tu portada.',
            'subtitulo.required' => 'Escribe una frase debajo del título.',
            'boton.required' => 'Escribe el texto del botón.',
            'beneficios.*.titulo.required_with' => 'Completa el título de cada beneficio.',
            'beneficios.*.texto.required_with' => 'Completa el texto de cada beneficio.',
            'color_primario.regex' => 'Color no válido.', 'color_secundario.regex' => 'Color no válido.',
            'logo.max' => 'El logo puede pesar hasta 4 MB.', 'favicon.max' => 'El ícono puede pesar hasta 1 MB.',
        ]);

        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);
        $tenant->color_primario = strtoupper($datos['color_primario']);
        if (! empty($datos['rubro'])) {
            $tenant->rubro = $datos['rubro'];
        }
        if ($request->hasFile('logo')) {
            $tenant->logo_url = Imagenes::guardarWebp($request->file('logo'), "logos/{$tenant->id}", 600, 200)['url'];
        } elseif ($request->boolean('quitar_logo')) {
            $tenant->logo_url = null;
        }
        $tenant->save();

        $ajustes = [
            'plantilla' => $datos['plantilla'], 'color_secundario' => strtoupper($datos['color_secundario']),
            'fuente' => $datos['fuente'], 'pie_texto' => trim((string) ($datos['pie_texto'] ?? '')),
        ];
        if ($request->hasFile('favicon')) {
            $ajustes['favicon_url'] = Imagenes::guardarWebp($request->file('favicon'), "logos/{$tenant->id}", 192, 64)['miniatura_url'];
        }
        AjustesNegocio::guardar($tenant->id, $ajustes);

        $registro = TiendaContenido::withoutGlobalScopes()->firstOrNew(['tenant_id' => $tenant->id, 'seccion' => 'landing']);
        $registro->contenido = array_merge(is_array($registro->contenido) ? $registro->contenido : [], [
            'titulo' => $datos['titulo'], 'subtitulo' => $datos['subtitulo'], 'boton' => $datos['boton'],
        ] + (isset($datos['beneficios']) ? ['beneficios' => array_values(array_map(fn ($b) => ['titulo' => $b['titulo'], 'texto' => $b['texto']], $datos['beneficios']))] : []));
        $registro->save();

        return response()->json(['message' => 'Diseño guardado. Ya se ve en tu web.', 'logo_url' => $tenant->logo_url, 'favicon_url' => $ajustes['favicon_url'] ?? AjustesNegocio::leer($tenant->id)['favicon_url']]);
    }
}
