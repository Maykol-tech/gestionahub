<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-19: Galeria de imagenes de producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();
            $table->string('ruta', 255);                      // Ruta en el disco "public"
            $table->string('alt', 150)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'producto_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_imagenes');
    }
};
