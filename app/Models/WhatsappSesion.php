<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Sesion de conversacion de WhatsApp: una por cliente y negocio. Una sesion activa si hubo mensaje en los ultimos 30 min. */
class WhatsappSesion extends Model
{
    protected $table = 'whatsapp_sesiones';

    public const MINUTOS_ACTIVA = 30;

    protected $fillable = ['tenant_id', 'telefono', 'mensajes', 'ultimo_mensaje_at', 'contexto', 'crm_cliente_id'];

    protected $casts = ['ultimo_mensaje_at' => 'datetime', 'mensajes' => 'integer', 'contexto' => 'array'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    /** Registra un mensaje entrante y devuelve la sesion actualizada. */
    public static function registrar(int $tenantId, string $telefono): self
    {
        $sesion = static::withoutGlobalScopes()->firstOrNew(['tenant_id' => $tenantId, 'telefono' => $telefono]);
        $sesion->mensajes = ($sesion->exists ? (int) $sesion->mensajes : 0) + 1;
        $sesion->ultimo_mensaje_at = now();
        $sesion->save();

        return $sesion;
    }

    public function getActivaAttribute(): bool
    {
        return $this->ultimo_mensaje_at !== null
            && $this->ultimo_mensaje_at->gte(now()->subMinutes(self::MINUTOS_ACTIVA));
    }
}
