<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Ingreso registrado a mano, fuera de pedidos y del POS (GEST-66). */
class IngresoManual extends Model
{
    protected $table = 'ingresos_manuales';

    protected $fillable = ['tenant_id', 'fecha', 'concepto', 'monto', 'categoria', 'notas'];

    protected $casts = ['fecha' => 'date', 'monto' => 'decimal:2'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
