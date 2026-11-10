<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    use HasFactory;

    protected $table = 'producto_variantes';

    protected $fillable = [
        'tenant_id', 'producto_id', 'nombre', 'atributos', 'sku',
        'precio', 'precio_oferta', 'stock', 'stock_minimo', 'orden', 'activo',
    ];

    protected $casts = [
        'atributos'     => 'array',
        'precio'        => 'decimal:2',
        'precio_oferta' => 'decimal:2',
        'stock'         => 'integer',
        'stock_minimo'  => 'integer',
        'orden'         => 'integer',
        'activo'        => 'boolean',
    ];

    protected $hidden = ['producto'];

    protected $appends = ['precio_final', 'stock_bajo'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'variante_id')->orderBy('orden');
    }

    /** Precio que se cobra: oferta > precio de la variante > precio del producto. */
    public function getPrecioFinalAttribute()
    {
        if ($this->precio_oferta !== null) {
            return $this->precio_oferta;
        }

        return $this->precio ?? $this->producto?->precio;
    }

    public function getStockBajoAttribute(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }
}
