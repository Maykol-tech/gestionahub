<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class SeoIaService {
    public function generar($titulo, $descripcion) {
        $apiKey = config('services.openai.key');
        if (empty($apiKey)) {
            return ['title' => $titulo . ' | GestionaHub', 'description' => substr($descripcion, 0, 150)];
        }
        $response = Http::withToken($apiKey)->timeout(10)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un experto en SEO. Devuelve un JSON con las claves "title" (max 60 chars) y "description" (max 160 chars).'],
                ['role' => 'user', 'content' => "Título: $titulo. Descripción: $descripcion"]
            ]
        ]);
        if ($response->failed()) return ['title' => $titulo, 'description' => substr($descripcion, 0, 150)];
        $data = json_decode($response->json('choices.0.message.content'), true);
        return $data ?? ['title' => $titulo, 'description' => substr($descripcion, 0, 150)];
    }
}