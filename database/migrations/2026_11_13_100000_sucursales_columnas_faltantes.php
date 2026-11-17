<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Algunas bases ya tenían una tabla "sucursales" antigua, creada antes de la migración
 * 2026_11_06_210000, sin las columnas que usa el programa (por ejemplo "activo").
 * Solo agrega las columnas que faltan; no borra ni cambia nada de lo que ya existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sucursales')) {
            return;
        }

        $faltan = fn (string $c) => ! Schema::hasColumn('sucursales', $c);
        $nuevaActivo = $faltan('activo');

        Schema::table('sucursales', function (Blueprint $t) use ($faltan) {
            if ($faltan('tenant_id')) {
                $t->unsignedBigInteger('tenant_id')->nullable()->index();
            }
            if ($faltan('direccion')) {
                $t->string('direccion', 200)->nullable();
            }
            if ($faltan('telefono')) {
                $t->string('telefono', 20)->nullable();
            }
            if ($faltan('email')) {
                $t->string('email', 120)->nullable();
            }
            if ($faltan('horario')) {
                $t->string('horario', 120)->nullable();
            }
            if ($faltan('activo')) {
                $t->boolean('activo')->default(true);
            }
            if ($faltan('created_at')) {
                $t->timestamp('created_at')->nullable();
            }
            if ($faltan('updated_at')) {
                $t->timestamp('updated_at')->nullable();
            }
        });

        // Si la tabla antigua guardaba el estado con otro nombre, se respeta ese valor.
        if ($nuevaActivo) {
            if (Schema::hasColumn('sucursales', 'activa')) {
                DB::table('sucursales')->update(['activo' => DB::raw('activa')]);
            } elseif (Schema::hasColumn('sucursales', 'estado')) {
                DB::table('sucursales')->whereNotIn('estado', ['activa', 'activo', '1', 1])->update(['activo' => false]);
            }
        }
    }

    public function down(): void
    {
        // Solo agrega columnas: no se quitan para no perder datos.
    }
};
