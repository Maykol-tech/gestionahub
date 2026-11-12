<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Repartidores: placa del vehiculo (CRUD de repartidores). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('repartidores') || Schema::hasColumn('repartidores', 'placa')) {
            return;
        }

        Schema::table('repartidores', function (Blueprint $t) {
            $t->string('placa', 10)->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('repartidores') && Schema::hasColumn('repartidores', 'placa')) {
            Schema::table('repartidores', fn (Blueprint $t) => $t->dropColumn('placa'));
        }
    }
};
