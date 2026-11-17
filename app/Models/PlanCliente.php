<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Plan que el negocio vende a sus clientes (membresía, menú semanal...). GEST-57 */
class PlanCliente extends Model
{
    public const PERIODOS = ['semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual', 'anual' => 'Anual'];

    protected $table = 'planes_cliente';

    protected $fillable = ['tenant_id', 'nombre', 'descripcion', 'precio', 'periodicidad', 'beneficios', 'activo'];

    protected $casts = ['precio' => 'float', 'beneficios' => 'array', 'activo' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
