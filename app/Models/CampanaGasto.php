<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Gasto real de una campana en una fecha: lo que se fue en anuncios. */
class CampanaGasto extends Model
{
    protected $table = 'campana_gastos';

    protected $fillable = ['tenant_id', 'campana_id', 'fecha', 'monto', 'concepto'];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'float',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(CampanaPublicidad::class, 'campana_id');
    }
}
