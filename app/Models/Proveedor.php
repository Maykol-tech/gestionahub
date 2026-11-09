<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'tenant_id', 'nombre', 'ruc', 'contacto', 'telefono', 'email', 'notas', 'activo'
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}