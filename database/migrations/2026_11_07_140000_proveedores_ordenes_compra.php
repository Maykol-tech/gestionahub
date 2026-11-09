<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ordenes de compra a proveedores, con sus items. Al recibirlas suben el stock de los insumos vinculados. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ordenes_compra')) {
            Schema::create('ordenes_compra', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
                $t->string('numero', 20)->nullable();
                $t->date('fecha');
                $t->string('estado', 20)->default('borrador');
                $t->decimal('total', 12, 2)->default(0);
                $t->text('notas')->nullable();
                $t->timestamp('recibida_at')->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'estado']);
            });
        }

        if (! Schema::hasTable('orden_compra_items')) {
            Schema::create('orden_compra_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
                $t->foreignId('insumo_id')->nullable()->constrained('insumos')->nullOnDelete();
                $t->string('descripcion', 160);
                $t->decimal('cantidad', 12, 3);
                $t->decimal('precio_unitario', 12, 4)->default(0);
                $t->decimal('subtotal', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_items');
        Schema::dropIfExists('ordenes_compra');
    }
};
