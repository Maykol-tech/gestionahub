<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Pago reportado por fuera de la pasarela: Yape, Plin o transferencia. Lo confirma el negocio. */
class PagoManual extends Model
{
    protected $table = 'pagos_manuales';

    public const METODOS = ['yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia'];

    public const ESTADOS = ['pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'rechazado' => 'Rechazado'];

    protected $fillable = [
        'tenant_id', 'pedido_id', 'metodo', 'monto', 'referencia', 'estado', 'notas', 'confirmado_at',
    ];

    protected $casts = ['monto' => 'decimal:2', 'confirmado_at' => 'datetime'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
