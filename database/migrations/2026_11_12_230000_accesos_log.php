<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Historial de accesos al panel (ingresos, intentos fallidos, bloqueos y desbloqueos). Solo agrega una tabla. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accesos_log')) {
            Schema::create('accesos_log', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->nullable()->index();
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('email', 150);
                $t->string('ip', 45)->nullable();
                $t->string('navegador', 250)->nullable();
                $t->string('resultado', 20); // ingreso | fallido | bloqueado | desbloqueado | salida
                $t->timestamp('created_at')->nullable();
                $t->index(['tenant_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_log');
    }
};
