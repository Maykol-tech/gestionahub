<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mesas')) {
            Schema::create('mesas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('nombre', 40);
                $t->unsignedTinyInteger('capacidad')->default(4);
                $t->string('estado', 15)->default('libre'); // libre, ocupada, por_cobrar
                $t->string('token', 60)->unique();          // parte de la URL del QR
                $t->boolean('activo')->default(true);
                $t->timestamps();

                $t->unique(['tenant_id', 'nombre']);
            });
        }

        if (! Schema::hasTable('comandas')) {
            Schema::create('comandas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->foreignId('mesa_id')->constrained('mesas')->cascadeOnDelete();
                $t->string('estado', 15)->default('pendiente');
                $t->json('items');
                $t->decimal('total', 12, 2)->default(0);
                $t->string('notas', 255)->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'estado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comandas');
        Schema::dropIfExists('mesas');
    }
};
