<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\TiendaContenido;
use App\Support\TiendaConfig;
use App\Support\TiendaPedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * GEST-102..108 y 112: tienda online pública del negocio.
 * Catálogo con categorías, variantes y venta por kilo, ficha de producto con URL propia (SEO),
 * carrito y checkout en 3 pasos, pedidos programados cuando está cerrado, sitemap y robots.
 */
class TiendaPublicaController extends Controller
{
    private function negocio(string $slug): object
    {
        $n = TiendaConfig::negocioPorSlug($slug);
        abort_if(! $n, 404);

        return $n;
    }

    private function pagina(object $negocio, array $extra = []): View
    {
        $config = TiendaConfig::de($negocio);
        $catalogo = TiendaPedido::catalogo((int) $negocio->id);
        $seo = $config['contenido']['seo'];

        return view('publico.tienda', $extra + [
            'negocio' => $negocio,
            'config' => $config,
            'catalogo' => $catalogo,
            'producto' => null,
            'seo' => [
                'titulo' => $seo['titulo'] ?: $negocio->nombre_negocio . ' · Tienda online',
                'descripcion' => $seo['descripcion'] ?: ($negocio->descripcion ?: 'Pide en línea a ' . $negocio->nombre_negocio . '.'),
                'imagen' => $seo['imagen'] ?: ($config['negocio']['logo'] ?? null),
                'url' => route('tienda.publica', $negocio->slug),
            ],
            'jsonld' => null,
        ]);
    }

    public function index(string $slug): View
    {
        return $this->pagina($this->negocio($slug));
    }

    /** GEST-112: ficha del producto con URL propia, meta, Open Graph y JSON-LD Product. */
    public function producto(string $slug, string $producto): View
    {
        $negocio = $this->negocio($slug);
        $catalogo = TiendaPedido::catalogo((int) $negocio->id);
        $p = collect($catalogo['productos'])->firstWhere('slug', $producto);
        abort_if(! $p, 404);
        $fila = DB::table('productos')->where('id', $p['id'])->first(['meta_titulo', 'meta_descripcion']);
        $simbolo = TiendaConfig::MONEDAS[\App\Support\AjustesNegocio::leer((int) $negocio->id)['tienda_moneda']] ?? 'S/';
        $url = route('tienda.producto', [$negocio->slug, $p['slug']]);

        return $this->pagina($negocio, [
            'producto' => $p,
            'seo' => [
                'titulo' => ($fila->meta_titulo ?? null) ?: $p['nombre'] . ' · ' . $negocio->nombre_negocio,
                'descripcion' => ($fila->meta_descripcion ?? null) ?: (mb_strimwidth(strip_tags($p['descripcion']), 0, 155, '…') ?: $p['nombre'] . ' desde ' . $simbolo . ' ' . number_format($p['precio'], 2) . ' en ' . $negocio->nombre_negocio . '.'),
                'imagen' => $p['fotos'][0] ?? null,
                'url' => $url,
            ],
            'jsonld' => array_filter([
                '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['nombre'],
                'description' => strip_tags($p['descripcion']) ?: null, 'image' => $p['fotos'] ?: null, 'sku' => (string) $p['id'],
                'brand' => ['@type' => 'Brand', 'name' => $negocio->nombre_negocio],
                'offers' => [
                    '@type' => 'Offer', 'url' => $url, 'priceCurrency' => \App\Support\AjustesNegocio::leer((int) $negocio->id)['tienda_moneda'],
                    'price' => number_format($p['precio'], 2, '.', ''),
                    'availability' => $p['agotado'] ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
                ],
            ]),
        ]);
    }

    /** GEST-110: páginas de términos, políticas y preguntas frecuentes. */
    public function paginaLegal(string $slug, string $pagina): View
    {
        $negocio = $this->negocio($slug);
        abort_unless(isset(TiendaContenido::PAGINAS[$pagina]), 404);
        $config = TiendaConfig::de($negocio);
        $p = $config['contenido']['paginas'];
        $contenido = $pagina === 'preguntas' ? (array) ($p['faq'] ?? []) : (string) ($p[$pagina] ?? '');
        abort_if(empty($contenido), 404);

        return view('publico.tienda_pagina', [
            'negocio' => $negocio, 'config' => $config, 'titulo' => TiendaContenido::PAGINAS[$pagina],
            'pagina' => $pagina, 'contenido' => $contenido,
        ]);
    }

    private function datosCarrito(Request $request): array
    {
        if (is_string($request->input('carrito'))) {
            $request->merge(['carrito' => json_decode((string) $request->input('carrito'), true) ?: []]);
        }

        return $request->validate([
            'carrito' => ['required', 'array', 'min:1', 'max:50'],
            'carrito.*.producto_id' => ['required', 'integer'],
            'carrito.*.variante_id' => ['nullable', 'integer'],
            'carrito.*.cantidad' => ['required', 'integer', 'between:1,99'],
            'carrito.*.peso' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'entrega_id' => ['nullable', 'integer'],
            'zona_id' => ['nullable', 'integer'],
            'cupon' => ['nullable', 'string', 'max:40'],
            'usar_puntos' => ['nullable', 'boolean'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ], ['carrito.required' => 'Tu carrito está vacío.', 'carrito.min' => 'Tu carrito está vacío.']);
    }

    /** Cotiza el carrito (precios, envío, cupón y puntos) sin guardar nada. */
    public function cotizar(Request $request, string $slug): JsonResponse
    {
        $negocio = $this->negocio($slug);
        $datos = $this->datosCarrito($request);

        return response()->json(TiendaPedido::resumen(TiendaPedido::cotizar($negocio, $datos)));
    }

    public function pedido(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $negocio = $this->negocio($slug);
        $datos = $this->datosCarrito($request) + $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'regex:/^[0-9 +()-]{9,20}$/'],
            'entrega_id' => ['required', 'integer'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'metodo_pago' => ['required', 'string', 'max:20'],
            'notas' => ['nullable', 'string', 'max:300'],
            'programado_para' => ['nullable', 'date_format:Y-m-d H:i'],
        ], ['telefono.regex' => 'Escribe un celular válido (9 dígitos).', 'entrega_id.required' => 'Elige cómo quieres recibir tu pedido.']);
        $datos['nombre'] = trim(strip_tags($datos['nombre']));
        $datos['notas'] = isset($datos['notas']) ? trim(strip_tags($datos['notas'])) : null;
        $datos['direccion'] = isset($datos['direccion']) ? trim(strip_tags($datos['direccion'])) : null;

        $pedido = TiendaPedido::crear($negocio, $datos);
        $url = $pedido->metodo_pago === 'mercadopago'
            ? route('tienda.mp.pagar', ['slug' => $slug, 'numero' => $pedido->numero])
            : route('tienda.gracias', ['slug' => $slug, 'numero' => $pedido->numero]);

        return $request->expectsJson() ? response()->json(['ok' => true, 'numero' => $pedido->numero, 'url' => $url]) : redirect()->to($url);
    }

    /** Lleva al cliente a pagar con tarjeta en Mercado Pago. */
    public function pagarMercadoPago(string $slug, string $numero): \Illuminate\Http\RedirectResponse
    {
        $negocio = $this->negocio($slug);
        $pedido = Pedido::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('numero', $numero)->firstOrFail();
        if ($pedido->pagado || $pedido->estado === 'cancelado' || $pedido->metodo_pago !== 'mercadopago') {
            return redirect()->route('tienda.gracias', [$slug, $numero]);
        }
        $enlace = \App\Support\MercadoPagoTienda::enlacePago($negocio, $pedido);

        return $enlace ? redirect()->away($enlace)
            : redirect()->route('tienda.gracias', [$slug, $numero])->with('mp_error', 'No pudimos abrir Mercado Pago. Intenta de nuevo en un momento o escribe al negocio.');
    }

    /** Formulario de tarjeta dentro de la tienda: el navegador manda el token de la tarjeta, nunca el número. */
    public function cobrarTarjeta(\Illuminate\Http\Request $request, string $slug, string $numero): \Illuminate\Http\JsonResponse
    {
        $negocio = $this->negocio($slug);
        $pedido = Pedido::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('numero', $numero)->firstOrFail();
        if ($pedido->pagado) {
            return response()->json(['estado' => 'approved', 'mensaje' => 'Este pedido ya está pagado.']);
        }
        abort_if($pedido->estado === 'cancelado' || $pedido->metodo_pago !== 'mercadopago' || ! \App\Support\MercadoPagoTienda::formulario((int) $negocio->id), 422, 'Este pedido no se puede pagar con tarjeta.');
        $form = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'payment_method_id' => ['required', 'string', 'max:40'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:36'],
            'issuer_id' => ['nullable'],
            'payer.email' => ['required', 'email', 'max:120'],
            'payer.identification.type' => ['nullable', 'string', 'max:10'],
            'payer.identification.number' => ['nullable', 'string', 'max:20'],
        ]);
        $r = \App\Support\MercadoPagoTienda::cobrarTarjeta((int) $negocio->id, (float) $pedido->total, $negocio->id . ':' . $pedido->id,
            'Pedido ' . $pedido->numero . ' · ' . $negocio->nombre_negocio, $form);

        return response()->json($r, $r['estado'] === 'error' ? 422 : 200);
    }

    public function gracias(\Illuminate\Http\Request $request, string $slug, string $numero): View
    {
        $negocio = $this->negocio($slug);
        // Vuelta desde Mercado Pago: se confirma consultando el pago (no se confía en la URL)
        if ($request->filled('payment_id') && $request->query('payment_id') !== 'null') {
            \App\Support\MercadoPagoTienda::verificar((int) $negocio->id, (string) $request->query('payment_id'));
        }
        $pedido = Pedido::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('numero', $numero)->with('items')->firstOrFail();
        $config = TiendaConfig::de($negocio);

        $texto = "Hola, hice el pedido {$pedido->numero} en la tienda online por {$config['moneda']['simbolo']} " . number_format((float) $pedido->total, 2) . '.';

        return view('publico.tienda_gracias', [
            'negocio' => $negocio,
            'config' => $config,
            'pedido' => $pedido,
            'color' => $config['diseno']['color_primario'],
            'whatsapp' => $config['whatsapp'] ? 'https://wa.me/' . $config['whatsapp'] . '?text=' . rawurlencode($texto) : null,
            'pago' => collect($config['pagos'])->firstWhere('clave', $pedido->metodo_pago),
            'mpEstado' => $request->query('status'),
            'mpFormulario' => \App\Support\MercadoPagoTienda::formulario((int) $negocio->id) ? \App\Support\MercadoPagoTienda::publicKey((int) $negocio->id) : null,
            'pagoReportado' => \App\Models\PagoManual::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('pedido_id', $pedido->id)
                ->where('origen', 'tienda')->latest('id')->first(),
        ]);
    }

    /** El cliente sube la captura de su Yape, Plin o transferencia; llega a "Pagos por revisar". */
    public function reportarPago(\Illuminate\Http\Request $request, string $slug, string $numero): \Illuminate\Http\RedirectResponse
    {
        $negocio = $this->negocio($slug);
        $pedido = Pedido::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('numero', $numero)->firstOrFail();
        abort_if($pedido->pagado || $pedido->estado === 'cancelado', 422, 'Este pedido ya no necesita pago.');
        abort_unless(in_array($pedido->metodo_pago, ['yape', 'plin', 'transferencia'], true), 422);

        $datos = $request->validate([
            'captura' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'referencia' => ['nullable', 'string', 'max:60'],
        ], [
            'captura.required' => 'Elige la foto o captura de tu pago.',
            'captura.image' => 'La captura debe ser una imagen (foto o pantallazo).',
            'captura.max' => 'La imagen pesa más de 6 MB.',
        ], ['referencia' => 'el número de operación']);

        $previo = \App\Models\PagoManual::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('pedido_id', $pedido->id)
            ->where('origen', 'tienda')->where('estado', 'pendiente')->first();
        $ruta = $request->file('captura')->store('capturas_pago/' . $negocio->id, 'local');
        if ($previo) {
            if ($previo->captura) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($previo->captura);
            }
            $previo->update(['captura' => $ruta, 'referencia' => $datos['referencia'] ?? $previo->referencia]);
        } else {
            \App\Models\PagoManual::withoutGlobalScopes()->create([
                'tenant_id' => $negocio->id, 'pedido_id' => $pedido->id, 'metodo' => $pedido->metodo_pago,
                'monto' => $pedido->total, 'referencia' => $datos['referencia'] ?? null, 'estado' => 'pendiente',
                'notas' => 'Enviado por el cliente desde la tienda', 'captura' => $ruta, 'origen' => 'tienda',
            ]);
        }

        return redirect()->route('tienda.gracias', [$slug, $numero])->with('pago_enviado', true);
    }

    /** GEST-112: sitemap.xml de la tienda. */
    public function sitemap(string $slug): Response
    {
        $negocio = $this->negocio($slug);
        $productos = DB::table('productos')->where('tenant_id', $negocio->id)->where('activo', true)->get(['slug', 'updated_at']);
        $urls = [['loc' => route('tienda.publica', $slug), 'mod' => now()->toDateString(), 'prio' => '1.0']];
        foreach ($productos as $p) {
            $urls[] = ['loc' => route('tienda.producto', [$slug, $p->slug]), 'mod' => substr((string) $p->updated_at, 0, 10) ?: now()->toDateString(), 'prio' => '0.8'];
        }
        $paginas = TiendaContenido::todo((int) $negocio->id)['paginas'];
        foreach (['terminos', 'politicas'] as $k) {
            if (! empty($paginas[$k])) {
                $urls[] = ['loc' => route('tienda.pagina', [$slug, $k]), 'mod' => now()->toDateString(), 'prio' => '0.3'];
            }
        }
        if (! empty($paginas['faq'])) {
            $urls[] = ['loc' => route('tienda.pagina', [$slug, 'preguntas']), 'mod' => now()->toDateString(), 'prio' => '0.4'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . e($u['loc']) . '</loc><lastmod>' . $u['mod'] . '</lastmod><priority>' . $u['prio'] . "</priority></url>\n";
        }

        return response($xml . '</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** GEST-112: robots.txt de la tienda (útil cuando el negocio usa su propio dominio). */
    public function robots(string $slug): Response
    {
        $negocio = $this->negocio($slug);

        return response("User-agent: *\nAllow: /tienda/{$negocio->slug}\nDisallow: /tienda/{$negocio->slug}/pedido/\nDisallow: /admin\n\nSitemap: " . route('tienda.sitemap', $negocio->slug) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
