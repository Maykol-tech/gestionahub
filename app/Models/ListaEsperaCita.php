<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Cliente en espera de un horario. Si se cancela una cita de ese dia, se le avisa por WhatsApp. */
class ListaEsperaCita extends Model
{
    protected $table = 'lista_espera_citas';

    public const ESTADOS = ['esperando' => 'Esperando', 'avisado' => 'Avisado', 'reservado' => 'Reservado'];

    protected $fillable = [
        'tenant_id', 'servicio_id', 'cliente_nombre', 'cliente_telefono', 'fecha_deseada',
        'estado', 'cita_origen_id', 'avisado_at',
    ];

    protected $casts = ['fecha_deseada' => 'date', 'avisado_at' => 'datetime'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }
}
