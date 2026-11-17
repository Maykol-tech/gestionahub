<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Historial de puntos de un cliente: cada fila explica por que cambio su saldo. */
class FidelizacionMovimiento extends Model
{
    protected $table = 'fidelizacion_movimientos';

    protected $fillable = ['tenant_id', 'cliente_id', 'pedido_id', 'venta_pos_id', 'tipo', 'puntos', 'saldo_resultante', 'descripcion'];

    protected $casts = ['puntos' => 'integer'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
