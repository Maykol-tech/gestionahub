<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('landing_configs', function (Blueprint $table) {
            $table->id();
            $table->string('hero_title')->default('Potencia tu negocio con GestionaHub');
            $table->text('hero_subtitle')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('landing_configs');
    }
};