<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-91..96: avisos de fin de prueba, ingreso con Google, checkout de suscripción,
 * cobro recurrente con Niubiz y bandeja de pagos manuales. Solo agrega columnas y tablas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'google_id')) $t->string('google_id', 64)->nullable()->unique();
            if (! Schema::hasColumn('users', 'avatar_url')) $t->string('avatar_url', 255)->nullable();
        });

        Schema::table('tenants', function (Blueprint $t) {
            if (! Schema::hasColumn('tenants', 'trial_avisos')) $t->json('trial_avisos')->nullable(); // [7,3,1] ya enviados
        });

        Schema::table('pagos_saas', function (Blueprint $t) {
            if (! Schema::hasColumn('pagos_saas', 'plan_id')) $t->unsignedBigInteger('plan_id')->nullable();
            if (! Schema::hasColumn('pagos_saas', 'ciclo')) $t->string('ciclo', 10)->default('mensual');
            if (! Schema::hasColumn('pagos_saas', 'metodo')) $t->string('metodo', 20)->nullable(); // tarjeta, yape, plin, transferencia
            if (! Schema::hasColumn('pagos_saas', 'numero_operacion')) $t->string('numero_operacion', 40)->nullable();
            if (! Schema::hasColumn('pagos_saas', 'comprobante_path')) $t->string('comprobante_path', 255)->nullable();
            if (! Schema::hasColumn('pagos_saas', 'motivo_rechazo')) $t->string('motivo_rechazo', 255)->nullable();
            if (! Schema::hasColumn('pagos_saas', 'revisado_por')) $t->unsignedBigInteger('revisado_por')->nullable();
            if (! Schema::hasColumn('pagos_saas', 'revisado_at')) $t->timestamp('revisado_at')->nullable();
            if (! Schema::hasColumn('pagos_saas', 'intento')) $t->unsignedTinyInteger('intento')->default(1);
        });
        // Un mismo número de operación no puede usarse dos veces con el mismo método
        if (! $this->tieneIndice('pagos_saas', 'pagos_saas_metodo_operacion_unique')) {
            Schema::table('pagos_saas', fn (Blueprint $t) => $t->unique(['metodo', 'numero_operacion'], 'pagos_saas_metodo_operacion_unique'));
        }

        Schema::table('suscripciones_saas', function (Blueprint $t) {
            if (! Schema::hasColumn('suscripciones_saas', 'tarjeta_token')) $t->text('tarjeta_token')->nullable(); // cifrado
            if (! Schema::hasColumn('suscripciones_saas', 'tarjeta_marca')) $t->string('tarjeta_marca', 20)->nullable();
            if (! Schema::hasColumn('suscripciones_saas', 'tarjeta_ultimos')) $t->string('tarjeta_ultimos', 4)->nullable();
            if (! Schema::hasColumn('suscripciones_saas', 'renovacion_auto')) $t->boolean('renovacion_auto')->default(false);
            if (! Schema::hasColumn('suscripciones_saas', 'intentos_fallidos')) $t->unsignedTinyInteger('intentos_fallidos')->default(0);
            if (! Schema::hasColumn('suscripciones_saas', 'proximo_intento_at')) $t->timestamp('proximo_intento_at')->nullable();
        });

        // GEST-96: cada vez que alguien abre el checkout; si no paga, se vuelve un lead
        if (! Schema::hasTable('checkouts_saas')) {
            Schema::create('checkouts_saas', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('plan_id');
                $t->string('ciclo', 10)->default('mensual');
                $t->string('metodo', 20)->nullable();
                $t->string('estado', 15)->default('iniciado'); // iniciado, pagado, abandonado
                $t->unsignedBigInteger('lead_id')->nullable();
                $t->unsignedBigInteger('pago_id')->nullable();
                $t->timestamps();
            });
        }
    }

    private function tieneIndice(string $tabla, string $nombre): bool
    {
        try {
            return collect(Schema::getIndexes($tabla))->contains(fn ($i) => $i['name'] === $nombre);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts_saas');
    }
};
