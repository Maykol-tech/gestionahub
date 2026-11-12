<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Stub seguro para evitar errores de tabla inexistente
        if (!Schema::hasTable('tienda_pagos_saas')) {
            Schema::create('tienda_pagos_saas', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }
    public function down(): void {}
};