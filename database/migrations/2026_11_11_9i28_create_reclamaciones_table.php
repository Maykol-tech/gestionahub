<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('reclamaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero_caso')->unique();
            $table->string('nombre');
            $table->string('tipo_documento');
            $table->string('numero_documento');
            $table->string('email');
            $table->string('telefono');
            $table->string('direccion');
            $table->string('tipo_bien');
            $table->decimal('monto_reclamado', 10, 2)->nullable();
            $table->text('detalle');
            $table->text('pedido');
            $table->string('estado')->default('pendiente');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reclamaciones'); }
};