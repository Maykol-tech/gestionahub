<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Configuracion de venta por kilo de un producto. */
class ProductoKilo extends Model
{
    protected $table = 'producto_kilo';

    protected $fillable = ['tenant_id', 'producto_id', 'precio_kg', 'activo', 'presentaciones', 'peso_libre', 'peso_minimo'];

    protected $casts = ['precio_kg' => 'decimal:2', 'activo' => 'boolean', 'presentaciones' => 'array', 'peso_libre' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
