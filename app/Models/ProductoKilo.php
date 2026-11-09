<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Configuracion de venta por kilo de un producto. */
class ProductoKilo extends Model
{
    protected $table = 'producto_kilo';

    protected $fillable = ['tenant_id', 'producto_id', 'precio_kg', 'activo'];

    protected $casts = ['precio_kg' => 'decimal:2', 'activo' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
