<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GEST-88/98: contenido editable guardado en las tablas cms_*.
 * Ámbito "saas" = landing de GestionaHub (tenant_id nulo); "tienda" = web de cada negocio.
 */
class Cms
{
    public const TABLAS = [
        'faqs' => 'cms_faqs',
        'testimonios' => 'cms_testimonios',
        'logos' => 'cms_logos',
        'bloques' => 'cms_bloques',
        'secciones' => 'cms_secciones',
    ];

    /** Secciones de la landing SaaS que se pueden ocultar u ordenar. */
    public const SECCIONES_SAAS = [
        'funciones' => 'Funciones',
        'como' => 'Cómo funciona',
        'precios' => 'Precios',
        'logos' => 'Logos de clientes',
        'testimonios' => 'Testimonios',
        'blog' => 'Blog',
        'preguntas' => 'Preguntas frecuentes',
        'contacto' => 'Contacto',
    ];

    public static function disponible(): bool
    {
        static $ok = null;

        return $ok ??= Schema::hasTable('cms_faqs');
    }

    public static function consulta(string $tipo, string $ambito = 'saas', ?int $tenantId = null)
    {
        return DB::table(self::TABLAS[$tipo])->where('ambito', $ambito)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId), fn ($q) => $q->whereNull('tenant_id'))
            ->orderBy('orden')->orderBy('id');
    }

    /** Filas visibles como arreglos simples. */
    public static function lista(string $tipo, string $ambito = 'saas', ?int $tenantId = null, bool $soloVisibles = true): array
    {
        if (! self::disponible()) {
            return [];
        }

        return self::consulta($tipo, $ambito, $tenantId)->when($soloVisibles, fn ($q) => $q->where('visible', true))
            ->get()->map(fn ($r) => (array) $r)->all();
    }

    /** Orden y visibilidad de las secciones: [clave => ['visible' => bool, 'orden' => int, 'titulo' => ?]] */
    public static function secciones(string $ambito = 'saas', ?int $tenantId = null): array
    {
        $base = [];
        $i = 0;
        foreach (self::SECCIONES_SAAS as $clave => $nombre) {
            $base[$clave] = ['visible' => true, 'orden' => $i++, 'titulo' => null, 'subtitulo' => null, 'nombre' => $nombre];
        }
        foreach (self::lista('secciones', $ambito, $tenantId, false) as $s) {
            if (isset($base[$s['clave']])) {
                $base[$s['clave']] = array_merge($base[$s['clave']], [
                    'visible' => (bool) $s['visible'], 'orden' => (int) $s['orden'],
                    'titulo' => $s['titulo'], 'subtitulo' => $s['subtitulo'],
                ]);
            }
        }
        uasort($base, fn ($a, $b) => $a['orden'] <=> $b['orden']);

        return $base;
    }

    public static function guardarSecciones(array $secciones, string $ambito = 'saas', ?int $tenantId = null): void
    {
        $orden = 0;
        foreach ($secciones as $clave => $s) {
            if (! isset(self::SECCIONES_SAAS[$clave])) {
                continue;
            }
            DB::table('cms_secciones')->updateOrInsert(
                ['ambito' => $ambito, 'tenant_id' => $tenantId, 'clave' => $clave],
                ['orden' => $orden++, 'visible' => ! empty($s['visible']), 'titulo' => $s['titulo'] ?? null,
                    'subtitulo' => $s['subtitulo'] ?? null, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    /**
     * Reemplaza todas las filas de un tipo con la lista recibida (el editor manda la lista completa).
     * Solo toca el ámbito indicado.
     */
    public static function reemplazar(string $tipo, array $filas, string $ambito = 'saas', ?int $tenantId = null): void
    {
        $campos = [
            'faqs' => ['pregunta', 'respuesta'],
            'testimonios' => ['nombre', 'negocio', 'texto', 'foto_url', 'estrellas'],
            'logos' => ['nombre', 'imagen_url', 'enlace'],
        ][$tipo];

        DB::transaction(function () use ($tipo, $filas, $ambito, $tenantId, $campos) {
            self::consulta($tipo, $ambito, $tenantId)->delete();
            foreach (array_values($filas) as $i => $f) {
                $fila = array_intersect_key($f, array_flip($campos));
                if (trim((string) ($fila[$campos[0]] ?? '')) === '') {
                    continue;
                }
                if ($tipo === 'testimonios') {
                    $fila['estrellas'] = max(1, min(5, (int) ($fila['estrellas'] ?? 5)));
                }
                DB::table(self::TABLAS[$tipo])->insert($fila + [
                    'ambito' => $ambito, 'tenant_id' => $tenantId, 'orden' => $i,
                    'visible' => ! array_key_exists('visible', $f) || ! empty($f['visible']),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }
}
