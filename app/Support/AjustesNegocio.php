<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Ajustes del negocio (colores del panel y configuracion del bot).
 * Se guardan por tenant en storage/app/ajustes/tenant-{id}.json
 * hasta que exista una tabla de configuracion.
 */
class AjustesNegocio
{
    public const POR_DEFECTO = [
        'color_menu' => '#2B6EF6',
        'color_accion' => '#F47B20',
        'margen_objetivo' => 60,
        'margen_minimo' => 30,
        // GEST-75: programa de puntos
        'fid_activo' => true,
        'fid_puntos_por_sol' => 1,
        'fid_valor_punto' => 0.05,
        'fid_tope_pct' => 30,
        'fid_vence_meses' => 12,
        // GEST-82 a 84: asistente, menú por rubro y diseño de la web
        'onboarding_listo' => false,
        'modulos_ocultos' => [],
        'plantilla' => 'clasica',
        'color_secundario' => '#F47B20',
        'fuente' => 'Inter',
        'favicon_url' => '',
        'pie_texto' => '',
        // GEST-85: carrito abandonado
        'carrito_activo' => true,
        'carrito_minutos' => 30,
        'carrito_cupon' => '',
        'carrito_mensaje' => 'Hola {nombre}, dejaste tu pedido de {total} en {negocio}. Puedes terminarlo aquí: {enlace}',
        // GEST-86: minutos que dura el enlace para tomar un cupo liberado
        'espera_minutos' => 30,
        // GEST-78: boletín a inactivos
        'boletin_dias' => 60,
        'boletin_cupon' => '',
        // GEST-103/106: tienda online (moneda, métodos de pago activos, WhatsApp, redes, programar pedidos)
        'tienda_moneda' => 'PEN',
        'tienda_pagos' => ['efectivo', 'yape', 'plin'],
        'tienda_whatsapp' => '',
        'tienda_facebook' => '',
        'tienda_instagram' => '',
        'tienda_tiktok' => '',
        'tienda_programar' => true,
        'tienda_puntos' => true,
        'mp_access_token' => '', // Mercado Pago del negocio (cifrado)
        'mp_public_key' => '', // Public Key: muestra el formulario de tarjeta dentro de la tienda
        // Reseñas de Google: ID del lugar, clave de Google (cifrada) y si se muestran en la web
        'google_place_id' => '',
        'google_api_key' => '',
        'google_mostrar' => false,
        // GEST-62: API de pedidos. URL que recibe el aviso cuando un pedido cambia de estado y su clave de firma
        'api_webhook_url' => '',
        'api_webhook_secreto' => '',
        // Seguridad del acceso: intentos antes de bloquear, tiempo de bloqueo y bloqueo por inactividad
        'seg_intentos' => 5,
        'seg_bloqueo_valor' => 15,
        'seg_bloqueo_unidad' => 'minutos', // minutos | horas
        'seg_inactividad_activa' => true,
        'seg_inactividad_minutos' => 10,
        'bot_activo' => false,
        'bot_numero' => '',
        'bot_bienvenida' => '¡Hola! Gracias por escribirnos. ¿En qué podemos ayudarte?',
        'bot_horario' => 'Lunes a sábado, 9:00 a. m. a 9:00 p. m.',
        'bot_ausencia' => 'Ahora estamos fuera de horario. Te responderemos apenas abramos.',
    ];

    private static array $cache = [];

    /** Olvida lo leído en memoria (útil en pruebas y procesos largos). */
    public static function limpiarCache(): void
    {
        self::$cache = [];
    }

    private static function ruta(int $tenantId): string
    {
        return "ajustes/tenant-{$tenantId}.json";
    }

    public static function leer(int $tenantId): array
    {
        if (isset(self::$cache[$tenantId])) {
            return self::$cache[$tenantId];
        }

        $guardado = [];
        try {
            if (Storage::disk('local')->exists(self::ruta($tenantId))) {
                $guardado = json_decode(Storage::disk('local')->get(self::ruta($tenantId)), true) ?: [];
            }
        } catch (\Throwable $e) {
            $guardado = [];
        }

        return self::$cache[$tenantId] = array_merge(self::POR_DEFECTO, array_intersect_key($guardado, self::POR_DEFECTO));
    }

    public static function guardar(int $tenantId, array $valores): array
    {
        $nuevo = array_merge(self::leer($tenantId), array_intersect_key($valores, self::POR_DEFECTO));
        Storage::disk('local')->put(self::ruta($tenantId), json_encode($nuevo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::$cache[$tenantId] = $nuevo;
    }
}
