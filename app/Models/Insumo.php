<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Materia prima o insumo con stock, unidad y costo unitario. */
class Insumo extends Model
{
    protected $table = 'insumos';

    protected $fillable = ['tenant_id', 'nombre', 'unidad', 'stock', 'stock_minimo', 'costo_unitario', 'activo'];

    protected $casts = [
        'stock' => 'float',
        'stock_minimo' => 'float',
        'costo_unitario' => 'float',
        'activo' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function bajoMinimo(): bool
    {
        return (float) $this->stock <= (float) $this->stock_minimo;
    }
}
