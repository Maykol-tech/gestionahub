<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Publicidad: metricas de impacto por campana (alcance, clics, conversiones) y registro de gastos. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campanas_publicidad')) {
            Schema::table('campanas_publicidad', function (Blueprint $t) {
                if (! Schema::hasColumn('campanas_publicidad', 'objetivo')) {
                    $t->string('objetivo', 20)->default('ventas');
                }
                if (! Schema::hasColumn('campanas_publicidad', 'alcance')) {
                    $t->unsignedInteger('alcance')->nullable();
                }
                if (! Schema::hasColumn('campanas_publicidad', 'clics')) {
                    $t->unsignedInteger('clics')->nullable();
                }
                if (! Schema::hasColumn('campanas_publicidad', 'conversiones')) {
                    $t->unsignedInteger('conversiones')->nullable();
                }
                if (! Schema::hasColumn('campanas_publicidad', 'notas')) {
                    $t->text('notas')->nullable();
                }
            });
        }

        if (! Schema::hasTable('campana_gastos')) {
            Schema::create('campana_gastos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->foreignId('campana_id')->constrained('campanas_publicidad')->cascadeOnDelete();
                $t->date('fecha');
                $t->decimal('monto', 12, 2);
                $t->string('concepto', 120)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campana_gastos');

        if (Schema::hasTable('campanas_publicidad')) {
            Schema::table('campanas_publicidad', function (Blueprint $t) {
                foreach (['objetivo', 'alcance', 'clics', 'conversiones', 'notas'] as $columna) {
                    if (Schema::hasColumn('campanas_publicidad', $columna)) {
                        $t->dropColumn($columna);
                    }
                }
            });
        }
    }
};
