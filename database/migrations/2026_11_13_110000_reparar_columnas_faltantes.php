<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bases que vienen de una versión antigua: algunas tablas ya existían cuando se agregaron
 * las migraciones nuevas (que no las vuelven a crear) y les faltan columnas.
 * Agrega cada columna que falte según database/esquema_esperado.json.
 * Solo agrega (siempre como opcional, con su valor por defecto); no borra ni cambia nada existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $archivo = database_path('esquema_esperado.json');
        if (! is_file($archivo)) {
            return;
        }
        foreach ((array) json_decode((string) file_get_contents($archivo), true) as $tabla => $columnas) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }
            $hay = array_map('strtolower', Schema::getColumnListing($tabla));
            $faltan = array_values(array_filter($columnas, fn ($c) => is_array($c) && $c['name'] !== 'id' && ! in_array(strtolower($c['name']), $hay, true)));
            if (! $faltan) {
                continue;
            }
            Schema::table($tabla, function (Blueprint $t) use ($faltan) {
                foreach ($faltan as $c) {
                    $col = match (true) {
                        $c['type'] === 'tinyint(1)' => $t->boolean($c['name']),
                        $c['type'] === 'integer' => $t->bigInteger($c['name']),
                        $c['type'] === 'numeric' => $t->decimal($c['name'], 14, 3),
                        $c['type'] === 'text' => $t->text($c['name']),
                        $c['type'] === 'date' => $t->date($c['name']),
                        $c['type'] === 'datetime' => $t->dateTime($c['name']),
                        default => $t->string($c['name'], 255),
                    };
                    $col->nullable();
                    $defecto = $c['default'] ?? null;
                    if ($defecto !== null && $c['type'] !== 'text' && stripos((string) $defecto, 'current_timestamp') === false) {
                        $col->default(trim((string) $defecto, "'\""));
                    }
                }
            });
            $this->copiarDatosAntiguos($tabla, array_column($faltan, 'name'));
        }
    }

    /** Columnas nuevas que en la tabla antigua se llamaban de otra forma: se copia el valor que ya había. */
    private const EQUIVALENTES = [
        'repartidores' => ['nombre' => 'nombre_completo'],
        'campanas_publicidad' => ['canal' => 'plataforma', 'activo' => 'activa'],
        'sucursales' => ['activo' => 'activa'],
    ];

    private function copiarDatosAntiguos(string $tabla, array $agregadas): void
    {
        foreach (self::EQUIVALENTES[$tabla] ?? [] as $nueva => $antigua) {
            if (! in_array($nueva, $agregadas, true) || ! Schema::hasColumn($tabla, $antigua)) {
                continue;
            }
            DB::table($tabla)->whereNotNull($antigua)->update([$nueva => DB::raw($antigua)]);
        }
    }

    public function down(): void
    {
        // Solo agrega columnas: no se quitan para no perder datos.
    }
};
