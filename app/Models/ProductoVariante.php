<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Variante de un producto (tamaño, sabor) con su propio precio y stock. */
class ProductoVariante extends Model
{
    protected $table = 'producto_variantes';

    protected $fillable = ['tenant_id', 'producto_id', 'nombre', 'precio', 'stock', 'activo'];

    protected $casts = ['precio' => 'decimal:2', 'stock' => 'integer', 'activo' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
