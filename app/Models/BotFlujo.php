<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Regla del bot: cuando el mensaje contiene (o es exactamente) el disparador, responde con el texto. */
class BotFlujo extends Model
{
    public const COINCIDENCIAS = [
        'contiene' => 'Contiene la palabra',
        'exacta' => 'Es exactamente igual',
    ];

    protected $table = 'bot_flujos';

    protected $fillable = ['tenant_id', 'nombre', 'disparador', 'coincidencia', 'respuesta', 'orden', 'activo'];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
