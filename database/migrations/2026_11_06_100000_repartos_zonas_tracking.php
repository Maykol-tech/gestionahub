<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * GEST-23, 24 y 25: repartidores con enlace propio, historial de ubicaciones
 * para el mapa en vivo y zonas de delivery con costo por zona.
 * Solo agrega lo que falte: no borra ni cambia datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Repartidores: enlace privado para la app del repartidor (GEST-24)
        if (! Schema::hasTable('repartidores')) {
            Schema::create('repartidores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
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
                $table->index(['tenant_id', 'activo']);
            });
        }
        $columnasRep = [
            'telefono' => fn (Blueprint $t) => $t->string('telefono', 20)->nullable(),
            'dni' => fn (Blueprint $t) => $t->string('dni', 12)->nullable(),
            'vehiculo' => fn (Blueprint $t) => $t->string('vehiculo', 20)->default('moto'),
            'activo' => fn (Blueprint $t) => $t->boolean('activo')->default(true),
            'lat_actual' => fn (Blueprint $t) => $t->decimal('lat_actual', 10, 7)->nullable(),
            'lng_actual' => fn (Blueprint $t) => $t->decimal('lng_actual', 10, 7)->nullable(),
            'updated_location_at' => fn (Blueprint $t) => $t->timestamp('updated_location_at')->nullable(),
            'token' => fn (Blueprint $t) => $t->string('token', 64)->nullable()->unique(),
        ];
        foreach ($columnasRep as $nombre => $definir) {
            if (! Schema::hasColumn('repartidores', $nombre)) {
                Schema::table('repartidores', fn (Blueprint $t) => $definir($t));
            }
        }
        foreach (DB::table('repartidores')->whereNull('token')->pluck('id') as $id) {
            DB::table('repartidores')->where('id', $id)->update(['token' => Str::random(32)]);
        }

        // Zonas de delivery con costo por zona (GEST-25)
        if (! Schema::hasTable('zonas_delivery')) {
            Schema::create('zonas_delivery', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('nombre', 80);
                $table->decimal('costo', 8, 2)->default(0);
                $table->unsignedSmallInteger('tiempo_min')->nullable();
                $table->string('color', 7)->default('#2B6EF6');
                $table->json('poligono')->nullable();
                $table->boolean('activo')->default(true);
                $table->unsignedSmallInteger('orden')->default(0);
                $table->timestamps();
                $table->index(['tenant_id', 'activo']);
            });
        }

        // Pedidos: zona elegida y hora de asignacion al repartidor
        if (Schema::hasTable('pedidos')) {
            $columnasPed = [
                'repartidor_id' => fn (Blueprint $t) => $t->unsignedBigInteger('repartidor_id')->nullable(),
                'zona_id' => fn (Blueprint $t) => $t->unsignedBigInteger('zona_id')->nullable()->index(),
                'asignado_at' => fn (Blueprint $t) => $t->timestamp('asignado_at')->nullable(),
                'lat_entrega' => fn (Blueprint $t) => $t->decimal('lat_entrega', 10, 7)->nullable(),
                'lng_entrega' => fn (Blueprint $t) => $t->decimal('lng_entrega', 10, 7)->nullable(),
            ];
            foreach ($columnasPed as $nombre => $definir) {
                if (! Schema::hasColumn('pedidos', $nombre)) {
                    Schema::table('pedidos', fn (Blueprint $t) => $definir($t));
                }
            }
        }

        // Recorrido de cada repartidor para el mapa en vivo (GEST-24)
        if (! Schema::hasTable('repartidor_ubicaciones')) {
            Schema::create('repartidor_ubicaciones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('repartidor_id');
                $table->unsignedBigInteger('pedido_id')->nullable();
                $table->decimal('lat', 10, 7);
                $table->decimal('lng', 10, 7);
                $table->unsignedSmallInteger('precision_m')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['repartidor_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repartidor_ubicaciones');
        Schema::dropIfExists('zonas_delivery');
        if (Schema::hasColumn('pedidos', 'zona_id')) {
            Schema::table('pedidos', function (Blueprint $t) {
                $t->dropIndex(['zona_id']);
                $t->dropColumn('zona_id');
            });
        }
        if (Schema::hasColumn('pedidos', 'asignado_at')) {
            Schema::table('pedidos', fn (Blueprint $t) => $t->dropColumn('asignado_at'));
        }
        if (Schema::hasColumn('repartidores', 'token')) {
            Schema::table('repartidores', function (Blueprint $t) {
                $t->dropUnique(['token']);
                $t->dropColumn('token');
            });
        }
    }
};
