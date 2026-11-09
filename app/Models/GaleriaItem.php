<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Trabajo del portafolio del negocio, visible en su pagina publica. */
class GaleriaItem extends Model
{
    protected $table = 'galeria_items';

    protected $fillable = ['tenant_id', 'titulo', 'descripcion', 'imagen_url', 'orden', 'activo'];

    protected $casts = ['activo' => 'boolean', 'orden' => 'integer'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
