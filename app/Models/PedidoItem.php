<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Linea de un pedido: producto, cantidad y precio al momento de la compra. */
class PedidoItem extends Model
{
    protected $table = 'pedido_items';

    protected $fillable = ['pedido_id', 'producto_id', 'nombre', 'cantidad', 'precio_unitario', 'subtotal'];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
