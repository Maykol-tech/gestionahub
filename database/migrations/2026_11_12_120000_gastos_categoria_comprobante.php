<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** GEST-66: los egresos llevan categoría, comprobante (n.º o enlace) y sucursal. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gastos')) {
            return;
        }
        Schema::table('gastos', function (Blueprint $t) {
            if (! Schema::hasColumn('gastos', 'categoria')) {
                $t->string('categoria', 40)->default('otros');
            }
            if (! Schema::hasColumn('gastos', 'comprobante')) {
                $t->string('comprobante', 120)->nullable();
            }
            if (! Schema::hasColumn('gastos', 'sucursal_id')) {
                $t->unsignedBigInteger('sucursal_id')->nullable()->index();
            }
            if (! Schema::hasColumn('gastos', 'registrado_por')) {
                $t->string('registrado_por', 120)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Sin cambios destructivos.
    }
};
