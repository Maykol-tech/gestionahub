<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Forma de entrega que ofrece el negocio: delivery con costo o recojo en tienda (GEST-109). */
class FormaEntrega extends Model
{
    protected $table = 'formas_entrega';

    public const TIPOS = ['delivery' => 'Delivery', 'recojo' => 'Recojo en tienda'];

    protected $fillable = ['tenant_id', 'nombre', 'tipo', 'costo', 'tiempo_estimado', 'activa', 'orden'];

    protected $casts = ['costo' => 'decimal:2', 'activa' => 'boolean', 'orden' => 'integer'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
