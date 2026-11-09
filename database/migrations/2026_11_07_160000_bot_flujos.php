<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Flujos del bot de WhatsApp: si el mensaje coincide con un disparador, el bot responde con el texto configurado. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_flujos')) {
            Schema::create('bot_flujos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 120);
                $t->string('disparador', 120);
                $t->string('coincidencia', 20)->default('contiene');
                $t->text('respuesta');
                $t->unsignedSmallInteger('orden')->default(0);
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_flujos');
    }
};
