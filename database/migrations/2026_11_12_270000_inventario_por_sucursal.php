<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario por sucursal. El stock de productos sigue siendo el total del negocio;
 * esta tabla dice cuántas unidades están en cada sucursal (el resto queda en el local principal).
 * Solo agrega cosas; no borra nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_sucursales')) {
            Schema::create('stock_sucursales', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('producto_id');
                $t->unsignedBigInteger('sucursal_id');
                $t->integer('stock')->default(0);
                $t->timestamps();
                $t->unique(['producto_id', 'sucursal_id']);
            });
        }
        if (! Schema::hasTable('movimientos_sucursal')) {
            Schema::create('movimientos_sucursal', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('producto_id');
                $t->unsignedBigInteger('desde_id')->nullable(); // null = local principal
                $t->unsignedBigInteger('hacia_id')->nullable();
                $t->integer('cantidad');
                $t->string('motivo', 30)->default('traslado'); // traslado, venta, conteo
                $t->string('usuario', 120)->nullable();
                $t->timestamps();
            });
        }
        if (Schema::hasTable('ventas_pos') && ! Schema::hasColumn('ventas_pos', 'sucursal_id')) {
            Schema::table('ventas_pos', fn (Blueprint $t) => $t->unsignedBigInteger('sucursal_id')->nullable());
        }
    }

    public function down(): void
    {
        // No se borran tablas con datos del negocio.
    }
};
