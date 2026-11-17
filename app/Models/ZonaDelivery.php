<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class ZonaDelivery extends Model
{
    protected $table = 'zonas_delivery';

    protected $fillable = ['tenant_id', 'nombre', 'costo', 'tiempo_min', 'color', 'poligono', 'activo', 'orden'];

    protected $casts = [
        'costo' => 'decimal:2', 'tiempo_min' => 'integer', 'poligono' => 'array', 'activo' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'zona_id');
    }

    /** true si el punto (lat, lng) cae dentro del poligono de la zona. */
    public function contiene(float $lat, float $lng): bool
    {
        $puntos = $this->poligono ?? [];
        $dentro = false;
        for ($i = 0, $j = count($puntos) - 1; $i < count($puntos); $j = $i++) {
            [$yi, $xi] = $puntos[$i];
            [$yj, $xj] = $puntos[$j];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $dentro = ! $dentro;
            }
        }

        return $dentro;
    }

    /** Zona activa del negocio que contiene el punto, o null. */
    public static function paraPunto(int $tenantId, float $lat, float $lng): ?self
    {
        return static::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activo', true)
            ->orderBy('orden')->get()->first(fn ($z) => $z->contiene($lat, $lng));
    }
}
