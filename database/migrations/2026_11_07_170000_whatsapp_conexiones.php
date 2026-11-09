<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Numero de WhatsApp Business propio de cada negocio. El token se guarda cifrado. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_conexiones')) {
            Schema::create('whatsapp_conexiones', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->unique();
                $t->string('phone_id', 40)->unique();
                $t->text('token');
                $t->string('numero_visible', 30)->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conexiones');
    }
};
