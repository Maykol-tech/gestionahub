<?php

namespace App\Support;

/**
 * Rubros y plantillas (GEST-82, 83 y 84).
 *  - Cada rubro sugiere categorías, servicios, módulos del menú y textos de ejemplo.
 *  - Cada rubro tiene 3 plantillas (clásica, moderna y minimal) para la landing y la tienda.
 *  - Aplicar una plantilla cambia el diseño y solo completa textos vacíos: no borra lo que el negocio escribió.
 */
class PlantillasRubro
{
    /** Módulos que siempre se ven, sea cual sea el rubro. */
    public const MODULOS_BASE = ['admin.dashboard', 'admin.pedidos', 'admin.productos', 'admin.clientes', 'caja', 'reportes', 'personalizar', 'planes'];

    /** Módulos opcionales que el negocio puede mostrar u ocultar en el menú. */
    public const MODULOS_OPCIONALES = [
        'fidelizacion' => 'Fidelización y puntos',
        'servicios' => 'Servicios y citas',
        'admin.espera' => 'Lista de espera',
        'admin.pos' => 'Punto de venta',
        'admin.comprobantes' => 'Comprobantes SUNAT',
        'cobros' => 'Cobros y pagos',
        'repartos' => 'Repartos',
        'produccion' => 'Producción y recetas',
        'admin.mesas' => 'Mesas y QR',
        'admin.cocina' => 'Pantalla de cocina',
        'proveedores' => 'Proveedores y compras',
        'tienda' => 'Tienda online',
        'admin.galeria' => 'Galería de trabajos',
        'admin.resenas' => 'Reseñas',
        'bot' => 'Bot de WhatsApp',
        'admin.campanas' => 'Campañas de WhatsApp',
        'publicidad' => 'Publicidad',
        'sucursales' => 'Sucursales',
    ];

    public const FUENTES = [
        'Inter' => 'Inter, system-ui, sans-serif',
        'Poppins' => 'Poppins, system-ui, sans-serif',
        'Montserrat' => 'Montserrat, system-ui, sans-serif',
        'Nunito' => 'Nunito, system-ui, sans-serif',
        'Playfair Display' => '"Playfair Display", Georgia, serif',
        'Lora' => 'Lora, Georgia, serif',
    ];

    public const DISENOS = [
        'clasica' => ['nombre' => 'Clásica', 'descripcion' => 'Portada centrada y tarjetas. Segura y fácil de leer.'],
        'moderna' => ['nombre' => 'Moderna', 'descripcion' => 'Portada a dos columnas con degradado de tu color.'],
        'minimal' => ['nombre' => 'Minimal', 'descripcion' => 'Fondo blanco, títulos elegantes y mucho aire.'],
    ];

    public const CATALOGO = [
        'restaurante' => [
            'nombre' => 'Restaurante', 'icono' => '🍽️',
            'categorias' => ['Entradas', 'Platos de fondo', 'Bebidas', 'Postres'],
            'servicios' => ['Delivery', 'Reserva de mesa', 'Menú del día'],
            'modulos' => ['admin.pos', 'admin.comprobantes', 'admin.mesas', 'admin.cocina', 'produccion', 'proveedores', 'repartos', 'tienda', 'fidelizacion', 'admin.resenas', 'bot'],
            'textos' => ['titulo' => 'Comida casera hecha con cariño', 'subtitulo' => 'Pide por WhatsApp, recoge en local o recibe en tu casa.', 'boton' => 'Ver la carta',
                'beneficios' => [['titulo' => 'Delivery rápido', 'texto' => 'Llevamos tu pedido caliente a tu puerta.'], ['titulo' => 'Ingredientes frescos', 'texto' => 'Compramos cada mañana en el mercado.'], ['titulo' => 'Reserva tu mesa', 'texto' => 'Escríbenos y te guardamos un lugar.']]],
            'paletas' => [['#B91C1C', '#F59E0B', 'Poppins'], ['#EA580C', '#1E293B', 'Montserrat'], ['#166534', '#CA8A04', 'Playfair Display']],
        ],
        'cafeteria' => [
            'nombre' => 'Cafetería o panadería', 'icono' => '☕',
            'categorias' => ['Café', 'Panadería', 'Desayunos', 'Pasteles'],
            'servicios' => ['Pedido para llevar', 'Pedido por encargo'],
            'modulos' => ['admin.pos', 'admin.comprobantes', 'produccion', 'proveedores', 'tienda', 'fidelizacion', 'admin.resenas'],
            'textos' => ['titulo' => 'El mejor café del barrio', 'subtitulo' => 'Pan recién horneado, café de especialidad y tortas por encargo.', 'boton' => 'Hacer un pedido',
                'beneficios' => [['titulo' => 'Pan del día', 'texto' => 'Horneamos desde las 6 a. m.'], ['titulo' => 'Tortas por encargo', 'texto' => 'Cuéntanos la ocasión y la preparamos.'], ['titulo' => 'Café peruano', 'texto' => 'Granos de Chanchamayo y Cusco.']]],
            'paletas' => [['#78350F', '#F59E0B', 'Lora'], ['#9A3412', '#0F172A', 'Poppins'], ['#44403C', '#B45309', 'Playfair Display']],
        ],
        'tienda' => [
            'nombre' => 'Tienda de productos', 'icono' => '🛍️',
            'categorias' => ['Novedades', 'Ofertas', 'Más vendidos'],
            'servicios' => ['Envío a domicilio', 'Recojo en tienda'],
            'modulos' => ['admin.pos', 'admin.comprobantes', 'proveedores', 'repartos', 'tienda', 'fidelizacion', 'admin.campanas', 'publicidad'],
            'textos' => ['titulo' => 'Todo lo que buscas, en un solo lugar', 'subtitulo' => 'Compra online y recibe en tu casa o recoge en tienda.', 'boton' => 'Ir a la tienda',
                'beneficios' => [['titulo' => 'Envíos a todo Lima', 'texto' => 'Recibe tu pedido en 24 a 48 horas.'], ['titulo' => 'Pago seguro', 'texto' => 'Yape, Plin, tarjeta o contra entrega.'], ['titulo' => 'Cambios fáciles', 'texto' => 'Tienes 7 días para cambiar tu producto.']]],
            'paletas' => [['#2B6EF6', '#F47B20', 'Inter'], ['#7C3AED', '#EC4899', 'Poppins'], ['#0F172A', '#22C55E', 'Montserrat']],
        ],
        'peluqueria' => [
            'nombre' => 'Peluquería, barbería o spa', 'icono' => '💇',
            'categorias' => ['Cortes', 'Coloración', 'Tratamientos'],
            'servicios' => ['Corte de cabello', 'Barba', 'Tinte', 'Manicure'],
            'modulos' => ['servicios', 'admin.espera', 'admin.pos', 'fidelizacion', 'admin.galeria', 'admin.resenas', 'bot', 'admin.campanas'],
            'textos' => ['titulo' => 'Tu mejor versión empieza aquí', 'subtitulo' => 'Reserva tu cita en segundos y llega sin esperar.', 'boton' => 'Reservar cita',
                'beneficios' => [['titulo' => 'Estilistas expertos', 'texto' => 'Más de 10 años creando looks.'], ['titulo' => 'Sin esperas', 'texto' => 'Atendemos a la hora de tu reserva.'], ['titulo' => 'Productos premium', 'texto' => 'Cuidamos tu cabello y tu piel.']]],
            'paletas' => [['#BE185D', '#0F172A', 'Playfair Display'], ['#111827', '#D4A373', 'Montserrat'], ['#7C3AED', '#F472B6', 'Poppins']],
        ],
        'clinica' => [
            'nombre' => 'Clínica o consultorio', 'icono' => '🩺',
            'categorias' => ['Consultas', 'Exámenes', 'Tratamientos'],
            'servicios' => ['Consulta general', 'Control', 'Limpieza dental'],
            'modulos' => ['servicios', 'admin.espera', 'admin.comprobantes', 'cobros', 'admin.resenas', 'bot', 'sucursales'],
            'textos' => ['titulo' => 'Cuidamos tu salud con cercanía', 'subtitulo' => 'Especialistas, horarios flexibles y citas confirmadas por WhatsApp.', 'boton' => 'Agendar cita',
                'beneficios' => [['titulo' => 'Especialistas', 'texto' => 'Profesionales colegiados y con experiencia.'], ['titulo' => 'Citas puntuales', 'texto' => 'Te recordamos tu cita un día antes.'], ['titulo' => 'Equipos modernos', 'texto' => 'Diagnósticos rápidos y seguros.']]],
            'paletas' => [['#0E7490', '#22C55E', 'Inter'], ['#1D4ED8', '#06B6D4', 'Nunito'], ['#0F766E', '#334155', 'Lora']],
        ],
        'gimnasio' => [
            'nombre' => 'Gimnasio o academia', 'icono' => '🏋️',
            'categorias' => ['Membresías', 'Clases', 'Suplementos'],
            'servicios' => ['Clase de prueba', 'Entrenamiento personal', 'Evaluación física'],
            'modulos' => ['servicios', 'admin.pos', 'cobros', 'fidelizacion', 'admin.galeria', 'admin.campanas', 'publicidad'],
            'textos' => ['titulo' => 'Entrena con nosotros y supera tus metas', 'subtitulo' => 'Clases grupales, entrenadores certificados y planes a tu medida.', 'boton' => 'Pide tu clase gratis',
                'beneficios' => [['titulo' => 'Horario amplio', 'texto' => 'De 5 a. m. a 11 p. m., todos los días.'], ['titulo' => 'Entrenadores certificados', 'texto' => 'Rutinas pensadas para ti.'], ['titulo' => 'Comunidad', 'texto' => 'Retos y eventos cada mes.']]],
            'paletas' => [['#DC2626', '#111827', 'Montserrat'], ['#16A34A', '#0F172A', 'Poppins'], ['#F59E0B', '#18181B', 'Inter']],
        ],
        'servicios' => [
            'nombre' => 'Servicios profesionales', 'icono' => '💼',
            'categorias' => ['Paquetes', 'Servicios individuales'],
            'servicios' => ['Consulta inicial', 'Sesión regular'],
            'modulos' => ['servicios', 'cobros', 'admin.comprobantes', 'admin.galeria', 'admin.resenas', 'bot'],
            'textos' => ['titulo' => 'Soluciones a tu medida', 'subtitulo' => 'Cuéntanos qué necesitas y te enviamos una propuesta hoy mismo.', 'boton' => 'Pedir cotización',
                'beneficios' => [['titulo' => 'Respuesta en el día', 'texto' => 'Te escribimos en menos de 24 horas.'], ['titulo' => 'Experiencia', 'texto' => 'Más de 200 clientes satisfechos.'], ['titulo' => 'Garantía', 'texto' => 'Si no quedas conforme, lo corregimos.']]],
            'paletas' => [['#1E40AF', '#F59E0B', 'Inter'], ['#0F172A', '#2B6EF6', 'Montserrat'], ['#365314', '#A16207', 'Lora']],
        ],
        'otro' => [
            'nombre' => 'Otro negocio', 'icono' => '✨',
            'categorias' => ['General'],
            'servicios' => [],
            'modulos' => ['admin.pos', 'tienda', 'fidelizacion', 'admin.resenas'],
            'textos' => ['titulo' => 'Bienvenido a nuestro negocio', 'subtitulo' => 'Conoce lo que hacemos y escríbenos cuando quieras.', 'boton' => 'Escríbenos',
                'beneficios' => [['titulo' => 'Atención rápida', 'texto' => 'Respondemos por WhatsApp en el día.'], ['titulo' => 'Calidad', 'texto' => 'Trabajamos con productos y servicios de confianza.'], ['titulo' => 'Cercanía', 'texto' => 'Estamos para ayudarte en cada paso.']]],
            'paletas' => [['#2B6EF6', '#F47B20', 'Inter'], ['#0F766E', '#F59E0B', 'Poppins'], ['#334155', '#2B6EF6', 'Lora']],
        ],
    ];

    public static function claves(): array
    {
        return array_keys(self::CATALOGO);
    }

    public static function de(?string $rubro): ?array
    {
        return $rubro !== null ? (self::CATALOGO[$rubro] ?? null) : null;
    }

    /** Las 3 plantillas (clásica, moderna y minimal) del rubro, con colores y fuente propios. */
    public static function plantillasDe(?string $rubro): array
    {
        $r = self::CATALOGO[$rubro ?? 'otro'] ?? self::CATALOGO['otro'];
        $lista = [];
        foreach (array_keys(self::DISENOS) as $i => $diseno) {
            [$c1, $c2, $fuente] = $r['paletas'][$i];
            $lista[$diseno] = [
                'clave' => $diseno,
                'nombre' => self::DISENOS[$diseno]['nombre'],
                'descripcion' => self::DISENOS[$diseno]['descripcion'],
                'color_primario' => $c1,
                'color_secundario' => $c2,
                'fuente' => $fuente,
            ];
        }

        return $lista;
    }

    /** Módulos que se muestran por defecto para el rubro. */
    public static function modulosDe(?string $rubro): array
    {
        return (self::CATALOGO[$rubro ?? 'otro'] ?? self::CATALOGO['otro'])['modulos'];
    }

    /** Módulos opcionales que se ocultan según los que el negocio eligió. */
    public static function ocultosSegun(array $visibles): array
    {
        return array_values(array_diff(array_keys(self::MODULOS_OPCIONALES), $visibles));
    }
}
