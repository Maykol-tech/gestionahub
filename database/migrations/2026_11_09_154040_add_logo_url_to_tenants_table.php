<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenants', 'logo_url')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('logo_url')->nullable()->after('nombre');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'logo_url')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('logo_url');
            });
        }
    }
};
