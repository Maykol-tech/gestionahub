<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tienda publica (slug, pagos Yape/Plin), pago de pedidos con Mercado Pago,
 * vencimiento de suscripciones SaaS y registro de mensajes de WhatsApp.
 * Solo agrega lo que falte; no borra ni cambia datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $t) {
            if (! Schema::hasColumn('tenants', 'slug')) $t->string('slug', 60)->nullable()->unique();
            if (! Schema::hasColumn('tenants', 'tienda_activa')) $t->boolean('tienda_activa')->default(false);
            if (! Schema::hasColumn('tenants', 'yape_numero')) $t->string('yape_numero', 9)->nullable();
            if (! Schema::hasColumn('tenants', 'plin_numero')) $t->string('plin_numero', 9)->nullable();
            if (! Schema::hasColumn('tenants', 'yape_qr')) $t->string('yape_qr', 255)->nullable();
            if (! Schema::hasColumn('tenants', 'suscripcion_hasta')) $t->timestamp('suscripcion_hasta')->nullable();
        });

        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos', 'pago_ref')) $t->string('pago_ref', 64)->nullable();
        });

        if (! Schema::hasTable('whatsapp_mensajes')) {
            Schema::create('whatsapp_mensajes', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id')->nullable()->index();
                $t->unsignedBigInteger('pedido_id')->nullable()->index();
                $t->string('direccion', 12);
                $t->string('telefono', 20);
                $t->string('tipo', 30);
                $t->text('texto')->nullable();
                $t->string('estado', 20);
                $t->string('meta_id', 120)->nullable();
                $t->text('error')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_mensajes');
    }
};
