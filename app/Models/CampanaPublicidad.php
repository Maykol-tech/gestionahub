<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Campana de publicidad con presupuesto, metricas de impacto y gastos registrados. */
class CampanaPublicidad extends Model
{
    use \App\Models\Concerns\ColumnasAntiguas;

    /** Columnas con otro nombre en bases antiguas (ver ColumnasAntiguas). */
    protected const COLUMNAS_ANTIGUAS = ['canal' => 'plataforma', 'activo' => 'activa'];

    protected $table = 'campanas_publicidad';

    protected $fillable = [
        'tenant_id', 'nombre', 'canal', 'objetivo', 'inversion', 'alcance', 'clics', 'conversiones',
        'ventas_atribuidas', 'fecha_inicio', 'fecha_fin', 'activo', 'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'inversion' => 'float',
        'ventas_atribuidas' => 'float',
        'alcance' => 'integer',
        'clics' => 'integer',
        'conversiones' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(CampanaGasto::class, 'campana_id')->orderByDesc('fecha')->orderByDesc('id');
    }
}
