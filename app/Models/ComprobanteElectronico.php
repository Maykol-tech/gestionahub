<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Boleta, factura o nota de credito electronica (SUNAT vía Nubefact o modo simulado). */
class ComprobanteElectronico extends Model
{
    protected $table = 'comprobantes_electronicos';

    public const TIPOS = ['01' => 'Factura', '03' => 'Boleta', '07' => 'Nota de crédito'];

    public const ESTADOS = ['pendiente', 'aceptado', 'rechazado', 'error', 'anulado'];

    /** Clase de badge por estado (estilos del panel). */
    public const BADGES = [
        'pendiente' => 'b-a', 'aceptado' => 'b-ok', 'rechazado' => 'b-r', 'error' => 'b-r', 'anulado' => 'b-n',
    ];

    protected $fillable = [
        'tenant_id', 'pedido_id', 'venta_pos_id', 'referencia_id', 'tipo', 'serie', 'numero',
        'cliente_tipo_documento', 'cliente_documento', 'cliente_nombre', 'cliente_direccion',
        'subtotal', 'igv', 'total', 'estado', 'aceptado', 'simulado', 'mensaje',
        'enlace_pdf', 'enlace_xml', 'enlace_cdr', 'codigo_hash', 'payload', 'emitido_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2', 'igv' => 'decimal:2', 'total' => 'decimal:2',
        'aceptado' => 'boolean', 'simulado' => 'boolean', 'payload' => 'array', 'emitido_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function ventaPos()
    {
        return $this->belongsTo(VentaPos::class, 'venta_pos_id');
    }

    /** Comprobante al que modifica una nota de credito. */
    public function referencia()
    {
        return $this->belongsTo(self::class, 'referencia_id');
    }

    public function notas()
    {
        return $this->hasMany(self::class, 'referencia_id');
    }

    public function getNombreTipoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getNumeroCompletoAttribute(): string
    {
        return $this->serie . '-' . str_pad((string) $this->numero, 8, '0', STR_PAD_LEFT);
    }

    public function getBadgeAttribute(): string
    {
        return self::BADGES[$this->estado] ?? 'b-n';
    }
}
