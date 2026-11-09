<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Servicio extends Model
{
    use HasFactory;

    protected $table = 'servicios_citas';
    protected $fillable = ['tenant_id', 'nombre', 'duracion_min', 'precio', 'color', 'activo'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}