<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Registro de cada mensaje de WhatsApp enviado o recibido. */
class WhatsappMensaje extends Model
{
    protected $table = 'whatsapp_mensajes';

    protected $fillable = ['tenant_id', 'pedido_id', 'direccion', 'telefono', 'tipo', 'texto', 'estado', 'meta_id', 'error'];
}
