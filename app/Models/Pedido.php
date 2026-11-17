<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Support\Fidelizacion;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'tenant_id', 'cliente_id', 'repartidor_id', 'zona_id', 'numero', 'canal', 'estado', 'tipo',
        'subtotal', 'descuento', 'costo_envio', 'igv', 'total', 'direccion_entrega', 'lat_entrega', 'lng_entrega',
        'notas', 'metodo_pago', 'pagado', 'pago_ref', 'programado_para', 'cliente_nombre', 'cliente_telefono', 'forma_entrega_id', 'cupon_codigo', 'puntos_usados', 'whatsapp_ok', 'confirmado_at', 'asignado_at', 'entregado_at', 'cancelado_at', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2', 'descuento' => 'decimal:2', 'costo_envio' => 'decimal:2',
        'igv' => 'decimal:2', 'total' => 'decimal:2', 'pagado' => 'boolean',
        'lat_entrega' => 'float', 'lng_entrega' => 'float',
        'confirmado_at' => 'datetime', 'programado_para' => 'datetime', 'asignado_at' => 'datetime', 'entregado_at' => 'datetime', 'cancelado_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        // Puntos de fidelizacion al pagar o entregar; se revierten si se cancela
        static::updated(fn (Pedido $pedido) => Fidelizacion::alActualizarPedido($pedido));

        // GEST-62: aviso al sistema del negocio (webhook) cuando cambia el estado
        static::updated(function (Pedido $pedido) {
            if ($pedido->wasChanged('estado')) {
                \App\Support\PedidosApi::alCambiarEstado($pedido, $pedido->getOriginal('estado'));
            }
        });
    }

    public function cliente()
    {
        return $this->belongsTo(CrmCliente::class, 'cliente_id');
    }

    public function repartidor()
    {
        return $this->belongsTo(Repartidor::class, 'repartidor_id');
    }

    public function zona()
    {
        return $this->belongsTo(ZonaDelivery::class, 'zona_id');
    }

    public function items()
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }

    public function comprobantes()
    {
        return $this->hasMany(ComprobanteElectronico::class, 'pedido_id');
    }

    /** Siguiente numero correlativo del negocio: P-0001, P-0002... */
    public static function siguienteNumero(int $tenantId): string
    {
        $ultimo = static::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->where('numero', 'like', 'P-%')->orderByDesc('id')->value('numero');
        $n = $ultimo ? ((int) substr($ultimo, 2)) + 1 : 1;

        return 'P-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
