<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sunat_configuraciones')) {
            Schema::create('sunat_configuraciones', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
                $t->string('ruta', 255)->nullable();
                $t->text('token')->nullable();
                $t->string('serie_factura', 4)->default('F001');
                $t->string('serie_boleta', 4)->default('B001');
                $t->string('ambiente', 12)->default('demo');
                $t->boolean('activo')->default(false);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('comprobantes_electronicos')) {
            Schema::create('comprobantes_electronicos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $t->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
                $t->string('tipo', 2);                 // 01 factura, 03 boleta
                $t->string('serie', 4);
                $t->unsignedInteger('numero');
                $t->string('cliente_tipo_documento', 2)->default('0');
                $t->string('cliente_documento', 20)->nullable();
                $t->string('cliente_nombre', 150);
                $t->string('cliente_direccion', 255)->nullable();
                $t->decimal('subtotal', 12, 2)->default(0);   // valor gravado sin IGV
                $t->decimal('igv', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);
                $t->string('estado', 15)->default('pendiente'); // pendiente, aceptado, rechazado, error
                $t->boolean('aceptado')->default(false);
                $t->text('mensaje')->nullable();
                $t->text('enlace_pdf')->nullable();
                $t->text('enlace_xml')->nullable();
                $t->text('enlace_cdr')->nullable();
                $t->string('codigo_hash', 100)->nullable();
                $t->json('payload')->nullable();
                $t->timestamp('emitido_at')->nullable();
                $t->timestamps();

                $t->unique(['tenant_id', 'tipo', 'serie', 'numero']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes_electronicos');
        Schema::dropIfExists('sunat_configuraciones');
    }
};
