<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Niveles de fidelizacion configurables por negocio (GEST-75). Null = usar los niveles por defecto.
        if (! Schema::hasColumn('tenants', 'niveles_fidelizacion')) {
            Schema::table('tenants', function (Blueprint $t) {
                $t->json('niveles_fidelizacion')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'niveles_fidelizacion')) {
            Schema::table('tenants', function (Blueprint $t) {
                $t->dropColumn('niveles_fidelizacion');
            });
        }
    }
};
