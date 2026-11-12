<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LandingConfig extends Model {
    protected $guarded = [];
    protected $casts = [
        'popup_activo' => 'boolean',
        'popup_exit_intent' => 'boolean',
        'popup_beneficios' => 'array',
    ];

    public static function defaults(): self {
        return new self([
            'hero_title' => 'Potencia tu negocio con GestionaHub',
            'hero_subtitle' => 'El sistema SaaS integral para gestionar ventas, inventario y delivery.',
            'popup_activo' => true,
            'popup_delay_seconds' => 30,
            'popup_exit_intent' => true,
            'popup_frecuencia_dias' => 7,
            'popup_cuenta_regresiva_min' => 10,
            'popup_badge' => 'Oferta por tiempo limitado',
            'popup_titulo' => 'Tu primer mes al 50% y configuración gratis',
            'popup_texto' => 'Solo por hoy. Aprovecha y empieza con el pie derecho.',
            'popup_beneficios' => ['Primer mes a mitad de precio', 'Setup y migración gratis', 'Soporte prioritario 24/7'],
            'popup_cta' => 'Quiero mi beneficio',
            'popup_oferta_codigo' => 'POPUP50',
        ]);
    }
}