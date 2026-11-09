<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/** Conexion de un negocio con su numero de WhatsApp Business. */
class WhatsappConexion extends Model
{
    protected $table = 'whatsapp_conexiones';

    protected $fillable = ['tenant_id', 'phone_id', 'token', 'numero_visible', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    protected $hidden = ['token'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    /** Token en claro, solo para llamar a la API de Meta. */
    public function tokenClaro(): string
    {
        return Crypt::decryptString($this->getRawOriginal('token'));
    }

    public function guardarToken(string $tokenClaro): void
    {
        $this->token = Crypt::encryptString($tokenClaro);
    }
}
