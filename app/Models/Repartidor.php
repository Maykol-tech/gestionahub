<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Repartidor extends Model
{
    use HasFactory;

    protected $table = 'repartidores';

    protected $fillable = [
        'tenant_id', 'nombre', 'telefono', 'vehiculo', 'activo', 'disponible', 'lat_actual', 'lng_actual'
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}