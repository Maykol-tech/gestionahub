<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Linea de un pedido: producto (o variante), cantidad y precio al momento de la compra.
 *
 * La tabla real (2026_11_05_120000_create_pedidos_tables) usa tenant_id, variante_id,
 * precio_unit y notas. Se acepta tambien "precio_unitario" (lo usa la tienda publica)
 * y, si no llega tenant_id, se toma del pedido.
 */
class PedidoItem extends Model
{
    protected $table = 'pedido_items';

    protected $fillable = [
        'tenant_id', 'pedido_id', 'producto_id', 'variante_id', 'nombre', 'cantidad',
        'precio_unit', 'precio_unitario', 'subtotal', 'notas',
    ];

    protected $casts = ['cantidad' => 'integer', 'precio_unit' => 'decimal:2', 'subtotal' => 'decimal:2'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (PedidoItem $item) {
            if (! $item->tenant_id && $item->pedido_id) {
                $item->tenant_id = Pedido::withoutGlobalScopes()->whereKey($item->pedido_id)->value('tenant_id');
            }
        });
    }

    /** Alias de compatibilidad: precio_unitario se guarda en la columna precio_unit. */
    public function setPrecioUnitarioAttribute($valor): void
    {
        $this->attributes['precio_unit'] = $valor;
    }

    public function getPrecioUnitarioAttribute()
    {
        return $this->precio_unit;
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'variante_id');
    }
}
