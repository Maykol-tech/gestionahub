<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lote de produccion. Si tiene receta, al terminarlo se descuentan sus insumos. */
class OrdenProduccion extends Model
{
    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'terminada' => 'Terminada',
    ];

    protected $table = 'ordenes_produccion';

    protected $fillable = [
        'tenant_id', 'titulo', 'receta_id', 'cantidad', 'fecha', 'estado', 'notas', 'costo_total', 'descontado_at',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'fecha' => 'date',
        'costo_total' => 'float',
        'descontado_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }
}
