<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Linea de una orden de compra. Si tiene insumo vinculado, al recibir la orden sube su stock. */
class OrdenCompraItem extends Model
{
    protected $table = 'orden_compra_items';

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'orden_compra_id', 'insumo_id', 'descripcion', 'cantidad', 'precio_unitario', 'subtotal'];

    protected $casts = [
        'cantidad' => 'float',
        'precio_unitario' => 'float',
        'subtotal' => 'float',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }
}
