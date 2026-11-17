<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de los modulos del administrador: Fidelizacion, Servicios, Proveedores,
 * Produccion y Publicidad. Solo crea lo que falte; no toca datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fidelizacion_recompensas')) {
            Schema::create('fidelizacion_recompensas', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->unsignedInteger('puntos_requeridos')->default(0);
                $t->decimal('descuento_pct', 5, 2)->nullable();
                $t->text('descripcion')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('servicios')) {
            Schema::create('servicios', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->text('descripcion')->nullable();
                $t->decimal('precio', 10, 2)->default(0);
                $t->unsignedSmallInteger('duracion_min')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('proveedores')) {
            Schema::create('proveedores', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->string('ruc', 11)->nullable();
                $t->string('contacto', 120)->nullable();
                $t->string('telefono', 20)->nullable();
                $t->string('email', 120)->nullable();
                $t->string('condicion_pago', 20)->default('contado');
                $t->text('notas')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('ordenes_produccion')) {
            Schema::create('ordenes_produccion', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('titulo', 120);
                $t->unsignedInteger('cantidad')->default(1);
                $t->date('fecha');
                $t->string('estado', 20)->default('pendiente');
                $t->text('notas')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('campanas_publicidad')) {
            Schema::create('campanas_publicidad', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->string('canal', 20)->default('facebook');
                $t->decimal('inversion', 10, 2)->default(0);
                $t->decimal('ventas_atribuidas', 12, 2)->nullable();
                $t->date('fecha_inicio');
                $t->date('fecha_fin')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campanas_publicidad');
        Schema::dropIfExists('ordenes_produccion');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('fidelizacion_recompensas');
    }
};
