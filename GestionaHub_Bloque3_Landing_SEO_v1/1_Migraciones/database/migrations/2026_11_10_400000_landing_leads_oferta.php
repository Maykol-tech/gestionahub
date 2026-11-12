<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('landing_leads') && ! Schema::hasColumn('landing_leads', 'oferta')) {
            Schema::table('landing_leads', function (Blueprint $t) {
                $t->string('oferta', 40)->nullable()->after('fuente');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('landing_leads') && Schema::hasColumn('landing_leads', 'oferta')) {
            Schema::table('landing_leads', fn (Blueprint $t) => $t->dropColumn('oferta'));
        }
    }
};
