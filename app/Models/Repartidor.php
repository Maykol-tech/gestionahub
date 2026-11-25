<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Repartidor del negocio. Columnas tomadas de la migracion completar_tablas_pedidos (+ placa).
 * Usado por Pedido (repartidor_id), Support\Roles (user_id), Reportes y la app del repartidor.
 */
class Repartidor extends Model
{
    use \App\Models\Concerns\ColumnasAntiguas;

    /** Columnas con otro nombre en bases antiguas (ver ColumnasAntiguas). */
    protected const COLUMNAS_ANTIGUAS = ['nombre' => 'nombre_completo'];

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
        // Cada repartidor nace con su enlace privado para la app (/r/{token})
        static::creating(function (Repartidor $r) {
            $r->token ??= Str::random(40);
        });
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
