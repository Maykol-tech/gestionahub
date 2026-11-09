<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Variantes de un producto (tamaño, sabor): cada una con su precio y su stock (GEST-105)
        if (! Schema::hasTable('producto_variantes')) {
            Schema::create('producto_variantes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                $t->string('nombre', 60);
                $t->decimal('precio', 10, 2);
                $t->unsignedInteger('stock')->default(0);
                $t->boolean('activo')->default(true);
                $t->timestamps();

                $t->index(['tenant_id', 'producto_id']);
            });
        }

        // Producto que se vende por kilo, con su precio por kilo (GEST-105)
        if (! Schema::hasTable('producto_kilo')) {
            Schema::create('producto_kilo', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->foreignId('producto_id')->unique()->constrained('productos')->cascadeOnDelete();
                $t->decimal('precio_kg', 10, 2);
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        // Ajuste de peso real al despachar (GEST-111): guarda el peso estimado y el real
        if (! Schema::hasTable('pesos_despacho')) {
            Schema::create('pesos_despacho', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
                $t->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
                $t->decimal('peso_estimado_kg', 8, 3);
                $t->decimal('peso_real_kg', 8, 3);
                $t->decimal('precio_kg', 10, 2);
                $t->decimal('diferencia', 10, 2);      // positivo = se cobra mas, negativo = se devuelve
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pesos_despacho');
        Schema::dropIfExists('producto_kilo');
        Schema::dropIfExists('producto_variantes');
    }
};
