<?php

namespace App\Support;

use App\Models\WhatsappConexion;
use App\Models\WhatsappMensaje;
use App\Models\WhatsappSesion;
use Illuminate\Support\Facades\Http;

/** Responde con el bot desde el numero propio del negocio, usando sus flujos. */
class WhatsAppNegocio
{
    /** Procesa un mensaje entrante: lo guarda y, si un flujo coincide, responde. */
    public static function procesarEntrante(WhatsappConexion $conexion, string $telefono, string $texto): void
    {
        WhatsappMensaje::withoutGlobalScopes()->create([
            'tenant_id' => $conexion->tenant_id,
            'direccion' => 'entrante',
            'telefono' => $telefono,
            'tipo' => 'entrante',
            'texto' => mb_substr($texto, 0, 1000),
            'estado' => 'recibido',
        ]);

        $sesion = WhatsappSesion::registrar((int) $conexion->tenant_id, $telefono);

        // Vincula la conversacion con el cliente del CRM si su telefono ya existe (GEST-30)
        if (! $sesion->crm_cliente_id) {
            $digitos = preg_replace('/\D/', '', $telefono);
            $cliente = \App\Models\CrmCliente::withoutGlobalScopes()
                ->where('tenant_id', $conexion->tenant_id)
                ->whereRaw("REPLACE(REPLACE(telefono, ' ', ''), '+', '') LIKE ?", ['%' . substr($digitos, -9)])
                ->first();
            if ($cliente) {
                $sesion->update(['crm_cliente_id' => $cliente->id]);
            }
        }

        // Menu numerado tiene prioridad sobre los flujos por palabra clave
        $menu = \App\Support\BotMenus::responder((int) $conexion->tenant_id, $sesion, $texto);
        if ($menu !== null && $telefono !== '') {
            self::enviarTexto($conexion, $telefono, $menu, 'bot');

            return;
        }

        $respuesta = BotFlujos::respuesta((int) $conexion->tenant_id, $texto);

        if ($respuesta === null || $telefono === '') {
            return;
        }

        self::enviarTexto($conexion, $telefono, $respuesta, 'bot');
    }

    /** Envia un texto desde el numero del negocio y lo registra. Devuelve true si Meta lo acepto. */
    public static function enviarTexto(WhatsappConexion $conexion, string $telefono, string $texto, string $tipo = 'recordatorio'): bool
    {
        $version = config('services.whatsapp.version', 'v21.0');
        $ok = false;

        try {
            $resp = Http::withToken($conexion->tokenClaro())->timeout(10)
                ->post("https://graph.facebook.com/{$version}/{$conexion->phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $telefono,
                    'type' => 'text',
                    'text' => ['body' => $texto],
                ]);
            $ok = $resp->successful();
        } catch (\Throwable) {
            $ok = false;
        }

        WhatsappMensaje::withoutGlobalScopes()->create([
            'tenant_id' => $conexion->tenant_id,
            'direccion' => 'saliente',
            'telefono' => $telefono,
            'tipo' => $tipo,
            'texto' => mb_substr($texto, 0, 1000),
            'estado' => $ok ? 'enviado' : 'fallido',
        ]);

        return $ok;
    }

    /** Envia una plantilla aprobada por Meta. Obligatoria para mensajes fuera de la ventana de 24 h. */
    public static function enviarPlantilla(WhatsappConexion $conexion, string $telefono, string $plantilla, string $tipo = 'campana', string $idioma = 'es'): bool
    {
        $version = config('services.whatsapp.version', 'v21.0');
        $ok = false;

        try {
            $resp = Http::withToken($conexion->tokenClaro())->timeout(10)
                ->post("https://graph.facebook.com/{$version}/{$conexion->phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $telefono,
                    'type' => 'template',
                    'template' => ['name' => $plantilla, 'language' => ['code' => $idioma]],
                ]);
            $ok = $resp->successful();
        } catch (\Throwable) {
            $ok = false;
        }

        WhatsappMensaje::withoutGlobalScopes()->create([
            'tenant_id' => $conexion->tenant_id,
            'direccion' => 'saliente',
            'telefono' => $telefono,
            'tipo' => $tipo,
            'texto' => 'Plantilla: ' . $plantilla,
            'estado' => $ok ? 'enviado' : 'fallido',
        ]);

        return $ok;
    }
}
