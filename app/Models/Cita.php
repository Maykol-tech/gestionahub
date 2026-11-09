<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cita agendada para un servicio. Ocupa el horario desde "inicio" durante "duracion_min". */
class Cita extends Model
{
    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'completada' => 'Completada',
        'no_asistio' => 'No asistió',
        'cancelada' => 'Cancelada',
    ];

    protected $table = 'citas';

    protected $fillable = [
        'tenant_id', 'servicio_id', 'crm_cliente_id', 'cliente_nombre', 'cliente_telefono',
        'inicio', 'duracion_min', 'precio', 'estado', 'notas', 'origen', 'recordatorio_enviado_at',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'duracion_min' => 'integer',
        'precio' => 'float',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function fin(): Carbon
    {
        return $this->inicio->copy()->addMinutes((int) $this->duracion_min);
    }
}
