<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('horarios')) {
            Schema::create('horarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->integer('dia_semana'); // 1 = Lunes, 7 = Domingo
                $table->time('apertura');
                $table->time('cierre');
                $table->timestamps();
            });
        }
    }
    public function down(): void {}
};