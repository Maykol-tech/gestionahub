<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('formas_entrega')) {
            Schema::create('formas_entrega', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('nombre', 80);
                $t->string('tipo', 15)->default('delivery'); // delivery, recojo
                $t->decimal('costo', 10, 2)->default(0);
                $t->string('tiempo_estimado', 40)->nullable(); // "30-45 min"
                $t->boolean('activa')->default(true);
                $t->unsignedInteger('orden')->default(0);
                $t->timestamps();
            });
        }

        // Textos de la tienda que el negocio edita desde el panel (GEST-110)
        if (! Schema::hasTable('tienda_contenido')) {
            Schema::create('tienda_contenido', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('seccion', 40);
                $t->json('contenido');
                $t->timestamps();

                $t->unique(['tenant_id', 'seccion']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tienda_contenido');
        Schema::dropIfExists('formas_entrega');
    }
};
