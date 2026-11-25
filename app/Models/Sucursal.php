<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use \App\Models\Concerns\ColumnasAntiguas;

    /** Columnas con otro nombre en bases antiguas (ver ColumnasAntiguas). */
    protected const COLUMNAS_ANTIGUAS = ['activo' => 'activa'];

    protected $table = 'sucursales';

    protected $fillable = ['tenant_id', 'nombre', 'direccion', 'telefono', 'email', 'horario', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
