<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cobro de un periodo de una suscripción de cliente. GEST-57 */
class CobroSuscripcion extends Model
{
    public const ESTADOS = ['pendiente' => 'Por pagar', 'por_revisar' => 'Por revisar', 'pagado' => 'Pagado', 'vencido' => 'Vencido', 'anulado' => 'Anulado'];

    protected $table = 'cobros_suscripcion';

    protected $fillable = ['tenant_id', 'suscripcion_id', 'monto', 'desde', 'hasta', 'vence', 'estado', 'metodo', 'referencia', 'captura', 'pagado_at', 'avisado_at'];

    protected $casts = ['monto' => 'float', 'desde' => 'date', 'hasta' => 'date', 'vence' => 'date', 'pagado_at' => 'datetime', 'avisado_at' => 'datetime'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function suscripcion(): BelongsTo
    {
        return $this->belongsTo(SuscripcionCliente::class, 'suscripcion_id')->withoutGlobalScopes();
    }
}
