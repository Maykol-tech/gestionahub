<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepartidorUbicacion extends Model
{
    protected $table = 'repartidor_ubicaciones';

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'repartidor_id', 'pedido_id', 'lat', 'lng', 'precision_m', 'created_at'];

    protected $casts = ['lat' => 'float', 'lng' => 'float', 'created_at' => 'datetime'];
}
