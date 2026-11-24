<?php

namespace App\Support;

/**
 * Módulos que no están en el prototipo (GestionaHub_Panel_v3). Por pedido del profesor quedan fuera del panel:
 * no salen en el menú y sus pantallas responden 404. El código y las tablas NO se borran:
 * para volver a mostrarlos basta MODULOS_EXTRA=true en el .env.
 */
class ModulosPrototipo
{
    /** Rutas del menú que no están en el prototipo (Seguridad se queda). */
    public const FUERA = [
        'admin.membresias' => 'Membresías',
        'admin.espera' => 'Lista de espera',
        'admin.pos' => 'Punto de venta',
        'admin.comprobantes' => 'Comprobantes',
        'admin.mesas' => 'Mesas y QR',
        'admin.cocina' => 'Cocina',
        'admin.resenas' => 'Reseñas',
        'admin.api' => 'API y conexiones',
    ];

    /** Pantallas públicas que dependen de esos módulos. */
    private const PUBLICAS = ['membresia.', 'publico.espera', 'publico.mesa', 'publico.resenas', 'admin.exportar.comprobantes'];

    public static function activo(): bool
    {
        return ! config('app.modulos_extra', false);
    }

    /** Lista de rutas a ocultar en el menú. */
    public static function ocultos(): array
    {
        return self::activo() ? array_keys(self::FUERA) : [];
    }

    public static function bloqueada(?string $ruta): bool
    {
        if (! $ruta || ! self::activo()) {
            return false;
        }
        foreach ([...array_keys(self::FUERA), ...self::PUBLICAS] as $base) {
            if ($ruta === $base || str_starts_with($ruta, rtrim($base, '.') . '.')) {
                return true;
            }
        }

        return false;
    }
}
