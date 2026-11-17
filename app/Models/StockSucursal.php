<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Unidades de un producto que están en una sucursal. */
class StockSucursal extends Model
{
    protected $table = 'stock_sucursales';

    protected $fillable = ['tenant_id', 'producto_id', 'sucursal_id', 'stock'];

    protected $casts = ['stock' => 'integer'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
