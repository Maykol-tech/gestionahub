<?php

namespace App\Models;

use App\Support\Cms;
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
        // GEST-101: sin testimonios inventados; la sección aparece cuando el superadmin agrega testimonios reales en el CMS
        'testimonios' => ['items' => []],
        'faq' => [
            'items' => [
                ['pregunta' => '¿Necesito instalar algo?', 'respuesta' => 'No. GestionaHub funciona en el navegador de tu computadora, tablet o celular. Solo necesitas internet.'],
                ['pregunta' => '¿Puedo emitir boletas y facturas electrónicas?', 'respuesta' => 'Sí. Nos conectamos con SUNAT a través de un operador autorizado y emites desde el punto de venta o desde cada pedido.'],
                ['pregunta' => '¿Qué pasa cuando termina la prueba gratis?', 'respuesta' => 'Eliges un plan y sigues trabajando con todos tus datos. Si no pagas, tu cuenta queda en solo lectura; nunca borramos tu información.'],
                ['pregunta' => '¿Cómo pago la suscripción?', 'respuesta' => 'Con tarjeta, Yape, Plin o transferencia. Puedes pagar mes a mes o un año con dos meses gratis.'],
                ['pregunta' => '¿Sirve si tengo varias sucursales?', 'respuesta' => 'Sí. Cada sucursal tiene su caja, su stock y sus reportes, y tú ves todo junto desde un solo panel.'],
            ],
        ],
        'pie' => ['texto' => '© GestionaHub. Todos los derechos reservados.', 'email' => ''],
        // GEST-101: cifras de la portada. Van ocultas hasta que el superadmin ponga datos reales.
        'cifras' => [
            'mostrar' => false,
            'items' => [
                ['valor' => 0, 'sufijo' => '+', 'texto' => 'negocios activos'],
                ['valor' => 0, 'sufijo' => 'k+', 'texto' => 'pedidos al mes'],
                ['valor' => 0, 'sufijo' => '%', 'texto' => 'comprobantes aceptados'],
                ['valor' => 0, 'sufijo' => ' min', 'texto' => 'para cerrar caja'],
            ],
        ],
        // GEST-101: datos reales de la empresa que se muestran en la landing y el registro
        'empresa' => ['razon_social' => '', 'ruc' => '', 'whatsapp' => '', 'video_url' => '', 'terminos_url' => '', 'privacidad_url' => ''],
        'analitica' => ['gtm_id' => '', 'utm_activo' => true],
        'blog' => ['url' => '', 'titulo' => 'Consejos para tu negocio', 'cantidad' => 3],
        'popup' => ['activo' => false, 'titulo' => '¿Te vas sin probarlo?', 'texto' => 'Déjanos tu correo y te contactamos para una demo.', 'segundos' => 30, 'oferta' => 'POPUP50'],
    ];

    /** Contenido final: lo guardado sobre los valores por defecto. */
    public static function todo(): array
    {
        $guardado = static::query()->pluck('contenido', 'seccion')->all();

        $todo = array_replace_recursive(self::DEFECTO, $guardado);

        // GEST-88/98: si el superadmin cargó filas en las tablas CMS, mandan sobre el JSON
        $faqs = Cms::lista('faqs');
        if ($faqs) {
            $todo['faq']['items'] = array_map(fn ($f) => ['pregunta' => $f['pregunta'], 'respuesta' => $f['respuesta']], $faqs);
        }
        $testimonios = Cms::lista('testimonios');
        if ($testimonios) {
            $todo['testimonios']['items'] = array_map(fn ($t) => [
                'nombre' => $t['nombre'], 'negocio' => (string) $t['negocio'], 'texto' => $t['texto'],
                'foto_url' => $t['foto_url'], 'estrellas' => (int) $t['estrellas'],
            ], $testimonios);
        }
        $todo['logos'] = Cms::lista('logos');
        $todo['secciones'] = Cms::secciones();

        return $todo;
    }

    /** GEST-99: solo el ID de GTM (una sola consulta). */
    public static function gtm(): string
    {
        try {
            $fila = static::query()->where('seccion', 'analitica')->value('contenido');
            $datos = is_array($fila) ? $fila : (json_decode((string) $fila, true) ?: []);
            $id = (string) ($datos['gtm_id'] ?? '');
        } catch (\Throwable $e) {
            $id = '';
        }

        return preg_match('/^GTM-[A-Z0-9]{4,12}$/', $id) ? $id : '';
    }

    public static function guardar(string $seccion, array $contenido): void
    {
        static::query()->updateOrCreate(['seccion' => $seccion], ['contenido' => $contenido]);
    }
}
