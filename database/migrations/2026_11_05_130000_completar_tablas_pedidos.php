<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-21: completa las tablas de pedidos cuando "pedidos" ya existia de antes
 * (creada a mano o por otra version). Agrega las columnas que falten y crea
 * pedido_items y repartidores si no estan. No borra ni cambia datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('repartidores')) {
            Schema::create('repartidores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('nombre', 120);
                $table->string('telefono', 20)->nullable();
                $table->string('dni', 12)->nullable();
                $table->string('vehiculo', 20)->default('moto');
                $table->boolean('activo')->default(true);
                $table->boolean('disponible')->default(true);
                $table->decimal('lat_actual', 10, 7)->nullable();
                $table->decimal('lng_actual', 10, 7)->nullable();
                $table->timestamp('updated_location_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pedidos')) {
            $columnas = [
                'cliente_id' => fn (Blueprint $t) => $t->unsignedBigInteger('cliente_id')->nullable(),
                'repartidor_id' => fn (Blueprint $t) => $t->unsignedBigInteger('repartidor_id')->nullable(),
                'numero' => fn (Blueprint $t) => $t->string('numero', 20)->nullable(),
                'canal' => fn (Blueprint $t) => $t->string('canal', 20)->default('manual'),
                'estado' => fn (Blueprint $t) => $t->string('estado', 20)->default('pendiente'),
                'tipo' => fn (Blueprint $t) => $t->string('tipo', 20)->default('delivery'),
                'subtotal' => fn (Blueprint $t) => $t->decimal('subtotal', 10, 2)->default(0),
                'descuento' => fn (Blueprint $t) => $t->decimal('descuento', 10, 2)->default(0),
                'costo_envio' => fn (Blueprint $t) => $t->decimal('costo_envio', 10, 2)->default(0),
                'igv' => fn (Blueprint $t) => $t->decimal('igv', 10, 2)->default(0),
                'total' => fn (Blueprint $t) => $t->decimal('total', 10, 2)->default(0),
                'direccion_entrega' => fn (Blueprint $t) => $t->string('direccion_entrega', 255)->nullable(),
                'lat_entrega' => fn (Blueprint $t) => $t->decimal('lat_entrega', 10, 7)->nullable(),
                'lng_entrega' => fn (Blueprint $t) => $t->decimal('lng_entrega', 10, 7)->nullable(),
                'notas' => fn (Blueprint $t) => $t->text('notas')->nullable(),
                'metodo_pago' => fn (Blueprint $t) => $t->string('metodo_pago', 20)->default('efectivo'),
                'pagado' => fn (Blueprint $t) => $t->boolean('pagado')->default(false),
                'confirmado_at' => fn (Blueprint $t) => $t->timestamp('confirmado_at')->nullable(),
                'entregado_at' => fn (Blueprint $t) => $t->timestamp('entregado_at')->nullable(),
                'cancelado_at' => fn (Blueprint $t) => $t->timestamp('cancelado_at')->nullable(),
                'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
                'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
            ];
            foreach ($columnas as $nombre => $definir) {
                if (! Schema::hasColumn('pedidos', $nombre)) {
                    Schema::table('pedidos', fn (Blueprint $t) => $definir($t));
                }
            }
        }

        if (! Schema::hasTable('pedido_items')) {
            Schema::create('pedido_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('pedido_id');
                $table->unsignedBigInteger('producto_id')->nullable();
                $table->unsignedBigInteger('variante_id')->nullable();
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
        // No se revierte: solo completa tablas existentes
    }
};