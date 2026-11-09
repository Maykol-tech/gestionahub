<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Reseña de un cliente. Entra como pendiente y el negocio decide si la publica. */
class Resena extends Model
{
    protected $table = 'resenas';

    public const ESTADOS = ['pendiente' => 'Pendiente', 'publicada' => 'Publicada', 'oculta' => 'Oculta'];

    protected $fillable = ['tenant_id', 'pedido_id', 'cliente_nombre', 'calificacion', 'comentario', 'estado', 'ip'];

    protected $casts = ['calificacion' => 'integer'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function scopePublicadas(Builder $q): Builder
    {
        return $q->where('estado', 'publicada');
    }
}
