<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConexion;
use App\Models\WhatsappMensaje;
use App\Models\WhatsappSesion;
use App\Support\WhatsAppNegocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Bandeja de conversaciones de WhatsApp: lista de clientes, hilo y respuesta manual. */
class WhatsappInboxController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $telefono = $request->query('telefono');

        return view('admin.modulos.whatsapp_inbox', [
            'titulo' => 'Conversaciones de WhatsApp',
            'modulo' => 'whatsapp',
            'sesiones' => WhatsappSesion::query()->orderByDesc('ultimo_mensaje_at')->limit(50)->get(),
            'telefono' => $telefono,
            'hilo' => $telefono
                ? WhatsappMensaje::query()->where('telefono', $telefono)->orderBy('id')->limit(200)->get()
                : collect(),
            'conectado' => WhatsappConexion::query()->where('activo', true)->exists(),
        ]);
    }

    public function responder(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'telefono' => ['required', 'string', 'max:30'],
            'texto' => ['required', 'string', 'max:1000'],
        ]);

        $conexion = WhatsappConexion::query()->where('activo', true)->first();
        if (! $conexion) {
            return back()->withErrors(['texto' => 'Conecta tu número de WhatsApp antes de responder.']);
        }

        $ok = WhatsAppNegocio::enviarTexto($conexion, $datos['telefono'], $datos['texto'], 'manual');

        return redirect()->route('admin.whatsapp.inbox', ['telefono' => $datos['telefono']])
            ->with($ok ? 'ok' : 'error', $ok ? 'Mensaje enviado.' : 'Meta no aceptó el mensaje.');
    }
}
