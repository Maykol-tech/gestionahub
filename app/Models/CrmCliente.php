<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class CrmCliente extends Model
{
    use HasFactory;

    protected $table = 'crm_clientes';

    protected $fillable = [
        'tenant_id', 'nombre', 'telefono', 'email', 'dni', 
        'direccion', 'distrito', 'fuente', 'score_lealtad', 'notas', 'activo'
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}