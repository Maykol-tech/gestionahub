<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes: etiquetas propias (VIP, mayorista...), cumpleaños y el código
 * de su tarjeta digital de puntos. Solo agrega columnas; no borra nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_clientes')) {
            return;
        }
        Schema::table('crm_clientes', function (Blueprint $t) {
            if (! Schema::hasColumn('crm_clientes', 'etiquetas')) {
                $t->text('etiquetas')->nullable();
            }
            if (! Schema::hasColumn('crm_clientes', 'cumpleanos')) {
                $t->date('cumpleanos')->nullable();
            }
            if (! Schema::hasColumn('crm_clientes', 'tarjeta_token')) {
                $t->string('tarjeta_token', 48)->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        // A propósito no se borran columnas con datos del negocio.
    }
};
