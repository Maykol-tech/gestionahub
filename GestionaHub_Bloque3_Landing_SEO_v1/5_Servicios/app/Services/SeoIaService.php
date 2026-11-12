<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Genera el titulo y la descripcion SEO de la landing con OpenAI.
 * Sin OPENAI_API_KEY, o si OpenAI falla, usa el texto del hero (nunca deja la landing sin SEO).
 */
class SeoIaService
{
    /** @return array{titulo: string, descripcion: string, origen: string} */
    public function generar(array $hero): array
    {
        $base = [
            'titulo' => Str::limit(trim((string) ($hero['titulo'] ?? 'GestionaHub')), 60, ''),
            'descripcion' => Str::limit(trim((string) ($hero['subtitulo'] ?? '')), 155, ''),
        ];

        $clave = (string) config('services.openai.key');
        if ($clave === '') {
            return $base + ['origen' => 'texto-del-hero'];
        }

        try {
            $respuesta = Http::withToken($clave)->acceptJson()->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres experto en SEO para negocios peruanos. Responde solo JSON con las claves "titulo" (maximo 60 caracteres) y "descripcion" (maximo 155 caracteres), en espanol.'],
                        ['role' => 'user', 'content' => "Producto: GestionaHub, software para gestionar pedidos, cocina, caja y WhatsApp.\nTitular actual: {$base['titulo']}\nSubtitulo actual: {$base['descripcion']}"],
                    ],
                ]);

            if (! $respuesta->successful()) {
                return $base + ['origen' => 'texto-del-hero'];
            }

            $json = json_decode((string) $respuesta->json('choices.0.message.content'), true);
            if (! is_array($json) || empty($json['titulo']) || empty($json['descripcion'])) {
                return $base + ['origen' => 'texto-del-hero'];
            }

            return [
                'titulo' => Str::limit(trim((string) $json['titulo']), 60, ''),
                'descripcion' => Str::limit(trim((string) $json['descripcion']), 155, ''),
                'origen' => 'openai',
            ];
        } catch (Throwable) {
            return $base + ['origen' => 'texto-del-hero'];
        }
    }
}
