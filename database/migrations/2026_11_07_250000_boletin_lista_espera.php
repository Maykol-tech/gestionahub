<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $t) {
            if (! Schema::hasColumn('tenants', 'boletin_activo')) {
                $t->boolean('boletin_activo')->default(false);
            }
            if (! Schema::hasColumn('tenants', 'boletin_texto')) {
                $t->text('boletin_texto')->nullable();
            }
        });

        if (! Schema::hasTable('lista_espera_citas')) {
            Schema::create('lista_espera_citas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
                $t->string('cliente_nombre', 120);
                $t->string('cliente_telefono', 30);
                $t->date('fecha_deseada');
                $t->string('estado', 15)->default('esperando'); // esperando, avisado, reservado
                $t->foreignId('cita_origen_id')->nullable()->constrained('citas')->nullOnDelete();
                $t->timestamp('avisado_at')->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'fecha_deseada', 'estado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lista_espera_citas');
        Schema::table('tenants', function (Blueprint $t) {
            foreach (['boletin_activo', 'boletin_texto'] as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
