<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Venta de mostrador (POS): se cobra al momento, descuenta stock y entra al cierre de caja. */
class VentaPos extends Model
{
    public const METODOS = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'yape' => 'Yape',
        'plin' => 'Plin',
        'transferencia' => 'Transferencia',
    ];

    protected $table = 'ventas_pos';

    protected $fillable = ['tenant_id', 'numero', 'metodo_pago', 'total', 'recibido', 'vuelto', 'cliente_nombre', 'items'];

    protected $casts = [
        'items' => 'array',
        'total' => 'float',
        'recibido' => 'float',
        'vuelto' => 'float',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
