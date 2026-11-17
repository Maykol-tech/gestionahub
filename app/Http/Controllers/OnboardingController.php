<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Servicio;
use App\Models\Tenant;
use App\Models\TiendaContenido;
use App\Support\AjustesNegocio;
use App\Support\Imagenes;
use App\Support\PlantillasRubro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * GEST-82: asistente de bienvenida en 4 pasos (rubro, datos, logo y colores, módulos).
 * Según el rubro se muestran los módulos del menú y, si el negocio quiere, se cargan
 * categorías, servicios y textos de ejemplo sin tocar lo que ya tenga.
 */
class OnboardingController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);
        $ajustes = AjustesNegocio::leer($tenant->id);
        $rubro = $tenant->rubro ?? null;

        $visibles = $ajustes['onboarding_listo']
            ? array_values(array_diff(array_keys(PlantillasRubro::MODULOS_OPCIONALES), (array) $ajustes['modulos_ocultos']))
            : PlantillasRubro::modulosDe($rubro);

        return view('admin.modulos.bienvenida', [
            'titulo' => 'Configura tu negocio',
            'modulo' => 'bienvenida',
            'cfg' => [
                'rubros' => collect(PlantillasRubro::CATALOGO)->map(fn ($r, $k) => [
                    'clave' => $k, 'nombre' => $r['nombre'], 'icono' => $r['icono'], 'modulos' => $r['modulos'],
                    'categorias' => $r['categorias'], 'servicios' => $r['servicios'], 'color' => $r['paletas'][0][0], 'color2' => $r['paletas'][0][1],
                ])->values(),
                'modulos' => PlantillasRubro::MODULOS_OPCIONALES,
                'datos' => [
                    'rubro' => $rubro ?? '',
                    'nombre_negocio' => $tenant->nombre_negocio,
                    'telefono' => $tenant->telefono ?? '',
                    'direccion' => $tenant->direccion ?? '',
                    'ruc' => $tenant->ruc ?? '',
                    'color_primario' => $tenant->color_primario ?: '#2B6EF6',
                    'color_secundario' => $ajustes['color_secundario'],
                    'logo_url' => $tenant->logo_url,
                    'modulos' => $visibles,
                    'ejemplos' => true,
                ],
                'urls' => ['guardar' => route('admin.bienvenida.guardar'), 'inicio' => route('admin.dashboard')],
            ],
        ]);
    }

    public function guardar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'rubro' => ['required', 'in:' . implode(',', PlantillasRubro::claves())],
            'nombre_negocio' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +()-]*$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ruc' => ['nullable', 'digits:11'],
            'color_primario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_secundario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'modulos' => ['nullable', 'array'],
            'modulos.*' => ['string', 'in:' . implode(',', array_keys(PlantillasRubro::MODULOS_OPCIONALES))],
            'ejemplos' => ['nullable', 'boolean'],
        ], [
            'rubro.required' => 'Elige el rubro de tu negocio.',
            'nombre_negocio.required' => 'Escribe el nombre de tu negocio.',
            'telefono.regex' => 'El teléfono solo puede tener números y + ( ) -.',
            'ruc.digits' => 'El RUC tiene 11 dígitos.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.max' => 'El logo puede pesar hasta 4 MB.',
        ]);

        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);
        $tenant->nombre_negocio = trim($datos['nombre_negocio']);
        $tenant->telefono = ($datos['telefono'] ?? null) ?: null;
        $tenant->ruc = ($datos['ruc'] ?? null) ?: null;
        foreach (['rubro' => $datos['rubro'], 'direccion' => ($datos['direccion'] ?? null) ?: null, 'color_primario' => strtoupper($datos['color_primario'])] as $col => $val) {
            if (Schema::hasColumn('tenants', $col)) {
                $tenant->{$col} = $val;
            }
        }
        if ($request->hasFile('logo')) {
            $tenant->logo_url = Imagenes::guardarWebp($request->file('logo'), "logos/{$tenant->id}", 600, 200)['url'];
        }
        $tenant->save();

        $visibles = $datos['modulos'] ?? [];
        AjustesNegocio::guardar($tenant->id, [
            'onboarding_listo' => true,
            'modulos_ocultos' => PlantillasRubro::ocultosSegun($visibles),
            'color_secundario' => strtoupper($datos['color_secundario']),
            'color_menu' => strtoupper($datos['color_primario']),
            'color_accion' => strtoupper($datos['color_secundario']),
            'fuente' => PlantillasRubro::plantillasDe($datos['rubro'])['clasica']['fuente'],
        ]);

        $cargados = $request->boolean('ejemplos') ? $this->cargarEjemplos($tenant, $datos['rubro']) : 0;

        return response()->json([
            'message' => '¡Listo! Tu negocio quedó configurado' . ($cargados ? " y cargamos {$cargados} ejemplos para empezar." : '.'),
            'redirect' => route('admin.dashboard'),
        ]);
    }

    /** Carga categorías, servicios y textos de ejemplo del rubro. No duplica ni sobrescribe. */
    private function cargarEjemplos(Tenant $tenant, string $rubro): int
    {
        $r = PlantillasRubro::de($rubro);
        $n = 0;

        foreach ($r['categorias'] as $i => $nombre) {
            $existe = Categoria::query()->where('nombre', $nombre)->exists();
            if (! $existe) {
                $slug = Str::slug($nombre);
                Categoria::create(['tenant_id' => $tenant->id, 'nombre' => $nombre, 'slug' => Categoria::query()->where('slug', $slug)->exists() ? $slug . '-' . Str::lower(Str::random(4)) : $slug, 'orden' => $i + 1, 'activo' => true]);
                $n++;
            }
        }

        if (Schema::hasTable('servicios') && in_array('servicios', PlantillasRubro::modulosDe($rubro), true)) {
            foreach ($r['servicios'] as $nombre) {
                if (! Servicio::query()->where('nombre', $nombre)->exists()) {
                    Servicio::create(['tenant_id' => $tenant->id, 'nombre' => $nombre, 'duracion_min' => 30, 'precio' => 0, 'color' => $tenant->color_primario ?: '#2B6EF6', 'activo' => true]);
                    $n++;
                }
            }
        }

        // Textos de la landing: solo si el negocio aún no escribió los suyos
        $landing = TiendaContenido::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('seccion', 'landing')->first();
        if (! $landing) {
            TiendaContenido::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'seccion' => 'landing', 'contenido' => $r['textos']]);
            $n++;
        }

        return $n;
    }
}
