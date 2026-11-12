<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Repartidor del negocio. Columnas tomadas de la migracion completar_tablas_pedidos (+ placa).
 * Usado por Pedido (repartidor_id), Support\Roles (user_id), Reportes y la app del repartidor.
 */
class Repartidor extends Model
{
    use SoftDeletes;

    protected $table = 'repartidores';

    /** Vehiculos permitidos: clave guardada => texto para mostrar. */
    public const VEHICULOS = ['moto' => 'Moto', 'bicicleta' => 'Bicicleta', 'auto' => 'Auto', 'pie' => 'A pie'];

    protected $fillable = [
        'tenant_id', 'user_id', 'nombre', 'telefono', 'dni', 'vehiculo', 'placa',
        'activo', 'disponible', 'lat_actual', 'lng_actual', 'updated_location_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'disponible' => 'boolean',
        'lat_actual' => 'float',
        'lng_actual' => 'float',
        'updated_location_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'repartidor_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
