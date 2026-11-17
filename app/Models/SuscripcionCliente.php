<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Un cliente suscrito a un plan del negocio. GEST-57 */
class SuscripcionCliente extends Model
{
    public const ESTADOS = ['pendiente' => 'Por pagar', 'activa' => 'Activa', 'vencida' => 'Vencida', 'cancelada' => 'Cancelada'];

    protected $table = 'suscripciones_cliente';

    protected $fillable = ['tenant_id', 'plan_id', 'cliente_id', 'estado', 'precio', 'periodicidad', 'inicio', 'proximo_cobro', 'cancelada_at', 'token'];

    protected $casts = ['precio' => 'float', 'inicio' => 'date', 'proximo_cobro' => 'date', 'cancelada_at' => 'datetime'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanCliente::class, 'plan_id')->withoutGlobalScopes();
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(CrmCliente::class, 'cliente_id')->withoutGlobalScopes();
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(CobroSuscripcion::class, 'suscripcion_id')->withoutGlobalScopes();
    }
}
