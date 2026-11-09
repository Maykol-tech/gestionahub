<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Linea de una receta: cuanto de un insumo se usa para una tanda. */
class RecetaInsumo extends Model
{
    protected $table = 'receta_insumos';

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'receta_id', 'insumo_id', 'cantidad'];

    protected $casts = ['cantidad' => 'float'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }
}
