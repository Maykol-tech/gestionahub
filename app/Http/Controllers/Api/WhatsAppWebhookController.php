<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConexion;
use App\Support\WhatsAppNegocio;
use Illuminate\Http\Request;

/** Webhook de Meta. Cada mensaje se enruta al negocio dueno del numero (phone_number_id) y el bot responde con sus flujos. */
class WhatsAppWebhookController extends Controller
{
    /** Meta llama aqui una vez al configurar el webhook. */
    public function verificar(Request $request)
    {
        $token = (string) config('services.whatsapp.verify_token');
        if ($token !== '' && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function recibir(Request $request)
    {
        if (! $this->firmaValida($request)) {
            return response('Forbidden', 403);
        }

        foreach ($request->input('entry', []) as $entrada) {
            foreach ($entrada['changes'] ?? [] as $cambio) {
                $value = $cambio['value'] ?? [];
                $phoneId = (string) ($value['metadata']['phone_number_id'] ?? '');

                $conexion = $phoneId !== ''
                    ? WhatsappConexion::withoutGlobalScopes()->where('phone_id', $phoneId)->where('activo', true)->first()
                    : null;

                if (! $conexion) {
                    continue; // Numero no conectado a ningun negocio.
                }

                foreach ($value['messages'] ?? [] as $mensaje) {
                    $texto = (string) ($mensaje['text']['body'] ?? '');

                    if ($texto === '') {
                        $texto = '[' . ($mensaje['type'] ?? 'mensaje') . ']';
                    }

                    WhatsAppNegocio::procesarEntrante($conexion, (string) ($mensaje['from'] ?? ''), $texto);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function firmaValida(Request $request): bool
    {
        $secreto = (string) config('services.whatsapp.app_secret');
        if ($secreto === '') {
            return false;
        }
        $esperada = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secreto);

        return hash_equals($esperada, (string) $request->header('X-Hub-Signature-256'));
    }
}
