<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. PLANES
        Schema::create('planes_saas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->enum('slug', ['emprendedor', 'negocio', 'pro']);
            $table->decimal('precio_mensual', 10, 2);
            $table->decimal('precio_anual', 10, 2);
            $table->smallInteger('max_usuarios')->default(3);
            $table->tinyInteger('max_sucursales')->default(1);
            $table->json('modulos_json')->nullable();
            $table->boolean('activo')->default(true);
            $table->unique('slug');
        });

        // 2. TENANTS (Negocios)
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_negocio', 150);
            $table->string('ruc', 11)->nullable();
            $table->string('email', 120)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('dominio', 120)->nullable()->unique();
            $table->string('logo_url', 255)->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('planes_saas');
            $table->enum('estado', ['trial', 'activo', 'suspendido', 'cancelado'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });

        // 3. SUSCRIPCIONES
        Schema::create('suscripciones_saas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('planes_saas');
            $table->enum('ciclo', ['mensual', 'anual'])->default('mensual');
            $table->decimal('monto', 10, 2);
            $table->enum('pasarela', ['mercadopago', 'niubiz', 'manual'])->default('mercadopago');
            $table->string('pasarela_sub_id', 100)->nullable();
            $table->enum('estado', ['activa', 'trial', 'vencida', 'cancelada'])->default('trial');
            $table->timestamp('inicio_at')->useCurrent();
            $table->timestamp('vence_at')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->timestamps();
        });

        // 4. PAGOS
        Schema::create('pagos_saas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('suscripcion_id')->nullable()->constrained('suscripciones_saas');
            $table->decimal('monto', 10, 2);
            $table->string('pasarela', 30)->nullable();
            $table->string('pasarela_pago_id', 100)->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'fallido', 'reembolsado'])->default('pendiente');
            $table->timestamp('fecha_pago')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_saas');
        Schema::dropIfExists('suscripciones_saas');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('planes_saas');
    }
};