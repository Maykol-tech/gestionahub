<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Agenda de citas para los servicios (peluqueria, consultas, reparaciones, etc.). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('citas')) {
            Schema::create('citas', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
                $t->foreignId('crm_cliente_id')->nullable()->constrained('crm_clientes')->nullOnDelete();
                $t->string('cliente_nombre', 120);
                $t->string('cliente_telefono', 30)->nullable();
                $t->dateTime('inicio');
                $t->unsignedSmallInteger('duracion_min')->default(30);
                $t->decimal('precio', 10, 2)->default(0);
                $t->string('estado', 20)->default('pendiente');
                $t->text('notas')->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'inicio']);
                $t->index(['tenant_id', 'estado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
