<?php

namespace App\Support;

use App\Models\CheckoutAbandonado;
use App\Models\CrmCliente;
use App\Models\FormaEntrega;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * GEST-105..108: catálogo de la tienda (variantes y venta por kilo), cotización del carrito
 * (entrega por zona, mínimo, cupón y puntos) y registro del pedido con aviso por WhatsApp.
 * El precio siempre se calcula en el servidor: lo que manda el navegador solo dice qué y cuánto.
 */
class TiendaPedido
{
    public static function urlImagen(?string $ruta): ?string
    {
        if (! $ruta) {
            return null;
        }

        return Str::startsWith($ruta, ['http://', 'https://', '/']) ? $ruta : asset('storage/' . $ruta);
    }

    /** Catálogo visible de la tienda. */
    public static function catalogo(int $tenantId): array
    {
        $productos = Producto::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activo', true)
            ->orderByDesc('destacado')->orderBy('nombre')->get();
        $ids = $productos->pluck('id');

        $imagenes = DB::table('producto_imagenes')->whereIn('producto_id', $ids)->orderByDesc('es_principal')->orderBy('orden')->get()->groupBy('producto_id');
        $variantes = ProductoVariante::withoutGlobalScopes()->whereIn('producto_id', $ids)->where('activo', true)
            ->orderBy(Schema::hasColumn('producto_variantes', 'orden') ? 'orden' : 'id')->orderBy('id')->get()->groupBy('producto_id');
        $kilos = Schema::hasTable('producto_kilo')
            ? DB::table('producto_kilo')->whereIn('producto_id', $ids)->where('activo', true)->get()->keyBy('producto_id') : collect();

        $categorias = DB::table('categorias')->where('tenant_id', $tenantId)->where('activo', true)->orderBy('orden')->orderBy('nombre')
            ->get(['id', 'nombre', 'slug', 'imagen_url']);

        $lista = $productos->map(function (Producto $p) use ($imagenes, $variantes, $kilos) {
            $fotos = collect($imagenes[$p->id] ?? [])->map(fn ($i) => self::urlImagen($i->ruta))->filter()->values();
            if ($p->imagen_url) {
                $fotos->prepend(self::urlImagen($p->imagen_url));
            }
            $fotos = $fotos->unique()->values()->all();
            [$precio, $normal] = self::precios((float) $p->precio, $p->precio_oferta !== null ? (float) $p->precio_oferta : null);

            $vars = collect($variantes[$p->id] ?? [])->map(function ($v) use ($p) {
                [$vp, $vn] = self::precios($v->precio !== null ? (float) $v->precio : (float) $p->precio, isset($v->precio_oferta) && $v->precio_oferta !== null ? (float) $v->precio_oferta : null);

                return ['id' => $v->id, 'nombre' => $v->nombre, 'precio' => $vp, 'normal' => $vn, 'stock' => (int) $v->stock, 'agotado' => (int) $v->stock <= 0];
            })->values()->all();

            $kilo = $kilos[$p->id] ?? null;
            $stock = $vars ? array_sum(array_column($vars, 'stock')) : (int) $p->stock;

            return [
                'id' => $p->id,
                'slug' => $p->slug,
                'nombre' => $p->nombre,
                'descripcion' => (string) $p->descripcion,
                'categoria_id' => $p->categoria_id,
                'precio' => $kilo ? (float) $kilo->precio_kg : ($vars ? min(array_column($vars, 'precio')) : $precio),
                'normal' => $kilo || $vars ? null : $normal,
                'fotos' => $fotos,
                'stock' => $stock,
                'agotado' => ! $kilo && $stock <= 0,
                'destacado' => (bool) ($p->destacado ?? false),
                'variantes' => $vars,
                'kilo' => $kilo ? [
                    'precio_kg' => (float) $kilo->precio_kg,
                    'presentaciones' => array_values(array_filter(array_map('floatval', (array) json_decode((string) ($kilo->presentaciones ?? '[]'), true)))) ?: [0.25, 0.5, 1],
                    'peso_libre' => (bool) ($kilo->peso_libre ?? true),
                    'peso_minimo' => (float) ($kilo->peso_minimo ?? 0.1),
                ] : null,
            ];
        })->values()->all();

        return ['productos' => $lista, 'categorias' => $categorias->map(fn ($c) => (array) $c)->values()->all()];
    }

    /** [precio vigente, precio normal tachado o null] */
    public static function precios(float $precio, ?float $oferta): array
    {
        return ($oferta !== null && $oferta > 0 && $oferta < $precio) ? [$oferta, $precio] : [$precio, null];
    }

    /**
     * Calcula el pedido sin guardarlo. $datos: carrito[{producto_id, variante_id?, cantidad, peso?}],
     * entrega_id, zona_id?, cupon?, puntos?, telefono?
     */
    public static function cotizar(object $negocio, array $datos, bool $bloquear = false): array
    {
        $tenantId = (int) $negocio->id;
        $lineas = [];
        $subtotal = 0.0;

        foreach ((array) ($datos['carrito'] ?? []) as $l) {
            $q = Producto::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activo', true);
            $producto = ($bloquear ? $q->lockForUpdate() : $q)->find((int) ($l['producto_id'] ?? 0));
            if (! $producto) {
                throw ValidationException::withMessages(['carrito' => 'Un producto de tu carrito ya no está disponible. Quítalo para continuar.']);
            }
            $cantidad = max(1, min(99, (int) ($l['cantidad'] ?? 1)));
            $kilo = Schema::hasTable('producto_kilo') ? DB::table('producto_kilo')->where('producto_id', $producto->id)->where('activo', true)->first() : null;
            $variante = null;
            $peso = null;

            if ($kilo) {
                $peso = round((float) ($l['peso'] ?? 0), 3);
                if ($peso < (float) ($kilo->peso_minimo ?? 0.1) || $peso > 50) {
                    throw ValidationException::withMessages(['carrito' => "Elige el peso de {$producto->nombre}."]);
                }
                $unitario = round((float) $kilo->precio_kg * $peso, 2);
                $nombre = $producto->nombre . ' (' . self::textoPeso($peso) . ')';
            } elseif (! empty($l['variante_id'])) {
                $vq = ProductoVariante::withoutGlobalScopes()->where('producto_id', $producto->id)->where('activo', true);
                $variante = ($bloquear ? $vq->lockForUpdate() : $vq)->find((int) $l['variante_id']);
                if (! $variante) {
                    throw ValidationException::withMessages(['carrito' => "La opción elegida de {$producto->nombre} ya no está disponible."]);
                }
                if ((int) $variante->stock < $cantidad) {
                    throw ValidationException::withMessages(['carrito' => (int) $variante->stock > 0
                        ? "Solo quedan {$variante->stock} de {$producto->nombre} ({$variante->nombre})."
                        : "{$producto->nombre} ({$variante->nombre}) se agotó."]);
                }
                [$unitario] = self::precios($variante->precio !== null ? (float) $variante->precio : (float) $producto->precio,
                    isset($variante->precio_oferta) && $variante->precio_oferta !== null ? (float) $variante->precio_oferta : null);
                $nombre = $producto->nombre . ' · ' . $variante->nombre;
            } else {
                if ($producto->tiene_variantes && ProductoVariante::withoutGlobalScopes()->where('producto_id', $producto->id)->where('activo', true)->exists()) {
                    throw ValidationException::withMessages(['carrito' => "Elige una opción de {$producto->nombre}."]);
                }
                if ((int) $producto->stock < $cantidad) {
                    throw ValidationException::withMessages(['carrito' => (int) $producto->stock > 0
                        ? "Solo quedan {$producto->stock} de {$producto->nombre}."
                        : "{$producto->nombre} se agotó."]);
                }
                [$unitario] = self::precios((float) $producto->precio, $producto->precio_oferta !== null ? (float) $producto->precio_oferta : null);
                $nombre = $producto->nombre;
            }

            $linea = round($unitario * $cantidad, 2);
            $subtotal += $linea;
            $lineas[] = [
                'producto' => $producto, 'variante' => $variante, 'producto_id' => $producto->id, 'variante_id' => $variante?->id,
                'nombre' => $nombre, 'variante_nombre' => $variante?->nombre, 'cantidad' => $cantidad, 'peso' => $peso,
                'unitario' => $unitario, 'subtotal' => $linea, 'kilo' => (bool) $kilo,
            ];
        }
        if (! $lineas) {
            throw ValidationException::withMessages(['carrito' => 'Tu carrito está vacío.']);
        }
        $subtotal = round($subtotal, 2);

        // Entrega
        $entrega = FormaEntrega::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activa', true)->find((int) ($datos['entrega_id'] ?? 0));
        $envio = 0.0;
        $zona = null;
        if ($entrega) {
            if ($subtotal < (float) ($entrega->monto_minimo ?? 0)) {
                throw ValidationException::withMessages(['entrega_id' => "{$entrega->nombre} es para pedidos desde " . number_format((float) $entrega->monto_minimo, 2) . '.']);
            }
            $envio = in_array($entrega->tipo, ['delivery', 'agencia'], true) ? (float) $entrega->costo : 0.0;
            if ($entrega->usa_zonas && Schema::hasTable('zonas_delivery')) {
                $zona = DB::table('zonas_delivery')->where('tenant_id', $tenantId)->where('activo', true)->where('id', (int) ($datos['zona_id'] ?? 0))->first();
                if (! $zona && ! empty($datos['exigir_zona'])) {
                    throw ValidationException::withMessages(['zona_id' => 'Elige tu zona para calcular el delivery.']);
                }
                if ($zona) {
                    $envio = (float) $zona->costo;
                }
            }
            if ($entrega->gratis_desde !== null && (float) $entrega->gratis_desde > 0 && $subtotal >= (float) $entrega->gratis_desde) {
                $envio = 0.0;
            }
        }

        // Cupón
        [$descCupon, $cupon] = Cupones::aplicar($tenantId, $datos['cupon'] ?? null, $subtotal, $bloquear);

        // Puntos (por el teléfono del cliente)
        $puntos = 0;
        $descPuntos = 0.0;
        $cliente = null;
        $disponibles = ['puntos' => 0, 'soles' => 0.0, 'saldo' => 0];
        $telefono = preg_replace('/\D/', '', (string) ($datos['telefono'] ?? ''));
        if (strlen($telefono) >= 9 && AjustesNegocio::leer($tenantId)['tienda_puntos']) {
            $cliente = CrmCliente::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where(fn ($q) => $q->where('telefono', $telefono)->orWhere('telefono', substr($telefono, -9)))->first();
            if ($cliente) {
                $programa = Fidelizacion::programa($tenantId);
                $max = Fidelizacion::maximoCanje((int) $cliente->puntos_saldo, max(0, $subtotal - $descCupon), $programa);
                $disponibles = $max + ['saldo' => (int) $cliente->puntos_saldo];
                if (! empty($datos['usar_puntos']) && $max['puntos'] > 0) {
                    $puntos = $max['puntos'];
                    $descPuntos = $max['soles'];
                }
            }
        }

        $descuento = round($descCupon + $descPuntos, 2);
        $total = round(max(0, $subtotal - $descuento) + $envio, 2);

        return [
            'lineas' => $lineas, 'subtotal' => $subtotal, 'envio' => round($envio, 2), 'descuento' => $descuento,
            'desc_cupon' => round($descCupon, 2), 'desc_puntos' => $descPuntos, 'puntos' => $puntos, 'puntos_disponibles' => $disponibles,
            'total' => $total, 'igv' => round($total - $total / 1.18, 2),
            'entrega' => $entrega, 'zona' => $zona, 'cupon' => $cupon, 'cliente' => $cliente,
        ];
    }

    public static function textoPeso(float $kg): string
    {
        // En gramos va sin decimales: no se recortan ceros (500 g no debe quedar en 5 g)
        return $kg < 1 ? number_format($kg * 1000, 0, '.', '') . ' g' : rtrim(rtrim(number_format($kg, 3, '.', ''), '0'), '.') . ' kg';
    }

    /** Resumen para el navegador (sin modelos). */
    public static function resumen(array $c): array
    {
        return [
            'lineas' => array_map(fn ($l) => array_intersect_key($l, array_flip(['producto_id', 'variante_id', 'nombre', 'cantidad', 'peso', 'unitario', 'subtotal'])), $c['lineas']),
            'subtotal' => $c['subtotal'], 'envio' => $c['envio'], 'descuento' => $c['descuento'],
            'desc_cupon' => $c['desc_cupon'], 'desc_puntos' => $c['desc_puntos'], 'puntos' => $c['puntos'],
            'puntos_disponibles' => $c['puntos_disponibles'], 'total' => $c['total'],
            'cupon' => $c['cupon'] ? ['codigo' => $c['cupon']->codigo] : null,
        ];
    }

    /** GEST-107: registra el pedido (canal tienda), descuenta stock, vincula al cliente y avisa por WhatsApp. */
    public static function crear(object $negocio, array $datos): Pedido
    {
        $config = TiendaConfig::de($negocio);
        $metodos = array_column($config['pagos'], 'clave');
        if (! in_array($datos['metodo_pago'], $metodos, true)) {
            throw ValidationException::withMessages(['metodo_pago' => 'Elige un método de pago disponible.']);
        }

        // GEST-104: si está cerrado, el pedido debe programarse para un horario de atención
        $programado = null;
        if (! empty($datos['programado_para'])) {
            $valido = collect($config['franjas'])->firstWhere('valor', $datos['programado_para']);
            if (! $valido) {
                throw ValidationException::withMessages(['programado_para' => 'Elige un horario disponible.']);
            }
            $programado = Carbon::createFromFormat('Y-m-d H:i', $datos['programado_para']);
        } elseif (! $config['abierta']) {
            throw ValidationException::withMessages(['programado_para' => $config['programar']
                ? 'Ahora estamos cerrados. Elige para cuándo quieres tu pedido.'
                : 'Ahora estamos cerrados. Vuelve en nuestro horario de atención.']);
        }

        $pedido = DB::transaction(function () use ($negocio, $datos, $programado) {
            $c = self::cotizar($negocio, $datos + ['exigir_zona' => true], true);
            $entrega = $c['entrega'];
            if (! $entrega) {
                throw ValidationException::withMessages(['entrega_id' => 'Elige una forma de entrega.']);
            }
            if (in_array($entrega->tipo, ['delivery', 'agencia'], true) && blank($datos['direccion'] ?? null)) {
                throw ValidationException::withMessages(['direccion' => 'Escribe la dirección de entrega.']);
            }

            $telefono = preg_replace('/\D/', '', (string) $datos['telefono']);
            $cliente = $c['cliente'] ?? CrmCliente::withoutGlobalScopes()->create([
                'tenant_id' => $negocio->id, 'nombre' => $datos['nombre'], 'telefono' => $telefono,
                'fuente' => 'landing', 'notas' => 'Cliente de la tienda online', 'activo' => true,
            ]);

            $numero = 'T-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
            $pedido = Pedido::withoutGlobalScopes()->create([
                'tenant_id' => $negocio->id, 'numero' => $numero, 'canal' => 'tienda', 'estado' => 'pendiente',
                'tipo' => $entrega->tipo === 'delivery' || $entrega->tipo === 'agencia' ? 'delivery' : 'recojo',
                'subtotal' => $c['subtotal'], 'descuento' => $c['descuento'], 'costo_envio' => $c['envio'], 'igv' => $c['igv'], 'total' => $c['total'],
                'direccion_entrega' => $datos['direccion'] ?? null,
                'lat_entrega' => $datos['lat'] ?? null, 'lng_entrega' => $datos['lng'] ?? null,
                'zona_id' => $c['zona']->id ?? null,
                'notas' => trim('Entrega: ' . $entrega->nombre . ($c['zona'] ? ' · Zona: ' . $c['zona']->nombre : '')
                    . ($programado ? ' · Programado: ' . $programado->format('d/m H:i') : '') . (! empty($datos['notas']) ? ' · ' . $datos['notas'] : '')),
                'metodo_pago' => $datos['metodo_pago'], 'pagado' => false,
                'cliente_id' => $cliente->id, 'cliente_nombre' => $datos['nombre'], 'cliente_telefono' => $telefono,
                'forma_entrega_id' => $entrega->id, 'programado_para' => $programado,
                'cupon_codigo' => $c['cupon']->codigo ?? null, 'puntos_usados' => $c['puntos'],
            ]);

            foreach ($c['lineas'] as $l) {
                PedidoItem::create([
                    'tenant_id' => $negocio->id, 'pedido_id' => $pedido->id, 'producto_id' => $l['producto_id'], 'variante_id' => $l['variante_id'],
                    'nombre' => $l['nombre'], 'variante_nombre' => $l['variante_nombre'], 'cantidad' => $l['cantidad'],
                    'precio_unit' => $l['unitario'], 'subtotal' => $l['subtotal'], 'peso_kg' => $l['peso'],
                ]);
                if ($l['variante']) {
                    $l['variante']->decrement('stock', $l['cantidad']);
                } elseif (! $l['kilo']) {
                    $l['producto']->decrement('stock', $l['cantidad']);
                }
            }

            if ($c['cupon']) {
                Cupones::usar($c['cupon']);
            }
            if ($c['puntos'] > 0 && $c['cliente']) {
                Fidelizacion::canjearEnPedido($c['cliente'], $pedido, $c['puntos']);
            }

            // GEST-85: si venía de un carrito abandonado, queda recuperado
            CheckoutAbandonado::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('recuperado', false)
                ->where(fn ($q) => $q->where('telefono', $telefono)->orWhere('telefono', $datos['telefono']))
                ->update(['recuperado' => true, 'recuperado_at' => now(), 'pedido_id' => $pedido->id]);

            return $pedido;
        });

        self::avisarWhatsapp($negocio, $pedido->fresh('items'), $config);

        return $pedido;
    }

    /** GEST-108: WhatsApp de confirmación al cliente (si el negocio conectó su número). */
    public static function avisarWhatsapp(object $negocio, Pedido $pedido, array $config): void
    {
        $conexion = \App\Models\WhatsappConexion::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('activo', true)->first();
        if (! $conexion) {
            return;
        }
        $s = $config['moneda']['simbolo'];
        $items = $pedido->items->map(fn ($i) => "• {$i->cantidad} x {$i->nombre}")->join("\n");
        $texto = "¡Hola {$pedido->cliente_nombre}! Recibimos tu pedido {$pedido->numero} en {$negocio->nombre_negocio} 🙌\n\n{$items}\n\nTotal: {$s} " . number_format((float) $pedido->total, 2)
            . ($pedido->programado_para ? "\nPara: " . $pedido->programado_para->locale('es')->translatedFormat('l d \\a \\l\\a\\s H:i') : '')
            . "\n\nTe avisamos cuando esté en camino. Sigue tu pedido aquí: " . route('tienda.gracias', [$negocio->slug, $pedido->numero]);
        try {
            $ok = WhatsAppNegocio::enviarTexto($conexion, (strlen($t = (string) $pedido->cliente_telefono) === 9 ? '51' . $t : $t), $texto, 'pedido_tienda');
            $pedido->forceFill(['whatsapp_ok' => (bool) $ok])->saveQuietly();
        } catch (\Throwable $e) {
            Log::info('WhatsApp de pedido no enviado', ['pedido' => $pedido->id, 'error' => $e->getMessage()]);
        }
    }
}
