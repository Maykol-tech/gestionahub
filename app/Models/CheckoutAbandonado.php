<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Carrito que el cliente dejo en el checkout de la tienda. Se usa para recuperarlo por WhatsApp. */
class CheckoutAbandonado extends Model
{
    protected $table = 'checkout_abandonados';

    protected $fillable = ['tenant_id', 'nombre', 'telefono', 'email', 'carrito', 'total', 'recuperado', 'recordado_at', 'token', 'cupon', 'recordatorio_ok', 'abierto_at', 'recuperado_at', 'pedido_id'];

    protected $casts = ['carrito' => 'array', 'total' => 'decimal:2', 'recuperado' => 'boolean', 'recordado_at' => 'datetime', 'abierto_at' => 'datetime', 'recuperado_at' => 'datetime', 'recordatorio_ok' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
