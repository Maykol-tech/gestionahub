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

    private function datosEntrega(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'tipo' => ['required', 'in:' . implode(',', array_keys(FormaEntrega::TIPOS))],
            'costo' => ['required', 'numeric', 'min:0', 'max:9999'],
            'tiempo_estimado' => ['nullable', 'string', 'max:40'],
            'monto_minimo' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'gratis_desde' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [], ['monto_minimo' => 'pedido mínimo', 'gratis_desde' => 'envío gratis desde']);

        return [
            'monto_minimo' => $datos['monto_minimo'] ?? 0,
            'gratis_desde' => ($datos['gratis_desde'] ?? null) ?: null,
            'usa_zonas' => $datos['tipo'] === 'delivery' && $request->boolean('usa_zonas'),
            'orden' => $datos['orden'] ?? 0,
        ] + $datos;
    }

    /** GEST-109: crear forma de entrega (delivery, recojo, agencia o consumo en el local). */
    public function guardarEntrega(Request $request): RedirectResponse
    {
        FormaEntrega::create($this->datosEntrega($request) + ['tenant_id' => auth()->user()->tenant_id, 'activa' => true]);

        return back()->with('ok', 'Forma de entrega agregada.');
    }

    public function actualizarEntrega(Request $request, FormaEntrega $entrega): RedirectResponse
    {
        $entrega->update($this->datosEntrega($request));

        return back()->with('ok', 'Forma de entrega actualizada.');
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

    /** GEST-110: editor de la tienda (portada, banners, avisos, destacados, páginas, pagos, redes y cupones). */
    public function contenido()
    {
        $this->compartirLayout();
        $tenantId = (int) auth()->user()->tenant_id;
        $negocio = DB::table('tenants')->where('id', $tenantId)->first();

        return view('admin.modulos.tienda_contenido', [
            'titulo' => 'Contenido de la tienda',
            'modulo' => 'tienda',
            'c' => TiendaContenido::todo($tenantId),
            'a' => \App\Support\AjustesNegocio::leer($tenantId),
            'negocio' => $negocio,
            'metodos' => \App\Support\TiendaConfig::METODOS_PAGO,
            'monedas' => \App\Support\TiendaConfig::MONEDAS,
            'productos' => DB::table('productos')->where('tenant_id', $tenantId)->where('activo', true)->orderByDesc('destacado')->orderBy('nombre')->limit(300)->get(['id', 'nombre', 'destacado', 'precio']),
            'cupones' => DB::table('cupones_tienda')->where('tenant_id', $tenantId)->orderByDesc('id')->get(),
            'url' => $negocio && $negocio->slug ? route('tienda.publica', $negocio->slug) : null,
        ]);
    }

    public function guardarContenido(Request $request): RedirectResponse
    {
        if (is_string($request->input('banners'))) {
            $request->merge(['banners' => json_decode((string) $request->input('banners'), true) ?: []]);
        }
        if (is_string($request->input('faq'))) {
            $request->merge(['faq' => json_decode((string) $request->input('faq'), true) ?: []]);
        }
        $datos = $request->validate([
            'portada_titulo' => ['nullable', 'string', 'max:120'],
            'portada_subtitulo' => ['nullable', 'string', 'max:250'],
            'bienvenida' => ['nullable', 'string', 'max:400'],
            'avisos' => ['nullable', 'string', 'max:600'],
            'mensaje_confirmacion' => ['nullable', 'string', 'max:300'],
            'pie_texto' => ['nullable', 'string', 'max:200'],
            'pie_email' => ['nullable', 'email', 'max:150'],
            'seo_titulo' => ['nullable', 'string', 'max:60'],
            'seo_descripcion' => ['nullable', 'string', 'max:160'],
            'seo_imagen' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
            'banners' => ['nullable', 'array', 'max:6'],
            'banners.*.imagen' => ['nullable', 'string', 'max:500'],
            'banners.*.titulo' => ['nullable', 'string', 'max:80'],
            'banners.*.texto' => ['nullable', 'string', 'max:160'],
            'banners.*.enlace' => ['nullable', 'string', 'max:300', 'regex:/^(https?:\/\/|#|\/)/'],
            'banner_archivo' => ['nullable', 'array', 'max:6'],
            'banner_archivo.*' => ['nullable', 'image', 'max:3072'],
            'terminos' => ['nullable', 'string', 'max:20000'],
            'politicas' => ['nullable', 'string', 'max:20000'],
            'faq' => ['nullable', 'array', 'max:30'],
            'faq.*.pregunta' => ['nullable', 'string', 'max:200'],
            'faq.*.respuesta' => ['nullable', 'string', 'max:1500'],
            'destacados' => ['nullable', 'array', 'max:40'],
            'destacados.*' => ['integer'],
            // Pagos, redes y opciones (AjustesNegocio)
            'tienda_pagos' => ['nullable', 'array'],
            'tienda_pagos.*' => ['in:' . implode(',', array_keys(\App\Support\TiendaConfig::METODOS_PAGO))],
            'tienda_moneda' => ['nullable', 'in:' . implode(',', array_keys(\App\Support\TiendaConfig::MONEDAS))],
            'tienda_whatsapp' => ['nullable', 'regex:/^[0-9 +()-]{9,20}$/'],
            'tienda_facebook' => ['nullable', 'url', 'max:200'],
            'tienda_instagram' => ['nullable', 'url', 'max:200'],
            'tienda_tiktok' => ['nullable', 'url', 'max:200'],
            'yape_numero' => ['nullable', 'regex:/^9\d{8}$/'],
            'mp_access_token' => ['nullable', 'string', 'max:200', 'regex:/^(APP_USR|TEST)-[A-Za-z0-9_-]+$/'],
            'mp_public_key' => ['nullable', 'string', 'max:120', 'regex:/^(APP_USR|TEST)-[A-Za-z0-9_-]+$/'],
            'plin_numero' => ['nullable', 'regex:/^9\d{8}$/'],
            'yape_qr' => ['nullable', 'image', 'max:2048'],
        ], [
            'banners.*.enlace.regex' => 'El enlace del banner debe empezar con https://, # o /.',
            'tienda_whatsapp.regex' => 'Escribe un WhatsApp válido.',
            'mp_access_token.regex' => 'El Access Token de Mercado Pago empieza con APP_USR- o TEST-.',
            'mp_public_key.regex' => 'La Public Key de Mercado Pago empieza con APP_USR- o TEST-.',
            'yape_numero.regex' => 'El número de Yape debe tener 9 dígitos y empezar con 9.',
            'plin_numero.regex' => 'El número de Plin debe tener 9 dígitos y empezar con 9.',
        ]);

        $tenantId = (int) auth()->user()->tenant_id;
        $limpio = fn ($t) => trim(strip_tags((string) $t));

        // Banners: imagen subida o enlace a una imagen
        $banners = [];
        foreach (array_values((array) ($datos['banners'] ?? [])) as $k => $b) {
            $imagen = trim((string) ($b['imagen'] ?? ''));
            if ($archivo = $request->file("banner_archivo.$k")) {
                $imagen = \Illuminate\Support\Facades\Storage::disk('public')->url($archivo->store("tienda/{$tenantId}", 'public'));
            }
            if ($imagen !== '' && ! preg_match('#^(https?://|/)#', $imagen)) {
                $imagen = '';
            }
            $item = ['imagen' => $imagen, 'titulo' => $limpio($b['titulo'] ?? ''), 'texto' => $limpio($b['texto'] ?? ''), 'enlace' => trim((string) ($b['enlace'] ?? ''))];
            if ($item['imagen'] !== '' || $item['titulo'] !== '') {
                $banners[] = $item;
            }
        }

        $avisos = array_values(array_slice(array_filter(array_map($limpio, preg_split('/\r\n|\n/', (string) ($datos['avisos'] ?? '')))), 0, 6));
        $faq = array_values(array_filter(array_map(fn ($f) => ['pregunta' => $limpio($f['pregunta'] ?? ''), 'respuesta' => $limpio($f['respuesta'] ?? '')], (array) ($datos['faq'] ?? [])), fn ($f) => $f['pregunta'] !== ''));

        $secciones = [
            'portada' => ['titulo' => $datos['portada_titulo'] ?? '', 'subtitulo' => $datos['portada_subtitulo'] ?? ''],
            'bienvenida' => ['texto' => $limpio($datos['bienvenida'] ?? '')],
            'avisos' => ['items' => $avisos],
            'banners' => ['items' => $banners],
            'pedido' => ['mensaje_confirmacion' => $datos['mensaje_confirmacion'] ?? ''],
            'pie' => ['texto' => $datos['pie_texto'] ?? '', 'email' => $datos['pie_email'] ?? ''],
            'paginas' => ['terminos' => $limpio($datos['terminos'] ?? ''), 'politicas' => $limpio($datos['politicas'] ?? ''), 'faq' => $faq],
            'seo' => [
                'titulo' => $datos['seo_titulo'] ?? '',
                'descripcion' => $datos['seo_descripcion'] ?? '',
                'imagen' => $datos['seo_imagen'] ?? '',
            ],
        ];

        DB::transaction(function () use ($secciones, $tenantId, $datos, $request) {
            foreach ($secciones as $seccion => $contenido) {
                // Se reemplaza la sección completa para que las listas (banners, preguntas) se puedan acortar
                TiendaContenido::withoutGlobalScopes()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'seccion' => $seccion],
                    ['contenido' => $contenido]
                );
            }

            // GEST-110: productos destacados
            if ($request->has('destacados_enviado')) {
                $ids = array_map('intval', (array) ($datos['destacados'] ?? []));
                DB::table('productos')->where('tenant_id', $tenantId)->update(['destacado' => false]);
                if ($ids) {
                    DB::table('productos')->where('tenant_id', $tenantId)->whereIn('id', $ids)->update(['destacado' => true]);
                }
            }
        });

        \App\Support\AjustesNegocio::guardar($tenantId, [
            'tienda_pagos' => array_values(array_unique((array) ($datos['tienda_pagos'] ?? ['efectivo']))) ?: ['efectivo'],
            'tienda_moneda' => $datos['tienda_moneda'] ?? 'PEN',
            'tienda_whatsapp' => preg_replace('/\D/', '', (string) ($datos['tienda_whatsapp'] ?? '')),
            'tienda_facebook' => $datos['tienda_facebook'] ?? '',
            'tienda_instagram' => $datos['tienda_instagram'] ?? '',
            'tienda_tiktok' => $datos['tienda_tiktok'] ?? '',
            'tienda_programar' => $request->boolean('tienda_programar'),
            'tienda_puntos' => $request->boolean('tienda_puntos'),
            'mp_public_key' => $request->boolean('quitar_mp') ? '' : trim((string) ($datos['mp_public_key'] ?? '')),
        ] + (! empty($datos['mp_access_token']) ? ['mp_access_token' => \Illuminate\Support\Facades\Crypt::encryptString(trim($datos['mp_access_token']))]
            : ($request->boolean('quitar_mp') ? ['mp_access_token' => ''] : [])));

        $tenant = ['yape_numero' => $datos['yape_numero'] ?? null, 'plin_numero' => $datos['plin_numero'] ?? null];
        if ($qr = $request->file('yape_qr')) {
            $tenant['yape_qr'] = \Illuminate\Support\Facades\Storage::disk('public')->url($qr->store("tienda/{$tenantId}", 'public'));
        } elseif ($request->boolean('quitar_qr')) {
            $tenant['yape_qr'] = null;
        }
        DB::table('tenants')->where('id', $tenantId)->update($tenant);

        return back()->with('ok', 'Tienda guardada. Los cambios ya se ven en tu tienda online.');
    }

    /** GEST-106: cupones de la tienda. */
    public function guardarCupon(Request $request): RedirectResponse
    {
        $request->merge(['codigo' => \App\Support\Cupones::normalizar($request->input('codigo'))]);
        $tenantId = (int) auth()->user()->tenant_id;
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/',
                \Illuminate\Validation\Rule::unique('cupones_tienda', 'codigo')->where('tenant_id', $tenantId)],
            'tipo' => ['required', 'in:porcentaje,monto'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999', $request->input('tipo') === 'porcentaje' ? 'max:100' : 'max:99999'],
            'minimo' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'usos_max' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'vence' => ['nullable', 'date', 'after_or_equal:today'],
        ], ['codigo.unique' => 'Ya tienes un cupón con ese código.', 'codigo.regex' => 'Usa solo letras, números y guiones en el código.']);

        DB::table('cupones_tienda')->insert($datos + ['tenant_id' => $tenantId, 'minimo' => $datos['minimo'] ?? 0, 'usos' => 0, 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->to(route('admin.tienda.contenido') . '#cupones')->with('ok', 'Cupón ' . $datos['codigo'] . ' creado.');
    }

    public function alternarCupon(int $cupon): RedirectResponse
    {
        $fila = DB::table('cupones_tienda')->where('tenant_id', auth()->user()->tenant_id)->where('id', $cupon)->first();
        abort_if(! $fila, 404);
        DB::table('cupones_tienda')->where('id', $fila->id)->update(['activo' => ! $fila->activo, 'updated_at' => now()]);

        return redirect()->to(route('admin.tienda.contenido') . '#cupones')->with('ok', $fila->activo ? 'Cupón pausado.' : 'Cupón activado.');
    }

    public function eliminarCupon(int $cupon): RedirectResponse
    {
        DB::table('cupones_tienda')->where('tenant_id', auth()->user()->tenant_id)->where('id', $cupon)->delete();

        return redirect()->to(route('admin.tienda.contenido') . '#cupones')->with('ok', 'Cupón eliminado.');
    }

    /**
     * GEST-103: configuración pública de la tienda (TENANT_CONFIG) en un solo JSON:
     * negocio, diseño, moneda, horarios, entregas, zonas, pagos, contenido y galería.
     */
    public function config(string $slug): JsonResponse
    {
        $negocio = \App\Support\TiendaConfig::negocioPorSlug($slug);
        abort_if(! $negocio, 404);

        $galeria = DB::table('galeria_items')->where('tenant_id', $negocio->id)->where('activo', true)
            ->orderBy('orden')->get(['titulo', 'descripcion', 'imagen_url'])->values();

        return response()->json(\App\Support\TiendaConfig::de($negocio) + ['galeria' => $galeria]);
    }
}
