<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-104..112: tienda online completa. Solo agrega columnas y tablas; no borra ni cambia datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // GEST-104/106/107: pedido programado, datos del cliente, cupón, puntos y zona
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos', 'programado_para')) $t->timestamp('programado_para')->nullable();
            if (! Schema::hasColumn('pedidos', 'cliente_nombre')) $t->string('cliente_nombre', 120)->nullable();
            if (! Schema::hasColumn('pedidos', 'cliente_telefono')) $t->string('cliente_telefono', 20)->nullable();
            if (! Schema::hasColumn('pedidos', 'forma_entrega_id')) $t->unsignedBigInteger('forma_entrega_id')->nullable();
            if (! Schema::hasColumn('pedidos', 'cupon_codigo')) $t->string('cupon_codigo', 40)->nullable();
            if (! Schema::hasColumn('pedidos', 'puntos_usados')) $t->unsignedInteger('puntos_usados')->default(0);
            if (! Schema::hasColumn('pedidos', 'whatsapp_ok')) $t->boolean('whatsapp_ok')->default(false);
        });

        // GEST-105/111: línea con variante y peso
        Schema::table('pedido_items', function (Blueprint $t) {
            if (! Schema::hasColumn('pedido_items', 'variante_nombre')) $t->string('variante_nombre', 120)->nullable();
            if (! Schema::hasColumn('pedido_items', 'peso_kg')) $t->decimal('peso_kg', 8, 3)->nullable();       // pedido
            if (! Schema::hasColumn('pedido_items', 'peso_real_kg')) $t->decimal('peso_real_kg', 8, 3)->nullable(); // despachado
        });

        // GEST-105: presentaciones del producto por kilo (250 g, 500 g, 1 kg) o peso libre
        if (Schema::hasTable('producto_kilo')) {
            Schema::table('producto_kilo', function (Blueprint $t) {
                if (! Schema::hasColumn('producto_kilo', 'presentaciones')) $t->json('presentaciones')->nullable(); // [0.25, 0.5, 1]
                if (! Schema::hasColumn('producto_kilo', 'peso_libre')) $t->boolean('peso_libre')->default(true);
                if (! Schema::hasColumn('producto_kilo', 'peso_minimo')) $t->decimal('peso_minimo', 6, 3)->default(0.1);
            });
        }

        // GEST-109: formas de entrega con mínimo, zonas y descripción (tipos: delivery, recojo, agencia, local)
        Schema::table('formas_entrega', function (Blueprint $t) {
            if (! Schema::hasColumn('formas_entrega', 'monto_minimo')) $t->decimal('monto_minimo', 10, 2)->default(0);
            if (! Schema::hasColumn('formas_entrega', 'gratis_desde')) $t->decimal('gratis_desde', 10, 2)->nullable();
            if (! Schema::hasColumn('formas_entrega', 'usa_zonas')) $t->boolean('usa_zonas')->default(false);
            if (! Schema::hasColumn('formas_entrega', 'descripcion')) $t->string('descripcion', 200)->nullable();
        });

        // GEST-112: SEO por producto y categoría
        Schema::table('productos', function (Blueprint $t) {
            if (! Schema::hasColumn('productos', 'meta_titulo')) $t->string('meta_titulo', 70)->nullable();
            if (! Schema::hasColumn('productos', 'meta_descripcion')) $t->string('meta_descripcion', 170)->nullable();
            if (! Schema::hasColumn('productos', 'destacado')) $t->boolean('destacado')->default(false); // GEST-110
        });
        Schema::table('categorias', function (Blueprint $t) {
            if (! Schema::hasColumn('categorias', 'meta_descripcion')) $t->string('meta_descripcion', 170)->nullable();
        });

        // GEST-106: cupones de la tienda
        if (! Schema::hasTable('cupones_tienda')) {
            Schema::create('cupones_tienda', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('codigo', 40);
                $t->string('tipo', 10)->default('porcentaje'); // porcentaje | monto
                $t->decimal('valor', 10, 2);
                $t->decimal('minimo', 10, 2)->default(0);
                $t->unsignedInteger('usos_max')->nullable();
                $t->unsignedInteger('usos')->default(0);
                $t->date('vence')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
                $t->unique(['tenant_id', 'codigo']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cupones_tienda');
    }
};
