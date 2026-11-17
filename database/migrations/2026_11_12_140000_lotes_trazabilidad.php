<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** GEST-74: código, vencimiento y responsable del lote, y registro de los insumos que consumió. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ordenes_produccion')) {
            Schema::table('ordenes_produccion', function (Blueprint $t) {
                if (! Schema::hasColumn('ordenes_produccion', 'codigo')) {
                    $t->string('codigo', 30)->nullable()->index();
                }
                if (! Schema::hasColumn('ordenes_produccion', 'vence_at')) {
                    $t->date('vence_at')->nullable();
                }
                if (! Schema::hasColumn('ordenes_produccion', 'responsable')) {
                    $t->string('responsable', 120)->nullable();
                }
            });
        }
        if (! Schema::hasTable('lote_consumos')) {
            Schema::create('lote_consumos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('orden_produccion_id')->index();
                $t->unsignedBigInteger('insumo_id')->nullable();
                $t->string('insumo', 120);
                $t->string('unidad', 20)->nullable();
                $t->decimal('cantidad', 12, 3);
                $t->decimal('costo', 12, 2)->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Sin cambios destructivos.
    }
};
