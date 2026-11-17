<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * GEST-97: últimas entradas de un blog WordPress (API REST pública /wp-json/wp/v2/posts).
 * Se guardan en caché 1 hora; si el blog no responde se muestra lo último que funcionó o nada.
 */
class BlogWordpress
{
    public const CACHE_SEGUNDOS = 3600;

    public static function entradas(?string $url, int $cantidad = 3): array
    {
        $url = rtrim(trim((string) $url), '/');
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with($url, 'http')) {
            return [];
        }
        $cantidad = max(1, min(6, $cantidad));
        $clave = 'blog_wp:' . md5($url . '|' . $cantidad);

        $fresco = Cache::get($clave);
        if ($fresco !== null) {
            return $fresco;
        }

        $entradas = self::descargar($url, $cantidad);
        if ($entradas === null) {
            // Falló: usa la última copia buena por 10 minutos para no insistir en cada visita
            $respaldo = Cache::get($clave . ':ultimo', []);
            Cache::put($clave, $respaldo, 600);

            return $respaldo;
        }

        Cache::put($clave, $entradas, self::CACHE_SEGUNDOS);
        Cache::forever($clave . ':ultimo', $entradas);

        return $entradas;
    }

    public static function limpiar(?string $url, int $cantidad = 3): void
    {
        Cache::forget('blog_wp:' . md5(rtrim(trim((string) $url), '/') . '|' . max(1, min(6, $cantidad))));
    }

    private static function descargar(string $url, int $cantidad): ?array
    {
        try {
            $r = Http::timeout(5)->acceptJson()->get($url . '/wp-json/wp/v2/posts', ['per_page' => $cantidad, '_embed' => 1]);
            if (! $r->successful() || ! is_array($r->json())) {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return collect($r->json())->take($cantidad)->map(function ($p) {
            $imagen = $p['_embedded']['wp:featuredmedia'][0]['media_details']['sizes']['medium_large']['source_url']
                ?? $p['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;

            return [
                'titulo' => html_entity_decode(strip_tags($p['title']['rendered'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'resumen' => mb_strimwidth(trim(html_entity_decode(strip_tags($p['excerpt']['rendered'] ?? ''), ENT_QUOTES, 'UTF-8')), 0, 150, '…'),
                'enlace' => $p['link'] ?? '#',
                'imagen' => $imagen,
                'fecha' => isset($p['date']) ? \Carbon\Carbon::parse($p['date'])->locale('es')->translatedFormat('d M Y') : '',
            ];
        })->filter(fn ($p) => $p['titulo'] !== '')->values()->all();
    }
}
