<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Prospecto: posible cliente, cargado a mano o desde la prospeccion automatica. */
class Prospecto extends Model
{
    protected $table = 'prospectos';

    public const ESTADOS = ['nuevo' => 'Nuevo', 'contactado' => 'Contactado', 'interesado' => 'Interesado', 'descartado' => 'Descartado'];

    protected $fillable = ['tenant_id', 'nombre', 'telefono', 'email', 'rubro', 'fuente', 'estado', 'notas'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
