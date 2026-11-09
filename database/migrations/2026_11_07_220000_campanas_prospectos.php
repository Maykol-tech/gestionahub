<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campanas_whatsapp')) {
            Schema::create('campanas_whatsapp', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('nombre', 120);
                $t->string('segmento', 20)->default('todos');   // todos, frecuentes, inactivos
                $t->string('plantilla', 120)->nullable();       // plantilla aprobada por Meta (obligatoria fuera de 24 h)
                $t->text('texto')->nullable();                  // texto libre (solo dentro de la ventana de 24 h)
                $t->string('estado', 15)->default('borrador');  // borrador, programada, enviada, cancelada
                $t->timestamp('programada_para')->nullable();
                $t->unsignedInteger('enviados')->default(0);
                $t->unsignedInteger('fallidos')->default(0);
                $t->timestamp('enviada_at')->nullable();
                $t->timestamps();

                $t->index(['estado', 'programada_para']);
            });
        }

        if (! Schema::hasTable('prospectos')) {
            Schema::create('prospectos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('nombre', 150);
                $t->string('telefono', 30)->nullable();
                $t->string('email', 150)->nullable();
                $t->string('rubro', 80)->nullable();
                $t->string('fuente', 80)->default('manual');
                $t->string('estado', 15)->default('nuevo');   // nuevo, contactado, interesado, descartado
                $t->text('notas')->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'estado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prospectos');
        Schema::dropIfExists('campanas_whatsapp');
    }
};
