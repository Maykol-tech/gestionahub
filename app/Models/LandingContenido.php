<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Textos y ajustes de la landing SaaS, editables desde el superadmin. Cada seccion es una fila. */
class LandingContenido extends Model
{
    protected $table = 'landing_contenido';

    protected $fillable = ['seccion', 'contenido'];

    protected $casts = ['contenido' => 'array'];

    public const DEFECTO = [
        'hero' => [
            'titulo' => 'Gestiona tu negocio desde un solo panel',
            'subtitulo' => 'Pedidos, cocina, caja, clientes y WhatsApp en un mismo lugar, pensado para negocios peruanos.',
            'cta' => 'Pruébalo gratis 14 días',
        ],
        'beneficios' => [
            'items' => [
                ['titulo' => 'Pedidos y cocina', 'texto' => 'Recibe pedidos de tu tienda, mesas y WhatsApp, y envíalos a cocina al instante.'],
                ['titulo' => 'WhatsApp de tu negocio', 'texto' => 'Responde a tus clientes desde tu propio número, con flujos automáticos.'],
                ['titulo' => 'Caja y comprobantes', 'texto' => 'Cierre de caja, POS y boletas o facturas electrónicas con SUNAT.'],
            ],
        ],
        'pie' => ['texto' => '© GestionaHub. Todos los derechos reservados.', 'email' => ''],
        'analitica' => ['gtm_id' => '', 'utm_activo' => true],
        'popup' => ['activo' => false, 'titulo' => '¿Te vas sin probarlo?', 'texto' => 'Déjanos tu correo y te contactamos para una demo.', 'segundos' => 20],
    ];

    /** Contenido final: lo guardado sobre los valores por defecto. */
    public static function todo(): array
    {
        $guardado = static::query()->pluck('contenido', 'seccion')->all();

        return array_replace_recursive(self::DEFECTO, $guardado);
    }

    public static function guardar(string $seccion, array $contenido): void
    {
        static::query()->updateOrCreate(['seccion' => $seccion], ['contenido' => $contenido]);
    }
}
