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
        'direccion', 'distrito', 'fuente', 'score_lealtad', 'notas', 'activo',
        'etiquetas', 'cumpleanos', 'tarjeta_token',
    ];

    protected $casts = [
        'etiquetas' => 'array',
        'cumpleanos' => 'date',
    ];

    /** Código de la tarjeta digital; se crea la primera vez que se pide. */
    public function tokenTarjeta(): string
    {
        if (! $this->tarjeta_token) {
            $this->forceFill(['tarjeta_token' => \Illuminate\Support\Str::random(32)])->saveQuietly();
        }

        return $this->tarjeta_token;
    }

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}