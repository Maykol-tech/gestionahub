<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Lead captado en la landing SaaS (formulario o chat). Va al CRM de la plataforma. */
class LandingLead extends Model
{
    protected $table = 'landing_leads';

    public const ESTADOS = ['nuevo', 'contactado', 'interesado', 'cerrado', 'descartado'];

    protected $fillable = ['nombre', 'email', 'telefono', 'mensaje', 'fuente', 'utm', 'estado'];

    protected $casts = ['utm' => 'array'];
}
