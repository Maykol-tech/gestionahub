<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-76 a 78: puntos en el POS, canje como descuento, saldo en cada movimiento
 * y registro del boletín a clientes inactivos. Solo agrega; no borra nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fidelizacion_movimientos')) {
            Schema::table('fidelizacion_movimientos', function (Blueprint $t) {
                if (! Schema::hasColumn('fidelizacion_movimientos', 'venta_pos_id')) {
                    $t->unsignedBigInteger('venta_pos_id')->nullable()->index();
                }
                if (! Schema::hasColumn('fidelizacion_movimientos', 'saldo_resultante')) {
                    $t->integer('saldo_resultante')->nullable();
                }
            });
        }

        if (Schema::hasTable('ventas_pos')) {
            Schema::table('ventas_pos', function (Blueprint $t) {
                if (! Schema::hasColumn('ventas_pos', 'cliente_id')) {
                    $t->unsignedBigInteger('cliente_id')->nullable()->index();
                }
                if (! Schema::hasColumn('ventas_pos', 'descuento')) {
                    $t->decimal('descuento', 10, 2)->default(0);
                }
                if (! Schema::hasColumn('ventas_pos', 'puntos_usados')) {
                    $t->integer('puntos_usados')->default(0);
                }
            });
        }

        if (! Schema::hasTable('boletin_envios')) {
            Schema::create('boletin_envios', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('cliente_id')->index();
                $t->string('telefono', 30)->nullable();
                $t->string('cupon', 40)->nullable();
                $t->boolean('enviado')->default(false);
                $t->timestamp('volvio_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('boletin_envios');
    }
};
