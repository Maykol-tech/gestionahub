<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Orden de compra a un proveedor. Pasa por borrador, enviada y recibida (o cancelada). */
class OrdenCompra extends Model
{
    public const ESTADOS = [
        'borrador' => 'Borrador',
        'enviada' => 'Enviada',
        'recibida' => 'Recibida',
        'cancelada' => 'Cancelada',
    ];

    protected $table = 'ordenes_compra';

    protected $fillable = ['tenant_id', 'proveedor_id', 'numero', 'fecha', 'estado', 'total', 'notas', 'recibida_at'];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'float',
        'recibida_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrdenCompraItem::class, 'orden_compra_id');
    }
}
