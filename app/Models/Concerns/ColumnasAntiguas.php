<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;

/**
 * Bases que vienen de una versión antigua tienen columnas con otro nombre (por ejemplo
 * repartidores.nombre_completo en vez de nombre) que son obligatorias y no tienen valor por defecto.
 * Al guardar, se copia el valor de la columna nueva a la antigua para que el INSERT no falle.
 * En una base nueva esas columnas no existen y esto no hace nada.
 *
 * El modelo define: protected const COLUMNAS_ANTIGUAS = ['nueva' => 'antigua'];
 */
trait ColumnasAntiguas
{
    protected static function bootColumnasAntiguas(): void
    {
        static::saving(function ($modelo) {
            $tabla = $modelo->getTable();
            foreach (static::COLUMNAS_ANTIGUAS as $nueva => $antigua) {
                if ($modelo->getAttribute($nueva) !== null && Schema::hasColumn($tabla, $antigua)) {
                    $modelo->setAttribute($antigua, $modelo->getAttribute($nueva));
                }
            }
        });
    }
}
