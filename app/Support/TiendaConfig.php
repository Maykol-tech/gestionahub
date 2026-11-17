<?php

namespace App\Support;

use App\Models\FormaEntrega;
use App\Models\TiendaContenido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-103: configuración de la tienda de un negocio (TENANT_CONFIG).
 * Todo lo que la plantilla muestra sale de aquí; nada del negocio va escrito en la plantilla.
 */
class TiendaConfig
{
    public const METODOS_PAGO = [
        'efectivo' => ['nombre' => 'Efectivo', 'icono' => '💵', 'detalle' => 'Pagas al recibir'],
        'yape' => ['nombre' => 'Yape', 'icono' => '🟣', 'detalle' => 'Yapea al número del negocio'],
        'plin' => ['nombre' => 'Plin', 'icono' => '🔵', 'detalle' => 'Al número del negocio'],
        'transferencia' => ['nombre' => 'Transferencia', 'icono' => '🏦', 'detalle' => 'Te enviamos la cuenta por WhatsApp'],
        'tarjeta' => ['nombre' => 'Tarjeta (POS)', 'icono' => '💳', 'detalle' => 'Llevamos el POS al entregar'],
        'mercadopago' => ['nombre' => 'Tarjeta en línea', 'icono' => '💳', 'detalle' => 'Pagas ahora con tarjeta en Mercado Pago'],
    ];

    public const MONEDAS = ['PEN' => 'S/', 'USD' => 'US$'];

    public static function negocioPorSlug(string $slug): ?object
    {
        return DB::table('tenants')->where('slug', $slug)->whereIn('estado', ['activo', 'trial'])->first();
    }

    public static function horarios(object $negocio): ?array
    {
        $h = $negocio->horarios ?? null;

        return $h ? (is_array($h) ? $h : json_decode((string) $h, true)) : null;
    }

    public static function de(object $negocio): array
    {
        $a = AjustesNegocio::leer((int) $negocio->id);
        $diseno = DisenoNegocio::de($negocio);
        $horarios = self::horarios($negocio);
        $abierta = Horarios::abiertoAhora($horarios);
        $contenido = TiendaContenido::todo((int) $negocio->id);

        $pagos = array_values(array_filter((array) $a['tienda_pagos'], fn ($m) => isset(self::METODOS_PAGO[$m])
            && ($m !== 'mercadopago' || MercadoPagoTienda::activo((int) $negocio->id))));
        if (! $pagos) {
            $pagos = ['efectivo'];
        }

        $whatsapp = preg_replace('/\D/', '', (string) ($a['tienda_whatsapp'] ?: ($negocio->telefono ?? '')));
        if (strlen($whatsapp) === 9) {
            $whatsapp = '51' . $whatsapp;
        }

        $zonas = Schema::hasTable('zonas_delivery')
            ? DB::table('zonas_delivery')->where('tenant_id', $negocio->id)->where('activo', true)->orderBy('orden')->orderBy('id')
                ->get(['id', 'nombre', 'costo', 'tiempo_min'])->map(fn ($z) => ['id' => $z->id, 'nombre' => $z->nombre, 'costo' => (float) $z->costo, 'tiempo' => $z->tiempo_min ? $z->tiempo_min . ' min' : null])->values()->all()
            : [];

        return [
            'negocio' => [
                'id' => (int) $negocio->id,
                'nombre' => $negocio->nombre_negocio,
                'slug' => $negocio->slug,
                'descripcion' => $negocio->descripcion ?? null,
                'direccion' => $negocio->direccion ?? null,
                'telefono' => $negocio->telefono ?? null,
                'rubro' => $negocio->rubro ?? null,
                'logo' => $diseno['logo_url'],
            ],
            'diseno' => $diseno,
            'moneda' => ['codigo' => $a['tienda_moneda'], 'simbolo' => self::MONEDAS[$a['tienda_moneda']] ?? 'S/'],
            'horarios' => $horarios ? Horarios::normalizar($horarios) : Horarios::porDefecto(),
            'abierta' => $abierta,
            'proxima_apertura' => $abierta ? null : Horarios::proximaApertura($horarios)?->locale('es')->translatedFormat('l H:i'),
            'programar' => (bool) $a['tienda_programar'],
            'franjas' => $a['tienda_programar'] ? Horarios::franjas($horarios) : [],
            'entregas' => FormaEntrega::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('activa', true)
                ->orderBy('orden')->orderBy('id')->get()->map(fn ($e) => [
                    'id' => $e->id, 'nombre' => $e->nombre, 'tipo' => $e->tipo, 'costo' => (float) $e->costo,
                    'tiempo' => $e->tiempo_estimado, 'minimo' => (float) ($e->monto_minimo ?? 0),
                    'gratis_desde' => $e->gratis_desde !== null ? (float) $e->gratis_desde : null,
                    'usa_zonas' => (bool) ($e->usa_zonas ?? false) && count($zonas) > 0,
                    'descripcion' => $e->descripcion ?? null,
                    'pide_direccion' => in_array($e->tipo, ['delivery', 'agencia'], true),
                ])->values()->all(),
            'zonas' => $zonas,
            'pagos' => array_map(fn ($m) => ['clave' => $m] + self::METODOS_PAGO[$m] + [
                'numero' => in_array($m, ['yape', 'plin'], true) ? ($negocio->{$m . '_numero'} ?? null) : null,
                'qr' => $m === 'yape' ? ($negocio->yape_qr ?? null) : null,
            ], $pagos),
            'whatsapp' => $whatsapp ?: null,
            'redes' => array_filter([
                'facebook' => $a['tienda_facebook'], 'instagram' => $a['tienda_instagram'], 'tiktok' => $a['tienda_tiktok'],
            ]),
            'puntos' => (bool) $a['tienda_puntos'] && (bool) $a['fid_activo'],
            'contenido' => $contenido,
        ];
    }
}
