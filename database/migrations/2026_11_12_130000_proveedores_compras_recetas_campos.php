<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** GEST-69 a 72: dirección del proveedor, fecha esperada de la orden, proveedor habitual del insumo y ficha técnica de la receta. */
return new class extends Migration
{
    public function up(): void
    {
        $agregar = function (string $tabla, string $columna, callable $def) {
            if (Schema::hasTable($tabla) && ! Schema::hasColumn($tabla, $columna)) {
                Schema::table($tabla, fn (Blueprint $t) => $def($t));
            }
        };

        $agregar('proveedores', 'direccion', fn ($t) => $t->string('direccion', 200)->nullable());
        $agregar('proveedores', 'condicion_pago', fn ($t) => $t->string('condicion_pago', 30)->nullable());
        $agregar('ordenes_compra', 'fecha_esperada', fn ($t) => $t->date('fecha_esperada')->nullable());
        $agregar('ordenes_compra', 'vence_at', fn ($t) => $t->date('vence_at')->nullable());
        $agregar('insumos', 'proveedor_id', fn ($t) => $t->unsignedBigInteger('proveedor_id')->nullable()->index());
        $agregar('recetas', 'producto_id', fn ($t) => $t->unsignedBigInteger('producto_id')->nullable()->index());
        $agregar('recetas', 'pasos', fn ($t) => $t->text('pasos')->nullable());
        $agregar('recetas', 'alergenos', fn ($t) => $t->string('alergenos', 255)->nullable());
        $agregar('recetas', 'imagen_url', fn ($t) => $t->string('imagen_url', 500)->nullable());
        $agregar('receta_insumos', 'merma_pct', fn ($t) => $t->decimal('merma_pct', 5, 2)->default(0));
    }

    public function down(): void
    {
        // Sin cambios destructivos.
    }
};
