<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('resenas')) {
            Schema::create('resenas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('cliente_nombre');
                $table->string('cliente_telefono')->nullable();
                $table->integer('estrellas');
                $table->text('comentario')->nullable();
                $table->string('estado')->default('pendiente');
                $table->string('origen')->default('propia');
                $table->string('token')->unique()->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void {}
};