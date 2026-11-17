<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El modelo Servicio usaba la tabla "servicios_citas", que ninguna migracion crea.
 * Desde ahora usa "servicios" (la que referencia citas.servicio_id).
 * Si existe "servicios_citas" con datos, se copian a "servicios" sin borrar nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('servicios')) {
            Schema::create('servicios', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->text('descripcion')->nullable();
                $t->decimal('precio', 10, 2)->default(0);
                $t->unsignedSmallInteger('duracion_min')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasColumn('servicios', 'color')) {
            Schema::table('servicios', function (Blueprint $t) {
                $t->string('color', 20)->nullable();
            });
        }

        if (Schema::hasTable('servicios_citas') && DB::table('servicios')->count() === 0) {
            foreach (DB::table('servicios_citas')->get() as $s) {
                $fila = (array) $s;
                DB::table('servicios')->insert(array_intersect_key($fila, array_flip(
                    ['id', 'tenant_id', 'nombre', 'descripcion', 'precio', 'duracion_min', 'activo', 'color', 'created_at', 'updated_at']
                )));
            }
        }
    }

    public function down(): void
    {
        // No se borra nada: la columna color puede quedarse.
    }
};
