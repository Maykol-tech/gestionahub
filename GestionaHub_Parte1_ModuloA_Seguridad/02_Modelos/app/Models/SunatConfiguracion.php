<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Credenciales y series de facturacion electronica del negocio.
 * Ambientes: simulado (sin conexion, para pruebas y demos), demo (Nubefact pruebas) y produccion.
 */
class SunatConfiguracion extends Model
{
    public const AMBIENTES = [
        'simulado' => 'Simulado (sin conexión a SUNAT)',
        'demo' => 'Nubefact pruebas (demo)',
        'produccion' => 'Nubefact producción',
    ];

    protected $table = 'sunat_configuraciones';

    protected $fillable = ['tenant_id', 'ruta', 'token', 'serie_factura', 'serie_boleta', 'ambiente', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    protected $hidden = ['token'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Configuracion del negocio actual. Si aun no existe se crea en modo simulado,
     * asi el POS puede emitir boletas de prueba desde el primer dia.
     */
    public static function delNegocio(?int $tenantId = null): self
    {
        $tenantId ??= auth()->user()?->tenant_id;

        return static::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['serie_factura' => 'F001', 'serie_boleta' => 'B001', 'ambiente' => 'simulado', 'activo' => true]
        );
    }

    /** True cuando el negocio trabaja en modo simulado: no se llama a Nubefact. */
    public function esSimulado(): bool
    {
        return $this->ambiente === 'simulado';
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
