<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cuentas por pagar: cuándo y cómo se pagó una compra a crédito. Solo agrega columnas. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ordenes_compra')) {
            return;
        }
        Schema::table('ordenes_compra', function (Blueprint $t) {
            if (! Schema::hasColumn('ordenes_compra', 'pagada_at')) {
                $t->timestamp('pagada_at')->nullable();
            }
            if (! Schema::hasColumn('ordenes_compra', 'pago_metodo')) {
                $t->string('pago_metodo', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        // No se borran columnas con datos del negocio.
    }
};
