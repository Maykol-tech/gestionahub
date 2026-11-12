<?php

namespace App\Http\Controllers;

use App\Models\CampanaWhatsapp;
use App\Support\Campanas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Campanas de WhatsApp: crear, programar, enviar ahora o cancelar. */
class CampanasController extends DashboardController
{
    public function index() { return $this->pagina(); } public function pagina()
    {
        $this->compartirLayout();

        $tenantId = (int) auth()->user()->tenant_id;

        return view('admin.modulos.campanas', [
            'titulo' => 'Campañas de WhatsApp',
            'modulo' => 'campanas',
            'campanas' => CampanaWhatsapp::query()->orderByDesc('id')->limit(50)->get(),
            'segmentos' => collect(CampanaWhatsapp::SEGMENTOS)->map(
                fn ($label, $key) => ['key' => $key, 'label' => $label, 'total' => Campanas::destinatarios($tenantId, $key)->count()]
            )->values(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'segmento' => ['required', 'in:' . implode(',', array_keys(CampanaWhatsapp::SEGMENTOS))],
            'plantilla' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'],
            'texto' => ['nullable', 'string', 'max:1000'],
            'programada_para' => ['nullable', 'date', 'after:now'],
        ]);

        if (empty($datos['plantilla']) && empty($datos['texto'])) {
            return back()->withErrors(['texto' => 'Escribe un texto o indica una plantilla aprobada.'])->withInput();
        }

        CampanaWhatsapp::create([
            'tenant_id' => auth()->user()->tenant_id,
            'nombre' => $datos['nombre'],
            'segmento' => $datos['segmento'],
            'plantilla' => $datos['plantilla'] ?? null,
            'texto' => $datos['texto'] ?? null,
            'estado' => ! empty($datos['programada_para']) ? 'programada' : 'borrador',
            'programada_para' => $datos['programada_para'] ?? null,
        ]);

        return back()->with('ok', ! empty($datos['programada_para'])
            ? 'Campaña programada. Se enviará a la hora indicada.'
            : 'Campaña guardada como borrador.');
    }

    public function enviarAhora(CampanaWhatsapp $campana): RedirectResponse
    {
        $resultado = Campanas::enviar($campana);

        return back()->with('ok', "Campaña enviada: {$resultado->enviados} entregados a Meta, {$resultado->fallidos} fallidos.");
    }

    public function cancelar(CampanaWhatsapp $campana): RedirectResponse
    {
        if (! in_array($campana->estado, ['borrador', 'programada'], true)) {
            return back()->withErrors(['estado' => 'Solo puedes cancelar campañas en borrador o programadas.']);
        }

        $campana->update(['estado' => 'cancelada']);

        return back()->with('ok', 'Campaña cancelada.');
    }
}
