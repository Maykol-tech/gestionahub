<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lineas de pedido (usadas por la tienda publica y por Pedido::items)
        if (! Schema::hasTable('pedido_items')) {
            Schema::create('pedido_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
                $t->unsignedBigInteger('producto_id')->nullable()->index();
                $t->string('nombre', 150);
                $t->integer('cantidad');
                $t->decimal('precio_unitario', 12, 2);
                $t->decimal('subtotal', 12, 2);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};
