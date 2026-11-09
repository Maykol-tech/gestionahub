<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('galeria_items')) {
            Schema::create('galeria_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('titulo', 120);
                $t->string('descripcion', 255)->nullable();
                $t->string('imagen_url', 500);
                $t->unsignedInteger('orden')->default(0);
                $t->boolean('activo')->default(true);
                $t->timestamps();

                $t->index(['tenant_id', 'activo', 'orden']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('galeria_items');
    }
};
