<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Menu numerado del bot: se abre con una palabra y el cliente responde con el numero de la opcion. */
class BotMenu extends Model
{
    protected $table = 'bot_menus';

    protected $fillable = ['tenant_id', 'disparador', 'titulo', 'opciones', 'activo'];

    protected $casts = ['opciones' => 'array', 'activo' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
