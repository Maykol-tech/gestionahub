<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ingresos registrados a mano (GEST-66): ventas fuera del sistema, alquileres, otros ingresos
        if (! Schema::hasTable('ingresos_manuales')) {
            Schema::create('ingresos_manuales', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->date('fecha');
                $t->string('concepto', 120);
                $t->decimal('monto', 12, 2);
                $t->string('categoria', 40)->nullable();
                $t->string('notas', 255)->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'fecha']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos_manuales');
    }
};
