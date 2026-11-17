<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Solo agrega: captura de pago que sube el cliente desde la tienda y de dónde vino el pago. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pagos_manuales')) {
            return;
        }
        Schema::table('pagos_manuales', function (Blueprint $table) {
            if (! Schema::hasColumn('pagos_manuales', 'captura')) {
                $table->string('captura')->nullable();
            }
            if (! Schema::hasColumn('pagos_manuales', 'origen')) {
                $table->string('origen', 20)->nullable(); // panel | tienda
            }
        });
    }

    public function down(): void
    {
        // No se borran columnas: los datos del negocio se conservan.
    }
};
