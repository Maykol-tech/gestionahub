<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Campana de WhatsApp a un segmento de clientes, enviada ahora o programada. */
class CampanaWhatsapp extends Model
{
    protected $table = 'campanas_whatsapp';

    public const SEGMENTOS = [
        'todos' => 'Todos los clientes',
        'frecuentes' => 'Frecuentes (3 o más pedidos)',
        'inactivos' => 'Inactivos (sin compras en 60 días)',
    ];

    public const ESTADOS = ['borrador', 'programada', 'enviada', 'cancelada'];

    protected $fillable = [
        'tenant_id', 'nombre', 'segmento', 'plantilla', 'texto', 'estado', 'programada_para',
        'enviados', 'fallidos', 'enviada_at',
    ];

    protected $casts = [
        'programada_para' => 'datetime', 'enviada_at' => 'datetime',
        'enviados' => 'integer', 'fallidos' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
