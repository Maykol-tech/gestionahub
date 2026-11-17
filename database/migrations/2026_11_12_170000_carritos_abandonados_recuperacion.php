<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** GEST-85: enlace para recuperar el carrito, cupón, y seguimiento de lo recuperado. Solo agrega. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checkout_abandonados')) {
            Schema::table('checkout_abandonados', function (Blueprint $t) {
                if (! Schema::hasColumn('checkout_abandonados', 'token')) {
                    $t->string('token', 40)->nullable()->unique();
                }
                if (! Schema::hasColumn('checkout_abandonados', 'cupon')) {
                    $t->string('cupon', 40)->nullable();
                }
                if (! Schema::hasColumn('checkout_abandonados', 'recordatorio_ok')) {
                    $t->boolean('recordatorio_ok')->default(false);
                }
                if (! Schema::hasColumn('checkout_abandonados', 'abierto_at')) {
                    $t->timestamp('abierto_at')->nullable();
                }
                if (! Schema::hasColumn('checkout_abandonados', 'recuperado_at')) {
                    $t->timestamp('recuperado_at')->nullable();
                }
                if (! Schema::hasColumn('checkout_abandonados', 'pedido_id')) {
                    $t->unsignedBigInteger('pedido_id')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // No se borran columnas con datos del negocio.
    }
};
