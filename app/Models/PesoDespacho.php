<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Peso real registrado al despachar un producto que se vende por kilo. */
class PesoDespacho extends Model
{
    protected $table = 'pesos_despacho';

    protected $fillable = [
        'tenant_id', 'pedido_id', 'producto_id', 'peso_estimado_kg', 'peso_real_kg', 'precio_kg', 'diferencia',
    ];

    protected $casts = [
        'peso_estimado_kg' => 'decimal:3', 'peso_real_kg' => 'decimal:3',
        'precio_kg' => 'decimal:2', 'diferencia' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
