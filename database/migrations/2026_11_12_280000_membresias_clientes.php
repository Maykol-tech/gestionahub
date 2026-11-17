<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-57: suscripciones recurrentes para los clientes del negocio (membresías, menú semanal...).
 * Solo crea tablas nuevas; no toca nada existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('planes_cliente')) {
            Schema::create('planes_cliente', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('nombre', 80);
                $t->string('descripcion', 255)->nullable();
                $t->decimal('precio', 10, 2);
                $t->string('periodicidad', 12)->default('mensual'); // semanal, quincenal, mensual, anual
                $t->text('beneficios')->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('suscripciones_cliente')) {
            Schema::create('suscripciones_cliente', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('plan_id');
                $t->unsignedBigInteger('cliente_id');
                $t->string('estado', 12)->default('pendiente'); // pendiente, activa, vencida, cancelada
                $t->decimal('precio', 10, 2);
                $t->string('periodicidad', 12);
                $t->date('inicio')->nullable();
                $t->date('proximo_cobro')->nullable();
                $t->timestamp('cancelada_at')->nullable();
                $t->string('token', 40)->unique();
                $t->timestamps();
                $t->index(['tenant_id', 'estado']);
            });
        }
        if (! Schema::hasTable('cobros_suscripcion')) {
            Schema::create('cobros_suscripcion', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('suscripcion_id')->index();
                $t->decimal('monto', 10, 2);
                $t->date('desde');
                $t->date('hasta');
                $t->date('vence');
                $t->string('estado', 12)->default('pendiente'); // pendiente, por_revisar, pagado, vencido, anulado
                $t->string('metodo', 20)->nullable();
                $t->string('referencia', 60)->nullable();
                $t->string('captura')->nullable();
                $t->timestamp('pagado_at')->nullable();
                $t->timestamp('avisado_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        // No se borran tablas con datos del negocio.
    }
};
