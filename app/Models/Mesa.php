<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Mesa del local. Cada mesa tiene un token que va en la URL de su codigo QR. */
class Mesa extends Model
{
    protected $table = 'mesas';

    public const ESTADOS = ['libre' => 'Libre', 'ocupada' => 'Ocupada', 'por_cobrar' => 'Por cobrar'];

    protected $fillable = ['tenant_id', 'nombre', 'capacidad', 'estado', 'token', 'activo'];

    protected $casts = ['capacidad' => 'integer', 'activo' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Mesa $mesa) {
            $mesa->token = $mesa->token ?: Str::random(40);
            $mesa->estado = $mesa->estado ?: 'libre';
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function comandas()
    {
        return $this->hasMany(Comanda::class, 'mesa_id');
    }

    public function comandasAbiertas()
    {
        return $this->comandas()->whereIn('estado', Comanda::ABIERTAS);
    }

    public function urlPublica(): string
    {
        return url('mesa/' . $this->token);
    }
}
