<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $fillable = ['tenant_id', 'concepto', 'monto', 'fecha', 'metodo', 'notas', 'categoria', 'comprobante', 'sucursal_id', 'registrado_por'];

    public const CATEGORIAS = [
        'insumos' => 'Insumos y mercadería', 'planilla' => 'Planilla y sueldos', 'alquiler' => 'Alquiler',
        'servicios' => 'Luz, agua e internet', 'marketing' => 'Publicidad', 'transporte' => 'Transporte y delivery',
        'impuestos' => 'Impuestos', 'mantenimiento' => 'Mantenimiento', 'otros' => 'Otros',
    ];

    public const METODOS = ['efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];

    protected $casts = ['monto' => 'float', 'fecha' => 'date:Y-m-d'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
