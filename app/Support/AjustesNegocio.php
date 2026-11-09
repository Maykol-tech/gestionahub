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
        'bot_activo' => false,
        'bot_numero' => '',
        'bot_bienvenida' => '¡Hola! Gracias por escribirnos. ¿En qué podemos ayudarte?',
        'bot_horario' => 'Lunes a sábado, 9:00 a. m. a 9:00 p. m.',
        'bot_ausencia' => 'Ahora estamos fuera de horario. Te responderemos apenas abramos.',
    ];

    private static array $cache = [];

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
