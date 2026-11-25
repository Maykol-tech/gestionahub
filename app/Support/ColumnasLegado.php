<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Bases que vienen de una versión antigua del proyecto pueden tener columnas que el programa
 * ya no usa (por ejemplo repartidores.tipo_documento) y que son obligatorias sin valor por defecto.
 * Al crear un registro, esas columnas quedarían vacías y MySQL rechaza el INSERT.
 *
 * Aquí se llenan con un valor neutro, solo si:
 *  - la columna NO está en database/esquema_esperado.json (o sea, es de la versión antigua),
 *  - es obligatoria, no tiene valor por defecto y el registro no trae un valor.
 * En una base nueva no existen esas columnas y no se hace nada. No cambia la estructura de la base.
 */
class ColumnasLegado
{
    /** [tabla => [columna => valor]] calculado una vez por petición. */
    private static array $cache = [];

    private static ?array $esperado = null;

    public static function completar(Model $modelo): void
    {
        foreach (self::obligatoriasAntiguas($modelo->getTable()) as $columna => $valor) {
            if ($modelo->getAttribute($columna) === null) {
                $modelo->setAttribute($columna, $valor instanceof \Closure ? $valor() : $valor);
            }
        }
    }

    /** @return array<string, mixed> */
    public static function obligatoriasAntiguas(string $tabla): array
    {
        if (array_key_exists($tabla, self::$cache)) {
            return self::$cache[$tabla];
        }
        self::$esperado ??= (array) json_decode((string) @file_get_contents(database_path('esquema_esperado.json')), true);
        $conocidas = array_map(fn ($c) => strtolower(is_array($c) ? $c['name'] : $c), self::$esperado[$tabla] ?? []);
        if (! $conocidas) {
            return self::$cache[$tabla] = []; // tabla que el programa no describe: no se toca
        }

        $resultado = [];
        try {
            $columnas = Schema::getColumns($tabla);
        } catch (\Throwable) {
            return self::$cache[$tabla] = [];
        }
        // Columnas con índice único: el valor neutro debe ser distinto en cada registro
        $unicas = [];
        try {
            foreach (Schema::getIndexes($tabla) as $i) {
                if (! empty($i['unique']) && count($i['columns']) === 1) {
                    $unicas[] = strtolower($i['columns'][0]);
                }
            }
        } catch (\Throwable) {
        }
        foreach ($columnas as $c) {
            if (in_array(strtolower($c['name']), $conocidas, true) || $c['nullable'] || $c['default'] !== null
                || ! empty($c['auto_increment']) || ! empty($c['generation'])) {
                continue;
            }
            $valor = self::valorNeutro((string) ($c['type_name'] ?? ''), (string) ($c['type'] ?? ''));
            if ($valor === '' && in_array(strtolower($c['name']), $unicas, true)) {
                $nombre = strtolower($c['name']);
                $valor = str_contains($nombre, 'email') || str_contains($nombre, 'correo')
                    ? fn () => 'sin-correo-' . strtolower(\Illuminate\Support\Str::random(10)) . '@local.invalid'
                    : fn () => 'SN-' . strtoupper(\Illuminate\Support\Str::random(10));
            }
            $resultado[$c['name']] = $valor;
        }

        return self::$cache[$tabla] = $resultado;
    }

    private static function valorNeutro(string $tipo, string $completo): mixed
    {
        $tipo = strtolower($tipo);
        if ($tipo === 'enum' && preg_match("/enum\\('([^']*)'/i", $completo, $m)) {
            return $m[1]; // primera opción del enum
        }

        return match (true) {
            str_contains($tipo, 'int'), in_array($tipo, ['decimal', 'numeric', 'float', 'double', 'real', 'bit', 'boolean'], true) => 0,
            $tipo === 'date' => fn () => now()->toDateString(),
            in_array($tipo, ['datetime', 'timestamp'], true) => fn () => now(),
            $tipo === 'time' => '00:00:00',
            $tipo === 'json' => '[]',
            default => '',
        };
    }

    /** Solo para pruebas. */
    public static function olvidar(): void
    {
        self::$cache = [];
        self::$esperado = null;
    }
}
