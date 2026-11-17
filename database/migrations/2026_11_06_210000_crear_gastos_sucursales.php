<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gastos (alimentan la caja del dia) y sucursales del negocio.
 * Solo crea lo que falte; no toca datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gastos')) {
            Schema::create('gastos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('concepto', 120);
                $t->decimal('monto', 10, 2)->default(0);
                $t->date('fecha');
                $t->string('metodo', 20)->default('efectivo');
                $t->text('notas')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('sucursales')) {
            Schema::create('sucursales', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->string('direccion', 200)->nullable();
                $t->string('telefono', 20)->nullable();
                $t->string('email', 120)->nullable();
                $t->string('horario', 120)->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('gastos');
    }
};
