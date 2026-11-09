<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $t) {
            if (! Schema::hasColumn('tenants', 'rubro')) {
                $t->string('rubro', 40)->nullable();
            }
            if (! Schema::hasColumn('tenants', 'color_primario')) {
                $t->string('color_primario', 7)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $t) {
            foreach (['rubro', 'color_primario'] as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
