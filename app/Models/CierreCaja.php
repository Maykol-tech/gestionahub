<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Cierre de caja de un dia: lo que debia haber en efectivo contra lo que se conto. */
class CierreCaja extends Model
{
    protected $table = 'cierres_caja';

    protected $fillable = [
        'tenant_id', 'fecha', 'monto_inicial', 'ventas_efectivo', 'ventas_digitales',
        'gastos_efectivo', 'gastos_digitales', 'esperado', 'contado', 'diferencia', 'cerrado_por', 'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_inicial' => 'float',
        'ventas_efectivo' => 'float',
        'ventas_digitales' => 'float',
        'gastos_efectivo' => 'float',
        'gastos_digitales' => 'float',
        'esperado' => 'float',
        'contado' => 'float',
        'diferencia' => 'float',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
