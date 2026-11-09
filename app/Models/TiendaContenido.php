<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/** Textos de la tienda del negocio, editables desde el panel (GEST-110). */
class TiendaContenido extends Model
{
    protected $table = 'tienda_contenido';

    protected $fillable = ['tenant_id', 'seccion', 'contenido'];

    protected $casts = ['contenido' => 'array'];

    public const DEFECTO = [
        'portada' => ['titulo' => '', 'subtitulo' => ''],
        'pedido' => ['mensaje_confirmacion' => 'Gracias por tu pedido. Te escribiremos por WhatsApp para confirmarlo.'],
        'pie' => ['texto' => '', 'email' => ''],
        // SEO de la tienda (GEST-112): se usa en <title>, meta description y Open Graph
        'seo' => ['titulo' => '', 'descripcion' => '', 'imagen' => ''],
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    /** Contenido final de la tienda: lo guardado sobre los valores por defecto. */
    public static function todo(int $tenantId): array
    {
        $guardado = static::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->pluck('contenido', 'seccion')->all();

        return array_replace_recursive(self::DEFECTO, $guardado);
    }
}
