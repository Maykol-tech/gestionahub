<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Carrito que el cliente dejo en el checkout de la tienda. Se usa para recuperarlo por WhatsApp. */
class CheckoutAbandonado extends Model
{
    protected $table = 'checkout_abandonados';

    protected $fillable = ['tenant_id', 'nombre', 'telefono', 'email', 'carrito', 'total', 'recuperado', 'recordado_at'];

    protected $casts = ['carrito' => 'array', 'total' => 'decimal:2', 'recuperado' => 'boolean', 'recordado_at' => 'datetime'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
