<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Bases antiguas: negocios sin dirección de tienda (slug vacío) no podían abrir su tienda pública.
 * Les pone una a partir del nombre del negocio ("El Clasico" → el-clasico). Solo llena los vacíos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasColumn('tenants', 'slug')) {
            return;
        }
        $nombre = Schema::hasColumn('tenants', 'nombre_negocio') ? 'nombre_negocio' : null;
        foreach (DB::table('tenants')->where(fn ($q) => $q->whereNull('slug')->orWhere('slug', ''))->get() as $t) {
            $base = Str::slug((string) ($nombre ? $t->$nombre : '')) ?: 'negocio-' . $t->id;
            $slug = $base;
            for ($i = 2; DB::table('tenants')->where('slug', $slug)->where('id', '!=', $t->id)->exists(); $i++) {
                $slug = $base . '-' . $i;
            }
            DB::table('tenants')->where('id', $t->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        // Solo llena datos vacíos: no se quita nada.
    }
};
