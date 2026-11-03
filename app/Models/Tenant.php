<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $table = 'tenants';

    protected $fillable = [
        'nombre_negocio',
        'ruc',
        'email',
        'telefono',
        'dominio',
        'logo_url',
        'plan_id',
        'estado',
        'trial_ends_at',
    ];

    // Relación: Un tenant tiene muchos usuarios
    public function users()
    {
        return $this->hasMany(User::class);
    }
}