<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fidelizacion: saldo de puntos por cliente y el historial de movimientos
 * (ganados, canjes, ajustes y reversiones). Solo agrega lo que falte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_clientes', function (Blueprint $t) {
            if (! Schema::hasColumn('crm_clientes', 'puntos_saldo')) {
                $t->integer('puntos_saldo')->default(0);
            }
            if (! Schema::hasColumn('crm_clientes', 'puntos_historicos')) {
                $t->unsignedInteger('puntos_historicos')->default(0);
            }
        });

        if (! Schema::hasTable('fidelizacion_movimientos')) {
            Schema::create('fidelizacion_movimientos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('cliente_id')->index();
                $t->unsignedBigInteger('pedido_id')->nullable();
                $t->string('tipo', 20); // ganado, canje, ajuste, reversion
                $t->integer('puntos');
                $t->string('descripcion', 200)->nullable();
                $t->timestamps();
                // Un pedido solo puede dar puntos (y revertirlos) una vez
                $t->unique(['pedido_id', 'tipo']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fidelizacion_movimientos');
        Schema::table('crm_clientes', function (Blueprint $t) {
            $t->dropColumn(['puntos_saldo', 'puntos_historicos']);
        });
    }
};
