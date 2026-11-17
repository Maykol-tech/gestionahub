<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Reseñas de Google del negocio (Google Places API).
 *  - Con solo el ID del lugar ya funciona el botón "Déjanos tu reseña en Google".
 *  - Con la clave de Google además se traen la nota, el total y las últimas reseñas (se guardan 12 horas).
 */
class GoogleResenas
{
    public static function config(int $tenantId): array
    {
        $a = AjustesNegocio::leer($tenantId);
        $clave = '';
        if (! empty($a['google_api_key'])) {
            try {
                $clave = Crypt::decryptString((string) $a['google_api_key']);
            } catch (\Throwable) {
                $clave = '';
            }
        }

        return [
            'place_id' => trim((string) $a['google_place_id']),
            'clave' => $clave,
            'mostrar' => (bool) $a['google_mostrar'],
        ];
    }

    public static function enlaceEscribir(string $placeId): ?string
    {
        return $placeId !== '' ? 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId) : null;
    }

    public static function claveCache(int $tenantId): string
    {
        return "google_resenas_{$tenantId}";
    }

    /** Nota y reseñas de Google. Devuelve null si falta configurar; ['error' => ...] si Google respondió con error. */
    public static function datos(int $tenantId): ?array
    {
        $cfg = self::config($tenantId);
        if ($cfg['place_id'] === '' || $cfg['clave'] === '') {
            return null;
        }

        return Cache::remember(self::claveCache($tenantId), now()->addHours(12), function () use ($cfg) {
            try {
                $r = Http::timeout(8)->withHeaders([
                    'X-Goog-Api-Key' => $cfg['clave'],
                    'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,googleMapsUri,reviews',
                ])->get('https://places.googleapis.com/v1/places/' . rawurlencode($cfg['place_id']), ['languageCode' => 'es']);
            } catch (\Throwable) {
                return ['error' => 'No se pudo conectar con Google. Intenta más tarde.'];
            }
            if (! $r->successful()) {
                $msg = (string) data_get($r->json(), 'error.message', '');

                return ['error' => $r->status() === 403 || $r->status() === 400
                    ? 'Google rechazó la clave o el ID del lugar. Revisa que la clave tenga activada "Places API (New)".'
                    : 'Google respondió con un error' . ($msg ? ': ' . $msg : '.')];
            }
            $j = $r->json();

            return [
                'nombre' => data_get($j, 'displayName.text'),
                'nota' => round((float) ($j['rating'] ?? 0), 1),
                'total' => (int) ($j['userRatingCount'] ?? 0),
                'url' => $j['googleMapsUri'] ?? null,
                'resenas' => collect($j['reviews'] ?? [])->take(5)->map(fn ($x) => [
                    'autor' => data_get($x, 'authorAttribution.displayName', 'Cliente de Google'),
                    'foto' => data_get($x, 'authorAttribution.photoUri'),
                    'nota' => (int) ($x['rating'] ?? 0),
                    'texto' => (string) data_get($x, 'text.text', data_get($x, 'originalText.text', '')),
                    'cuando' => (string) ($x['relativePublishTimeDescription'] ?? ''),
                ])->values()->all(),
            ];
        });
    }
}
