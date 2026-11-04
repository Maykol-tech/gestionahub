<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('nombre', 120);
            $table->string('telefono', 20)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('dni', 12)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('distrito', 80)->nullable();
            $table->enum('fuente', ['landing','whatsapp','manual','referido','importacion'])->default('manual');
            $table->tinyInteger('score_lealtad')->default(0);
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'telefono']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_clientes');
    }
};
