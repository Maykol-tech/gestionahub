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

    /** GEST-104: próxima vez que abre (hoy más tarde o los días siguientes). Null si nunca abre. */
    public static function proximaApertura(?array $horarios, ?Carbon $desde = null): ?Carbon
    {
        $desde = $desde ?? now();
        $lista = collect($horarios ? self::normalizar($horarios) : self::porDefecto());
        for ($i = 0; $i < 8; $i++) {
            $dia = $desde->copy()->addDays($i);
            $h = $lista->firstWhere('dia', (int) $dia->dayOfWeek);
            if (! $h || ! $h['activo']) {
                continue;
            }
            $abre = $dia->copy()->setTimeFromTimeString($h['abre']);
            $cierra = $dia->copy()->setTimeFromTimeString($h['cierra']);
            if ($i === 0 && $desde->greaterThanOrEqualTo($cierra)) {
                continue;
            }
            return $abre->greaterThan($desde) ? $abre : $desde->copy();
        }

        return null;
    }

    /**
     * GEST-104: horarios disponibles para programar un pedido (cada 30 min, próximos días abiertos).
     * Devuelve [['valor' => 'Y-m-d H:i', 'dia' => 'Hoy', 'hora' => '13:30'], ...]
     */
    public static function franjas(?array $horarios, int $dias = 3, int $margenMin = 45, ?Carbon $desde = null): array
    {
        $desde = ($desde ?? now())->copy();
        $minimo = $desde->copy()->addMinutes($margenMin);
        $lista = collect($horarios ? self::normalizar($horarios) : self::porDefecto());
        $franjas = [];
        $diasConHorario = 0;
        for ($i = 0; $i < 8 && $diasConHorario < $dias; $i++) {
            $dia = $desde->copy()->startOfDay()->addDays($i);
            $h = $lista->firstWhere('dia', (int) $dia->dayOfWeek);
            if (! $h || ! $h['activo']) {
                continue;
            }
            $t = $dia->copy()->setTimeFromTimeString($h['abre'])->addMinutes(30);
            $fin = $dia->copy()->setTimeFromTimeString($h['cierra']);
            $agrego = false;
            while ($t->lessThanOrEqualTo($fin)) {
                if ($t->greaterThanOrEqualTo($minimo)) {
                    $franjas[] = [
                        'valor' => $t->format('Y-m-d H:i'),
                        'dia' => $i === 0 ? 'Hoy' : ($i === 1 ? 'Mañana' : ucfirst($t->locale('es')->translatedFormat('l d'))),
                        'hora' => $t->format('H:i'),
                    ];
                    $agrego = true;
                }
                $t->addMinutes(30);
            }
            if ($agrego) {
                $diasConHorario++;
            }
        }

        return $franjas;
    }
}
