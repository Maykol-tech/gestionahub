<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Forma de entrega que ofrece el negocio: delivery con costo o recojo en tienda (GEST-109). */
class FormaEntrega extends Model
{
    protected $table = 'formas_entrega';

    public const TIPOS = ['delivery' => 'Delivery propio', 'recojo' => 'Recojo en tienda', 'agencia' => 'Envío por agencia', 'local' => 'Consumo en el local'];

    protected $fillable = ['tenant_id', 'nombre', 'tipo', 'costo', 'tiempo_estimado', 'activa', 'orden', 'monto_minimo', 'gratis_desde', 'usa_zonas', 'descripcion'];

    protected $casts = ['costo' => 'decimal:2', 'activa' => 'boolean', 'orden' => 'integer', 'monto_minimo' => 'decimal:2', 'gratis_desde' => 'decimal:2', 'usa_zonas' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
