<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Convencion del modelo de datos: entidades principales con borrado logico (deleted_at). */
return new class extends Migration
{
    private const TABLAS = ['repartidores', 'proveedores'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            if (Schema::hasTable($tabla) && ! Schema::hasColumn($tabla, 'deleted_at')) {
                Schema::table($tabla, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'deleted_at')) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropSoftDeletes());
            }
        }
    }
};
