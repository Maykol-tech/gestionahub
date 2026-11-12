<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * pedidos.canal era ENUM('web','whatsapp','pos','app','manual'), pero la tienda publica guarda "tienda".
 * En MySQL estricto ese INSERT fallaba y las ventas de la tienda nunca llegaban al reporte (GEST-40).
 * Se cambia a VARCHAR(20) conservando todos los valores existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pedidos') || ! Schema::hasColumn('pedidos', 'canal')) {
            return;
        }

        Schema::table('pedidos', function (Blueprint $t) {
            $t->string('canal', 20)->default('manual')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pedidos') || ! Schema::hasColumn('pedidos', 'canal')) {
            return;
        }

        DB::table('pedidos')->where('canal', 'tienda')->update(['canal' => 'web']);
        DB::table('pedidos')->whereNotIn('canal', ['web', 'whatsapp', 'pos', 'app', 'manual'])->update(['canal' => 'manual']);

        Schema::table('pedidos', function (Blueprint $t) {
            $t->enum('canal', ['web', 'whatsapp', 'pos', 'app', 'manual'])->default('manual')->change();
        });
    }
};
