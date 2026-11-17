<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Lead captado en la landing SaaS (formulario, popup, chat, registro o checkout). Va al CRM de la plataforma. */
class LandingLead extends Model
{
    protected $table = 'landing_leads';

    /** GEST-90: embudo de ventas de la plataforma. */
    public const ESTADOS = ['nuevo', 'contactado', 'demo', 'ganado', 'perdido'];

    public const NOMBRES_ESTADO = [
        'nuevo' => 'Nuevo', 'contactado' => 'Contactado', 'demo' => 'Demo', 'ganado' => 'Ganado', 'perdido' => 'Perdido',
    ];

    /** Estados antiguos que ya existen en la base: se muestran con su equivalente nuevo. */
    public const EQUIVALENTES = ['interesado' => 'demo', 'cerrado' => 'ganado', 'descartado' => 'perdido'];

    /** Orígenes del lead. */
    public const FUENTES = ['landing', 'popup', 'chatbot', 'registro', 'checkout', 'google'];

    protected $fillable = [
        'nombre', 'email', 'telefono', 'mensaje', 'fuente', 'oferta', 'utm', 'estado',
        'utm_source', 'utm_medium', 'utm_campaign', 'notas', 'tenant_id', 'contactado_at',
    ];

    protected $casts = ['utm' => 'array', 'contactado_at' => 'datetime'];

    public function getEtapaAttribute(): string
    {
        return self::EQUIVALENTES[$this->estado] ?? (in_array($this->estado, self::ESTADOS, true) ? $this->estado : 'nuevo');
    }

    /** Guarda un lead con sus UTM en columnas propias y también en el JSON (compatibilidad). */
    public static function registrar(array $datos, array $utm = []): self
    {
        $utm = array_filter($utm, fn ($v) => is_string($v) && $v !== '');

        // Si ya existe un lead abierto con el mismo correo o teléfono, se actualiza en vez de duplicar
        $existente = null;
        if (! empty($datos['email']) || ! empty($datos['telefono'])) {
            $existente = static::query()->whereNotIn('estado', ['ganado', 'perdido', 'cerrado', 'descartado'])
                ->where(function ($q) use ($datos) {
                    if (! empty($datos['email'])) $q->orWhere('email', $datos['email']);
                    if (! empty($datos['telefono'])) $q->orWhere('telefono', $datos['telefono']);
                })->latest('id')->first();
        }

        $fila = array_filter($datos, fn ($v) => $v !== null && $v !== '') + [
            'utm' => $utm ?: null,
            'utm_source' => $utm['utm_source'] ?? null,
            'utm_medium' => $utm['utm_medium'] ?? null,
            'utm_campaign' => $utm['utm_campaign'] ?? null,
        ];

        if ($existente) {
            $notas = trim(($existente->notas ? $existente->notas . "\n" : '') . now()->format('d/m H:i') . ' · volvió por ' . ($datos['fuente'] ?? 'landing')
                . (! empty($datos['mensaje']) ? ': ' . $datos['mensaje'] : ''));
            $existente->fill(array_filter([
                'telefono' => $existente->telefono ?: ($datos['telefono'] ?? null),
                'email' => $existente->email ?: ($datos['email'] ?? null),
                'tenant_id' => $existente->tenant_id ?: ($datos['tenant_id'] ?? null),
                'notas' => $notas,
            ]))->save();

            return $existente;
        }

        return static::create($fila);
    }
}
