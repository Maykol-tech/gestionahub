<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Apertura de caja de un dia: el efectivo con que se empieza el turno (GEST-67). */
class AperturaCaja extends Model
{
    protected $table = 'aperturas_caja';

    protected $fillable = ['tenant_id', 'fecha', 'monto_inicial', 'abierto_por', 'notas'];

    protected $casts = ['fecha' => 'date', 'monto_inicial' => 'float'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
