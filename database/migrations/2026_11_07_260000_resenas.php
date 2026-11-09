<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resenas')) {
            Schema::create('resenas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
                $t->string('cliente_nombre', 120);
                $t->unsignedTinyInteger('calificacion');   // 1 a 5
                $t->text('comentario')->nullable();
                $t->string('estado', 15)->default('pendiente'); // pendiente, publicada, oculta
                $t->string('ip', 45)->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'estado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resenas');
    }
};
