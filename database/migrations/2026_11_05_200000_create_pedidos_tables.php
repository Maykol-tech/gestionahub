<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('repartidores')) {
            Schema::create('repartidores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('nombre', 120);
                $table->string('telefono', 20)->nullable();
                $table->enum('vehiculo', ['moto', 'bicicleta', 'auto', 'pie'])->default('moto');
                $table->boolean('activo')->default(true);
                $table->boolean('disponible')->default(true);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('cliente_id')->nullable()->constrained('crm_clientes')->nullOnDelete();
                $table->foreignId('repartidor_id')->nullable()->constrained('repartidores')->nullOnDelete();
                $table->string('numero', 20);
                $table->enum('estado', ['pendiente', 'confirmado', 'preparando', 'listo', 'en_camino', 'entregado', 'cancelado'])->default('pendiente');
                $table->enum('canal', ['web', 'whatsapp', 'pos', 'manual'])->default('manual');
                $table->decimal('total', 10, 2)->default(0);
                $table->string('direccion_entrega', 255)->nullable();
                $table->string('metodo_pago', 30)->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('repartidores');
    }
};