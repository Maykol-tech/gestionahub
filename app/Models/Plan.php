<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    // Le decimos a Laravel el nombre exacto de la tabla
    protected $table = 'planes_saas';

    protected $fillable = [
        'nombre',
        'slug',
        'precio_mensual',
        'precio_anual',
        'max_usuarios',
        'max_sucursales',
        'modulos_json',
        'activo'
    ];
}