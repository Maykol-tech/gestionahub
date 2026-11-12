<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Venta de mostrador (POS): se cobra al momento, descuenta stock y entra al cierre de caja.
 * Los precios llevan IGV incluido; gravada + igv = total.
 */
class VentaPos extends Model
{
    /** Metodos que acepta el cobro (se conserva transferencia para ventas antiguas). */
    public const METODOS = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'yape' => 'Yape',
        'plin' => 'Plin',
        'transferencia' => 'Transferencia',
    ];

    /** Metodos que se muestran en la pantalla del POS. */
    public const METODOS_POS = ['efectivo', 'tarjeta', 'yape', 'plin'];

    public const COMPROBANTES = [
        'ticket' => 'Nota de venta',
        'boleta' => 'Boleta',
        'factura' => 'Factura',
    ];

    protected $table = 'ventas_pos';

    protected $fillable = [
        'tenant_id', 'user_id', 'numero', 'metodo_pago', 'gravada', 'igv', 'total', 'recibido', 'vuelto',
        'cliente_nombre', 'cliente_documento', 'tipo_comprobante', 'items', 'estado', 'anulada_at',
    ];

    protected $casts = [
        'items' => 'array',
        'gravada' => 'float',
        'igv' => 'float',
        'total' => 'float',
        'recibido' => 'float',
        'vuelto' => 'float',
        'anulada_at' => 'datetime',
    ];

    protected $attributes = [
        'estado' => 'pagada',
        'tipo_comprobante' => 'ticket',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    /** Ventas que cuentan para caja y reportes (no anuladas). */
    public function scopeVigentes(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->whereNull('estado')->orWhere('estado', '!=', 'anulada'));
    }

    public function comprobantes()
    {
        return $this->hasMany(ComprobanteElectronico::class, 'venta_pos_id');
    }

    /** Boleta o factura principal de la venta (sin contar notas de credito). */
    public function comprobante()
    {
        return $this->hasOne(ComprobanteElectronico::class, 'venta_pos_id')
            ->ofMany(['id' => 'max'], fn ($q) => $q->whereIn('tipo', ['01', '03']));
    }

    public function getAnuladaAttribute(): bool
    {
        return $this->estado === 'anulada';
    }

    public function getNombreMetodoAttribute(): string
    {
        return self::METODOS[$this->metodo_pago] ?? ucfirst((string) $this->metodo_pago);
    }
}
