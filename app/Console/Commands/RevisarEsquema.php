<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Compara la base de datos con las tablas y columnas que espera el programa
 * (database/esquema_esperado.json). Solo lee: no cambia nada.
 */
class RevisarEsquema extends Command
{
    protected $signature = 'esquema:revisar';

    protected $description = 'Lista las tablas o columnas que le faltan a la base de datos (solo lectura)';

    public function handle(): int
    {
        $esperado = json_decode((string) file_get_contents(database_path('esquema_esperado.json')), true) ?: [];
        $faltan = [];
        foreach ($esperado as $tabla => $columnas) {
            if (! Schema::hasTable($tabla)) {
                $faltan[] = [$tabla, '(falta la tabla completa)'];
                continue;
            }
            $hay = array_map('strtolower', Schema::getColumnListing($tabla));
            foreach ($columnas as $c) {
                $c = is_array($c) ? $c['name'] : $c;
                if (! in_array(strtolower($c), $hay, true)) {
                    $faltan[] = [$tabla, $c];
                }
            }
        }

        if (! $faltan) {
            $this->info('Todo en orden: la base de datos tiene todas las tablas y columnas (' . count($esperado) . ' tablas revisadas).');

            return self::SUCCESS;
        }
        $this->warn('Faltan ' . count($faltan) . ' elementos. Envía esta lista para corregirlos con una migración que solo agrega:');
        $this->table(['Tabla', 'Columna que falta'], $faltan);
        // Columnas que ya tienen esas tablas: sirve para ver si un dato está guardado con otro nombre.
        $tablas = array_unique(array_column(array_filter($faltan, fn ($f) => ! str_starts_with($f[1], '(')), 0));
        $this->table(['Tabla', 'Columnas que ya tiene'], array_map(fn ($t) => [$t, implode(', ', Schema::getColumnListing($t))], $tablas));

        return self::FAILURE;
    }
}
