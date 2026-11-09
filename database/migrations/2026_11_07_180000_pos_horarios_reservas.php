<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** POS (ventas de mostrador), horarios de atencion del negocio, y origen/recordatorio de las citas. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ventas_pos')) {
            Schema::create('ventas_pos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->string('numero', 20)->nullable();
                $t->string('metodo_pago', 20)->default('efectivo');
                $t->decimal('total', 12, 2)->default(0);
                $t->decimal('recibido', 12, 2)->nullable();
                $t->decimal('vuelto', 12, 2)->nullable();
                $t->string('cliente_nombre', 120)->nullable();
                $t->json('items');
                $t->timestamps();

                $t->index(['tenant_id', 'created_at']);
            });
        }

        if (Schema::hasTable('tenants') && ! Schema::hasColumn('tenants', 'horarios')) {
            Schema::table('tenants', function (Blueprint $t) {
                $t->json('horarios')->nullable();
            });
        }

        if (Schema::hasTable('citas')) {
            Schema::table('citas', function (Blueprint $t) {
                if (! Schema::hasColumn('citas', 'origen')) {
                    $t->string('origen', 20)->default('interno');
                }
                if (! Schema::hasColumn('citas', 'recordatorio_enviado_at')) {
                    $t->timestamp('recordatorio_enviado_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_pos');

        if (Schema::hasColumn('tenants', 'horarios')) {
            Schema::table('tenants', fn (Blueprint $t) => $t->dropColumn('horarios'));
        }

        if (Schema::hasTable('citas')) {
            Schema::table('citas', function (Blueprint $t) {
                foreach (['origen', 'recordatorio_enviado_at'] as $columna) {
                    if (Schema::hasColumn('citas', $columna)) {
                        $t->dropColumn($columna);
                    }
                }
            });
        }
    }
};
