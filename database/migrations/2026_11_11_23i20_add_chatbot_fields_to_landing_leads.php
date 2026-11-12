<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('landing_leads')) {
            Schema::table('landing_leads', function (Blueprint $table) {
                if (!Schema::hasColumn('landing_leads', 'origen')) $table->string('origen')->nullable();
                if (!Schema::hasColumn('landing_leads', 'rubro')) $table->string('rubro')->nullable();
                if (!Schema::hasColumn('landing_leads', 'tamano')) $table->string('tamano')->nullable();
                if (!Schema::hasColumn('landing_leads', 'utm_source')) $table->string('utm_source')->nullable();
                if (!Schema::hasColumn('landing_leads', 'utm_campaign')) $table->string('utm_campaign')->nullable();
            });
        }
    }
    public function down(): void {}
};