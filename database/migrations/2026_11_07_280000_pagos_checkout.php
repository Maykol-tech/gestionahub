<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pagos_manuales')) {
            Schema::create('pagos_manuales', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
                $t->string('metodo', 15);                 // yape, plin, transferencia
                $t->decimal('monto', 12, 2);
                $t->string('referencia', 60)->nullable(); // codigo de operacion que muestra la app o el banco
                $t->string('estado', 15)->default('pendiente'); // pendiente, confirmado, rechazado
                $t->string('notas', 255)->nullable();
                $t->timestamp('confirmado_at')->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'estado']);
            });
        }

        if (! Schema::hasTable('checkout_abandonados')) {
            Schema::create('checkout_abandonados', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->string('nombre', 120)->nullable();
                $t->string('telefono', 30)->nullable();
                $t->string('email', 150)->nullable();
                $t->json('carrito')->nullable();
                $t->decimal('total', 12, 2)->default(0);
                $t->boolean('recuperado')->default(false);
                $t->timestamp('recordado_at')->nullable();
                $t->timestamps();

                $t->index(['tenant_id', 'recuperado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_abandonados');
        Schema::dropIfExists('pagos_manuales');
    }
};
