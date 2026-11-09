<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-18: Variantes de producto (talla, color, sabor, presentacion...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre', 120);                    // Ej: "Rojo / M"
            $table->json('atributos')->nullable();            // Ej: {"color":"Rojo","talla":"M"}
            $table->string('sku', 60)->nullable();
            $table->decimal('precio', 10, 2)->nullable();     // null = usa el precio del producto
            $table->decimal('precio_oferta', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(0);      // Para alertas de stock (GEST-20)
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'producto_id']);
            $table->unique(['tenant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_variantes');
    }
};
