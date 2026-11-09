<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cierre de caja diario con arqueo: efectivo esperado contra efectivo contado. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cierres_caja')) {
            Schema::create('cierres_caja', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->date('fecha');
                $t->decimal('monto_inicial', 12, 2)->default(0);
                $t->decimal('ventas_efectivo', 12, 2)->default(0);
                $t->decimal('ventas_digitales', 12, 2)->default(0);
                $t->decimal('gastos_efectivo', 12, 2)->default(0);
                $t->decimal('gastos_digitales', 12, 2)->default(0);
                $t->decimal('esperado', 12, 2)->default(0);
                $t->decimal('contado', 12, 2)->default(0);
                $t->decimal('diferencia', 12, 2)->default(0);
                $t->string('cerrado_por', 120)->nullable();
                $t->text('notas')->nullable();
                $t->timestamps();

                $t->unique(['tenant_id', 'fecha']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
