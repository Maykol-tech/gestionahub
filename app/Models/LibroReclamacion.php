<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Hoja de reclamacion del libro de reclamaciones de la landing (INDECOPI). */
class LibroReclamacion extends Model
{
    protected $table = 'libro_reclamaciones';

    public const TIPOS = ['reclamo' => 'Reclamo', 'queja' => 'Queja'];

    public const DOCUMENTOS = ['DNI', 'CE', 'RUC'];

    protected $fillable = [
        'numero', 'tipo', 'nombre', 'documento_tipo', 'documento', 'email', 'telefono', 'pedido', 'detalle', 'ip',
    ];
}
