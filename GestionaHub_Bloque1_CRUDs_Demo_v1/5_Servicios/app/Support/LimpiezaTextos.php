<?php

namespace App\Support;

/**
 * Repara textos con doble codificacion UTF-8: "Ã³" en lugar de "ó", "â€”" en lugar de "—",
 * "ðŸ””" en lugar de "🔔". Solo cambia un tramo si al reinterpretarlo como Windows-1252 sale
 * UTF-8 valido; el resto del texto no se toca, asi que los acentos correctos quedan igual.
 */
class LimpiezaTextos
{
    /** Caracteres que aparecen al decodificar mal UTF-8 como Windows-1252 (no incluye ASCII). */
    private const TRAMO = '/[\x{0080}-\x{00FF}\x{0152}\x{0153}\x{0160}\x{0161}\x{0178}\x{017D}\x{017E}\x{0192}\x{02C6}\x{02DC}\x{2013}-\x{203A}\x{20AC}\x{2122}]+/u';

    public static function reparar(string $texto): string
    {
        return preg_replace_callback(self::TRAMO, fn (array $m) => self::tramo($m[0]), $texto) ?? $texto;
    }

    /** Busca desde el inicio del tramo el primer sufijo que, en bytes, sea UTF-8 valido. */
    private static function tramo(string $tramo): string
    {
        $largo = mb_strlen($tramo, 'UTF-8');

        for ($i = 0; $i < $largo; $i++) {
            $sufijo = mb_substr($tramo, $i, null, 'UTF-8');
            $bytes = self::aBytesCp1252($sufijo);

            if ($bytes !== null && mb_check_encoding($bytes, 'UTF-8')) {
                return mb_substr($tramo, 0, $i, 'UTF-8') . $bytes;
            }
        }

        return $tramo;
    }

    /** Null si algun caracter no existe en Windows-1252 (en ese caso no se arriesga la conversion). */
    private static function aBytesCp1252(string $texto): ?string
    {
        $bytes = mb_convert_encoding($texto, 'Windows-1252', 'UTF-8');

        return mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252') === $texto ? $bytes : null;
    }
}
