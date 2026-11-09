<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $t) {
            if (! Schema::hasColumn('tenants', 'telefono')) {
                $t->string('telefono', 30)->nullable();
            }
            if (! Schema::hasColumn('tenants', 'direccion')) {
                $t->string('direccion', 255)->nullable();
            }
            if (! Schema::hasColumn('tenants', 'ruc')) {
                $t->string('ruc', 11)->nullable();
            }
            if (! Schema::hasColumn('tenants', 'descripcion')) {
                $t->text('descripcion')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'sitio_web')) {
                $t->string('sitio_web', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $t) {
            foreach (['telefono', 'direccion', 'ruc', 'descripcion', 'sitio_web'] as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
