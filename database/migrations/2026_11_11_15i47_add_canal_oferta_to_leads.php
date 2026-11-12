<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (!Schema::hasColumn('leads', 'canal')) {
                    $table->string('canal')->nullable();
                }
                if (!Schema::hasColumn('leads', 'oferta')) {
                    $table->string('oferta')->nullable();
                }
                if (!Schema::hasColumn('leads', 'negocio')) {
                    $table->string('negocio')->nullable();
                }
                if (!Schema::hasColumn('leads', 'rubro')) {
                    $table->string('rubro')->nullable();
                }
            });
        }
    }
    public function down(): void {}
};