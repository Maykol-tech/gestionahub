<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** GEST-80: categoría y miniatura webp en la galería, y límite de fotos por plan. Solo agrega. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('galeria_items')) {
            Schema::table('galeria_items', function (Blueprint $t) {
                if (! Schema::hasColumn('galeria_items', 'categoria')) {
                    $t->string('categoria', 60)->nullable();
                }
                if (! Schema::hasColumn('galeria_items', 'miniatura_url')) {
                    $t->string('miniatura_url', 500)->nullable();
                }
                if (! Schema::hasColumn('galeria_items', 'archivo')) {
                    $t->string('archivo', 300)->nullable(); // ruta en el disco public, para borrar el archivo
                }
            });
        }

        if (Schema::hasTable('planes_saas') && ! Schema::hasColumn('planes_saas', 'max_fotos_galeria')) {
            Schema::table('planes_saas', fn (Blueprint $t) => $t->unsignedInteger('max_fotos_galeria')->nullable());
            foreach (['emprendedor' => 20, 'negocio' => 60, 'pro' => 200] as $slug => $max) {
                DB::table('planes_saas')->where('slug', $slug)->update(['max_fotos_galeria' => $max]);
            }
        }
    }

    public function down(): void
    {
        // No se borran columnas con datos del negocio.
    }
};
