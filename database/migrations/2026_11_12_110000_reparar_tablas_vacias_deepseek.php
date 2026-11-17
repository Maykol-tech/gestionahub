<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Repara las tablas "stub" que crearon versiones anteriores (solo id y timestamps, por ejemplo `gastos`).
 * Como ya existían, las migraciones reales se saltaban y los módulos fallaban por columnas faltantes.
 *
 * No borra nada: la tabla incompleta se renombra a {tabla}_stub_respaldo y se vuelve a ejecutar
 * la migración real que crea esa tabla. Si la tabla tiene filas, solo se registra un aviso en el log.
 */
return new class extends Migration
{
    public function up(): void
    {
        $archivos = collect(File::files(database_path('migrations')))
            ->filter(fn ($f) => $f->getFilename() !== basename(__FILE__))
            ->sortBy(fn ($f) => $f->getFilename());

        foreach ($archivos as $archivo) {
            $codigo = File::get($archivo->getPathname());
            preg_match_all("/Schema::create\\('([a-z0-9_]+)'/", $codigo, $m);

            $reparar = false;
            foreach (array_unique($m[1]) as $tabla) {
                if (! Schema::hasTable($tabla)) {
                    continue;
                }
                $columnas = array_map('strtolower', Schema::getColumnListing($tabla));
                $esStub = count(array_diff($columnas, ['id', 'created_at', 'updated_at', 'tenant_id'])) === 0;
                if (! $esStub) {
                    continue;
                }
                if (DB::table($tabla)->exists()) {
                    Log::warning("Tabla {$tabla} incompleta pero con datos: revisar a mano.");
                    continue;
                }
                $respaldo = substr($tabla, 0, 40) . '_stub_respaldo';
                if (Schema::hasTable($respaldo)) {
                    $respaldo .= '_' . date('His');
                }
                Schema::rename($tabla, $respaldo);
                $reparar = true;
            }

            if ($reparar) {
                try {
                    $migracion = require $archivo->getPathname();
                    if (is_object($migracion) && method_exists($migracion, 'up')) {
                        $migracion->up();
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo re-ejecutar ' . $archivo->getFilename() . ': ' . $e->getMessage());
                }
            }
        }

        // Segunda pasada: migraciones que agregan columnas con hasColumn. Si corrieron cuando la tabla
        // aún no existía, se saltaron; se vuelven a ejecutar (son seguras de repetir).
        foreach ($archivos as $archivo) {
            $codigo = File::get($archivo->getPathname());
            if (! str_contains($codigo, 'Schema::table(') || ! str_contains($codigo, 'hasColumn')) {
                continue;
            }
            try {
                $migracion = require $archivo->getPathname();
                if (is_object($migracion) && method_exists($migracion, 'up')) {
                    $migracion->up();
                }
            } catch (\Throwable $e) {
                Log::info('Repaso de columnas omitido en ' . $archivo->getFilename() . ': ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Nada: no se deshace una reparación.
    }
};
