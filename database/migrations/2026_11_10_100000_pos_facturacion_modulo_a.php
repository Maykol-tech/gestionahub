<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo A (POS y Facturacion):
 *  - ventas_pos guarda el desglose de IGV, el comprobante pedido y si la venta fue anulada.
 *  - comprobantes_electronicos se puede ligar a una venta POS y la nota de credito apunta al original.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ventas_pos')) {
            Schema::table('ventas_pos', function (Blueprint $t) {
                if (! Schema::hasColumn('ventas_pos', 'gravada')) {
                    $t->decimal('gravada', 12, 2)->default(0)->after('metodo_pago');
                }
                if (! Schema::hasColumn('ventas_pos', 'igv')) {
                    $t->decimal('igv', 12, 2)->default(0)->after('gravada');
                }
                if (! Schema::hasColumn('ventas_pos', 'tipo_comprobante')) {
                    $t->string('tipo_comprobante', 10)->default('ticket'); // ticket, boleta, factura
                }
                if (! Schema::hasColumn('ventas_pos', 'cliente_documento')) {
                    $t->string('cliente_documento', 11)->nullable();
                }
                if (! Schema::hasColumn('ventas_pos', 'estado')) {
                    $t->string('estado', 10)->default('pagada'); // pagada, anulada
                }
                if (! Schema::hasColumn('ventas_pos', 'anulada_at')) {
                    $t->timestamp('anulada_at')->nullable();
                }
                if (! Schema::hasColumn('ventas_pos', 'user_id')) {
                    $t->unsignedBigInteger('user_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('comprobantes_electronicos')) {
            Schema::table('comprobantes_electronicos', function (Blueprint $t) {
                if (! Schema::hasColumn('comprobantes_electronicos', 'venta_pos_id')) {
                    $t->unsignedBigInteger('venta_pos_id')->nullable()->index();
                }
                if (! Schema::hasColumn('comprobantes_electronicos', 'referencia_id')) {
                    $t->unsignedBigInteger('referencia_id')->nullable()->index(); // nota de credito -> comprobante original
                }
                if (! Schema::hasColumn('comprobantes_electronicos', 'simulado')) {
                    $t->boolean('simulado')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ventas_pos')) {
            Schema::table('ventas_pos', function (Blueprint $t) {
                foreach (['gravada', 'igv', 'tipo_comprobante', 'cliente_documento', 'estado', 'anulada_at', 'user_id'] as $c) {
                    if (Schema::hasColumn('ventas_pos', $c)) {
                        $t->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('comprobantes_electronicos')) {
            Schema::table('comprobantes_electronicos', function (Blueprint $t) {
                foreach (['venta_pos_id', 'referencia_id', 'simulado'] as $c) {
                    if (Schema::hasColumn('comprobantes_electronicos', $c)) {
                        $t->dropColumn($c);
                    }
                }
            });
        }
    }
};
