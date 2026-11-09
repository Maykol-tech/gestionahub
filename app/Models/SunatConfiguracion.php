<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/** Credenciales y series de facturacion electronica del negocio (Nubefact). */
class SunatConfiguracion extends Model
{
    protected $table = 'sunat_configuraciones';

    protected $fillable = ['tenant_id', 'ruta', 'token', 'serie_factura', 'serie_boleta', 'ambiente', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    protected $hidden = ['token'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tokenClaro(): string
    {
        $crudo = $this->getRawOriginal('token');

        return $crudo ? Crypt::decryptString($crudo) : '';
    }

    public function guardarToken(string $tokenClaro): void
    {
        $this->token = $tokenClaro === '' ? null : Crypt::encryptString($tokenClaro);
    }
}
