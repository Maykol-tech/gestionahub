<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('landing_contenido')) {
            Schema::create('landing_contenido', function (Blueprint $t) {
                $t->id();
                $t->string('seccion', 40)->unique();
                $t->json('contenido');
                $t->timestamps();
            });
        }

        // Leads de la landing SaaS (plataforma, no de un negocio): alimentan el CRM del superadmin
        if (! Schema::hasTable('landing_leads')) {
            Schema::create('landing_leads', function (Blueprint $t) {
                $t->id();
                $t->string('nombre', 150);
                $t->string('email', 150)->nullable();
                $t->string('telefono', 30)->nullable();
                $t->text('mensaje')->nullable();
                $t->string('fuente', 40)->default('landing');
                $t->json('utm')->nullable();
                $t->string('estado', 15)->default('nuevo');
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('libro_reclamaciones')) {
            Schema::create('libro_reclamaciones', function (Blueprint $t) {
                $t->id();
                $t->string('numero', 20)->unique();      // LR-000001
                $t->string('tipo', 10);                  // reclamo, queja
                $t->string('nombre', 150);
                $t->string('documento_tipo', 5);         // DNI, CE, RUC
                $t->string('documento', 20);
                $t->string('email', 150);
                $t->string('telefono', 30)->nullable();
                $t->string('pedido', 255)->nullable();   // bien o servicio contratado
                $t->text('detalle');
                $t->string('ip', 45)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_reclamaciones');
        Schema::dropIfExists('landing_leads');
        Schema::dropIfExists('landing_contenido');
    }
};
