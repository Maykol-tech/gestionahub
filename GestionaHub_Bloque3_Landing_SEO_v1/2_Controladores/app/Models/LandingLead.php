<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Lead captado en la landing SaaS (formulario o chat). Va al CRM de la plataforma. */
class LandingLead extends Model
{
    protected $table = 'landing_leads';

    public const ESTADOS = ['nuevo', 'contactado', 'interesado', 'cerrado', 'descartado'];

    /** Origenes del lead: formulario de la landing, popup de salida o chatbot de captura. */
    public const FUENTES = ['landing', 'popup', 'chatbot'];

    protected $fillable = ['nombre', 'email', 'telefono', 'mensaje', 'fuente', 'oferta', 'utm', 'estado'];

    protected $casts = ['utm' => 'array'];
}
