<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** GEST-86: cada aviso de cupo liberado es una oferta con enlace que vence en X minutos. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lista_espera_ofertas')) {
            Schema::create('lista_espera_ofertas', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('espera_id')->index();
                $t->unsignedBigInteger('cita_id')->index(); // la cita cancelada que liberó el cupo
                $t->string('token', 40)->unique();
                $t->string('estado', 20)->default('enviada'); // enviada, tomada, vencida, cerrada
                $t->boolean('whatsapp_ok')->default(false);
                $t->timestamp('expira_at');
                $t->unsignedBigInteger('cita_nueva_id')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lista_espera_ofertas');
    }
};
