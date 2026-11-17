<?php

namespace App\Support;

/**
 * GEST-83/84: diseño de las páginas públicas del negocio (landing, tienda y galería).
 * Se guarda en el tenant (color principal y logo) y en sus ajustes (plantilla, color
 * secundario, fuente, favicon y pie). Se aplica con variables CSS.
 */
class DisenoNegocio
{
    public static function de(object $tenant, array $cambios = []): array
    {
        $a = AjustesNegocio::leer((int) $tenant->id);

        $d = [
            'plantilla' => $a['plantilla'] ?: 'clasica',
            'color_primario' => $tenant->color_primario ?: '#2B6EF6',
            'color_secundario' => $a['color_secundario'] ?: '#F47B20',
            'fuente' => $a['fuente'] ?: 'Inter',
            'logo_url' => $tenant->logo_url ?? null,
            'favicon_url' => $a['favicon_url'] ?: null,
            'pie_texto' => $a['pie_texto'] ?: null,
        ];

        // Vista previa: valores sin guardar que llegan por la URL (validados)
        foreach ($cambios as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            if (in_array($k, ['color_primario', 'color_secundario'], true) && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $v)) {
                $d[$k] = $v;
            } elseif ($k === 'plantilla' && isset(PlantillasRubro::DISENOS[$v])) {
                $d[$k] = $v;
            } elseif ($k === 'fuente' && isset(PlantillasRubro::FUENTES[$v])) {
                $d[$k] = $v;
            } elseif ($k === 'pie_texto') {
                $d[$k] = mb_substr((string) $v, 0, 200);
            }
        }

        $d['plantilla'] = isset(PlantillasRubro::DISENOS[$d['plantilla']]) ? $d['plantilla'] : 'clasica';
        $d['fuente'] = isset(PlantillasRubro::FUENTES[$d['fuente']]) ? $d['fuente'] : 'Inter';
        $d['fuente_css'] = PlantillasRubro::FUENTES[$d['fuente']];
        $d['fuente_url'] = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $d['fuente']) . ':wght@400;600;700;800&display=swap';

        return $d;
    }
}
