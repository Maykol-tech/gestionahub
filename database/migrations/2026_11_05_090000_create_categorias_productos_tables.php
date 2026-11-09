<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de categorias y productos (Dia 3).
 * Si ya existen en tu base local, esta migracion no hace nada.
 * En una base nueva (Docker) las crea con las columnas que usan los modelos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('nombre', 80);
                $table->string('slug', 100);
                $table->string('imagen_url', 255)->nullable();
                $table->unsignedSmallInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'slug']);
            });
        }

        if (! Schema::hasTable('productos')) {
            Schema::create('productos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
                $table->string('nombre', 150);
                $table->string('slug', 170);
                $table->text('descripcion')->nullable();
                $table->decimal('precio', 10, 2)->default(0);
                $table->decimal('precio_oferta', 10, 2)->nullable();
                $table->integer('stock')->default(0);
                $table->integer('stock_minimo')->default(0);
                $table->string('sku', 60)->nullable();
                $table->string('imagen_url', 255)->nullable();
                $table->boolean('tiene_variantes')->default(false);
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'slug']);
                $table->index(['tenant_id', 'categoria_id']);
            });
        }
    }

    public function down(): void
    {
        // A proposito no se borran: son tablas con datos reales de los Dias 1-3.
    }
};
