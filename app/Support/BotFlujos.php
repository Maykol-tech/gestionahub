<?php

namespace App\Support;

use App\Models\BotFlujo;

/** Motor de respuestas del bot. Devuelve el texto del primer flujo activo que coincida con el mensaje. */
class BotFlujos
{
    public static function respuesta(int $tenantId, string $mensaje): ?string
    {
        $texto = self::normalizar($mensaje);

        if ($texto === '') {
            return null;
        }

        $flujos = BotFlujo::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('activo', true)
            ->orderBy('orden')->orderBy('id')
            ->get();

        foreach ($flujos as $flujo) {
            $disparador = self::normalizar($flujo->disparador);

            if ($disparador === '') {
                continue;
            }

            $coincide = $flujo->coincidencia === 'exacta'
                ? $texto === $disparador
                : str_contains($texto, $disparador);

            if ($coincide) {
                return $flujo->respuesta;
            }
        }

        return null;
    }

    /** Minusculas, sin acentos y con espacios simples, para comparar sin errores de tipeo de tildes. */
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);

        return preg_replace('/\s+/u', ' ', $texto) ?? '';
    }
}
