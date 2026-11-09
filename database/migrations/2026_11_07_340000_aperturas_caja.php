<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Apertura de caja diaria con registro (GEST-67): monto inicial con quien lo abrio
        if (! Schema::hasTable('aperturas_caja')) {
            Schema::create('aperturas_caja', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->date('fecha');
                $t->decimal('monto_inicial', 12, 2);
                $t->string('abierto_por', 120)->nullable();
                $t->string('notas', 255)->nullable();
                $t->timestamps();

                $t->unique(['tenant_id', 'fecha']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aperturas_caja');
    }
};
