<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Horarios de atencion del negocio: una lista de 7 dias (0 = domingo ... 6 = sabado),
 * cada uno con "activo", "abre" y "cierra" en formato HH:MM.
 */
class Horarios
{
    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo'];

    /** Horario por defecto: lunes a sabado de 9 a 19, domingo cerrado. */
    public static function porDefecto(): array
    {
        return collect(self::DIAS)->map(fn ($nombre, $dia) => [
            'dia' => $dia,
            'nombre' => $nombre,
            'activo' => $dia !== 0,
            'abre' => '09:00',
            'cierra' => '19:00',
        ])->values()->all();
    }

    /** Normaliza lo que llega del formulario o de la base de datos. */
    public static function normalizar(array $horarios): array
    {
        return collect($horarios)->map(fn ($h) => [
            'dia' => (int) $h['dia'],
            'nombre' => self::DIAS[(int) $h['dia']] ?? '',
            'activo' => (bool) ($h['activo'] ?? false),
            'abre' => substr((string) ($h['abre'] ?? '09:00'), 0, 5),
            'cierra' => substr((string) ($h['cierra'] ?? '19:00'), 0, 5),
        ])->sortBy('dia')->values()->all();
    }

    /** Dice si el negocio esta abierto en el momento indicado. */
    public static function abiertoAhora(?array $horarios, ?Carbon $ahora = null): bool
    {
        $ahora = $ahora ?? now();
        $lista = $horarios ? self::normalizar($horarios) : self::porDefecto();
        $dia = collect($lista)->firstWhere('dia', (int) $ahora->dayOfWeek);

        if (! $dia || ! $dia['activo']) {
            return false;
        }

        $hora = $ahora->format('H:i');

        return $hora >= $dia['abre'] && $hora < $dia['cierra'];
    }
}
