<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menus numerados del bot (GEST-30): "escribe 1 para..., 2 para..."
        if (! Schema::hasTable('bot_menus')) {
            Schema::create('bot_menus', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('disparador', 80);          // palabra que abre el menu, ej. "menu"
                $t->text('titulo');                    // texto de bienvenida antes de las opciones
                $t->json('opciones');                  // [{numero, texto, respuesta}]
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        // Contexto de la conversacion: menu abierto y cliente del CRM vinculado (GEST-31 y GEST-30)
        Schema::table('whatsapp_sesiones', function (Blueprint $t) {
            if (! Schema::hasColumn('whatsapp_sesiones', 'contexto')) {
                $t->json('contexto')->nullable();
            }
            if (! Schema::hasColumn('whatsapp_sesiones', 'crm_cliente_id')) {
                $t->unsignedBigInteger('crm_cliente_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_sesiones', function (Blueprint $t) {
            foreach (['contexto', 'crm_cliente_id'] as $col) {
                if (Schema::hasColumn('whatsapp_sesiones', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('bot_menus');
    }
};
