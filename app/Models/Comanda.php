<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Pedido que sale de una mesa hacia la cocina. */
class Comanda extends Model
{
    protected $table = 'comandas';

    public const ESTADOS = ['pendiente', 'en_preparacion', 'lista', 'entregada', 'cobrada', 'cancelada'];

    public const ABIERTAS = ['pendiente', 'en_preparacion', 'lista', 'entregada'];

    /** Flujo permitido: cocina avanza la comanda y solo se cancela antes de estar lista. */
    public const TRANSICIONES = [
        'pendiente' => ['en_preparacion', 'cancelada'],
        'en_preparacion' => ['lista', 'cancelada'],
        'lista' => ['entregada'],
        'entregada' => ['cobrada'],
        'cobrada' => [],
        'cancelada' => [],
    ];

    protected $fillable = ['tenant_id', 'mesa_id', 'estado', 'items', 'total', 'notas'];

    protected $casts = ['items' => 'array', 'total' => 'decimal:2'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }
}
