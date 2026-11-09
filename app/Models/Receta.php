<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Receta: los insumos necesarios para producir "rendimiento" unidades. */
class Receta extends Model
{
    protected $table = 'recetas';

    protected $fillable = ['tenant_id', 'nombre', 'rendimiento', 'notas', 'activo'];

    protected $casts = [
        'rendimiento' => 'integer',
        'activo' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecetaInsumo::class);
    }
}
