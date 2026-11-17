<?php

namespace App\Support;

use App\Models\Recompensa;
use App\Models\Servicio;

/**
 * Configuracion de los CRUD sencillos que se muestran con el componente admin.crud
 * y se guardan con GestionModuloController (/admin/gestion/{modulo}).
 *
 * Cada campo: [nombre, etiqueta, tipo, reglas, extra]
 * tipos: texto | area | moneda | numero | color | si_no
 */
class CrudModulos
{
    public static function definiciones(): array
    {
        return [
            'servicios' => [
                'modelo' => Servicio::class,
                'titulo' => 'Servicio',
                'nuevo' => 'Nuevo servicio',
                'plural' => 'servicios',
                'icono' => 'wrench',
                'orden' => 'nombre',
                'campos' => [
                    ['nombre', 'Nombre del servicio', 'texto', ['required', 'string', 'max:120'], ['placeholder' => 'Corte de cabello, Limpieza facial…']],
                    ['descripcion', 'Descripción', 'area', ['nullable', 'string', 'max:500'], ['placeholder' => 'Qué incluye el servicio']],
                    ['precio', 'Precio (S/)', 'moneda', ['required', 'numeric', 'min:0', 'max:99999'], []],
                    ['duracion_min', 'Duración (minutos)', 'numero', ['required', 'integer', 'min:5', 'max:600'], ['defecto' => 30]],
                    ['color', 'Color en la agenda', 'color', ['nullable', 'string', 'max:20'], ['defecto' => '#2B6EF6']],
                    ['activo', 'Disponible para reservar', 'si_no', ['boolean'], ['defecto' => true]],
                ],
                'columnas' => [
                    ['nombre', 'Servicio', 'texto'],
                    ['precio', 'Precio', 'moneda'],
                    ['duracion_min', 'Duración', 'minutos'],
                    ['activo', 'Estado', 'si_no'],
                ],
            ],
            'fidelizacion' => [
                'modelo' => Recompensa::class,
                'titulo' => 'Recompensa',
                'nuevo' => 'Nueva recompensa',
                'plural' => 'recompensas',
                'icono' => 'heart',
                'orden' => 'puntos_requeridos',
                'campos' => [
                    ['nombre', 'Nombre de la recompensa', 'texto', ['required', 'string', 'max:120'], ['placeholder' => 'Bebida gratis, 10% de descuento…']],
                    ['puntos_requeridos', 'Puntos necesarios', 'numero', ['required', 'integer', 'min:1', 'max:1000000'], ['defecto' => 100]],
                    ['descuento_pct', 'Descuento (%)', 'numero', ['nullable', 'numeric', 'min:0', 'max:100'], ['placeholder' => 'Opcional']],
                    ['descripcion', 'Descripción', 'area', ['nullable', 'string', 'max:500'], []],
                    ['activo', 'Activa (se puede canjear)', 'si_no', ['boolean'], ['defecto' => true]],
                ],
                'columnas' => [
                    ['nombre', 'Recompensa', 'texto'],
                    ['puntos_requeridos', 'Puntos', 'numero'],
                    ['descuento_pct', 'Descuento', 'porcentaje'],
                    ['activo', 'Estado', 'si_no'],
                ],
            ],
        ];
    }

    public static function existe(string $modulo): bool
    {
        return array_key_exists($modulo, self::definiciones());
    }

    public static function definicion(string $modulo): array
    {
        return self::definiciones()[$modulo];
    }

    /** Datos que la vista necesita: configuracion publica (sin el modelo) e items actuales. */
    public static function cliente(string $modulo): object
    {
        $def = self::definicion($modulo);
        $modelo = $def['modelo'];

        return (object) [
            'modulo' => $modulo,
            'titulo' => $def['titulo'],
            'nuevo' => $def['nuevo'],
            'plural' => $def['plural'],
            'icono' => $def['icono'],
            'campos' => array_map(fn ($c) => [
                'nombre' => $c[0], 'etiqueta' => $c[1], 'tipo' => $c[2],
                'requerido' => in_array('required', $c[3], true),
                'placeholder' => $c[4]['placeholder'] ?? '',
                'defecto' => $c[4]['defecto'] ?? ($c[2] === 'si_no' ? false : ''),
            ], $def['campos']),
            'columnas' => array_map(fn ($c) => ['nombre' => $c[0], 'etiqueta' => $c[1], 'tipo' => $c[2]], $def['columnas']),
            'items' => $modelo::query()->orderBy($def['orden'])->get()->values(),
            'urls' => [
                'guardar' => route('admin.gestion.guardar', $modulo),
                'base' => url('/admin/gestion/' . $modulo),
            ],
        ];
    }

    public static function reglas(string $modulo): array
    {
        $r = [];
        foreach (self::definicion($modulo)['campos'] as $c) {
            $r[$c[0]] = $c[3];
        }

        return $r;
    }
}
