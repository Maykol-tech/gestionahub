<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-21: Pedidos y su detalle. Incluye repartidores para GEST-23.
 * Si alguna tabla ya existe (creada a mano) no se vuelve a crear.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('repartidores')) {
            Schema::create('repartidores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nombre', 120);
                $table->string('telefono', 20)->nullable();
                $table->string('dni', 12)->nullable();
                $table->enum('vehiculo', ['moto', 'bicicleta', 'auto', 'pie'])->default('moto');
                $table->boolean('activo')->default(true);
                $table->boolean('disponible')->default(true);
                $table->decimal('lat_actual', 10, 7)->nullable();
                $table->decimal('lng_actual', 10, 7)->nullable();
                $table->timestamp('updated_location_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'activo']);
            });
        }

        if (! Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('cliente_id')->nullable()->constrained('crm_clientes')->nullOnDelete();
                $table->foreignId('repartidor_id')->nullable()->constrained('repartidores')->nullOnDelete();
                $table->string('numero', 20);
                $table->enum('canal', ['web', 'whatsapp', 'pos', 'app', 'manual'])->default('manual');
                $table->enum('estado', ['pendiente', 'confirmado', 'preparando', 'listo', 'en_camino', 'entregado', 'cancelado'])->default('pendiente');
                $table->enum('tipo', ['delivery', 'recojo', 'mesa'])->default('delivery');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('descuento', 10, 2)->default(0);
                $table->decimal('costo_envio', 10, 2)->default(0);
                $table->decimal('igv', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->string('direccion_entrega', 255)->nullable();
                $table->decimal('lat_entrega', 10, 7)->nullable();
                $table->decimal('lng_entrega', 10, 7)->nullable();
                $table->text('notas')->nullable();
                $table->enum('metodo_pago', ['efectivo', 'yape', 'plin', 'tarjeta', 'mercadopago', 'transferencia'])->default('efectivo');
                $table->boolean('pagado')->default(false);
                $table->timestamp('confirmado_at')->nullable();
                $table->timestamp('entregado_at')->nullable();
                $table->timestamp('cancelado_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'numero']);
                $table->index(['tenant_id', 'estado']);
                $table->index(['tenant_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('pedido_items')) {
            Schema::create('pedido_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
                $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
                $table->foreignId('variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();
                $table->string('nombre', 150);
                $table->unsignedSmallInteger('cantidad')->default(1);
                $table->decimal('precio_unit', 10, 2);
                $table->decimal('subtotal', 10, 2);
                $table->string('notas', 255)->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'pedido_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('repartidores');
    }
};
