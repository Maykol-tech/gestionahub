<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_sesiones')) {
            Schema::create('whatsapp_sesiones', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('telefono', 30);
                $t->unsignedInteger('mensajes')->default(0);
                $t->timestamp('ultimo_mensaje_at')->nullable();
                $t->timestamps();

                $t->unique(['tenant_id', 'telefono']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sesiones');
    }
};
