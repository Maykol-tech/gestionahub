<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Models\Mesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Administracion de mesas: alta, QR y cobro de las comandas abiertas. */
class MesasController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        return view('admin.modulos.mesas', [
            'titulo' => 'Mesas y QR',
            'modulo' => 'mesas',
            'mesas' => Mesa::query()->withCount(['comandasAbiertas as abiertas'])->orderBy('nombre')->get(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:40'],
            'capacidad' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $tenantId = auth()->user()->tenant_id;

        $existe = Mesa::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('nombre', $datos['nombre'])->exists();
        if ($existe) {
            return back()->withErrors(['nombre' => 'Ya existe una mesa con ese nombre.']);
        }

        Mesa::create([
            'tenant_id' => $tenantId,
            'nombre' => $datos['nombre'],
            'capacidad' => $datos['capacidad'],
        ]);

        return back()->with('ok', 'Mesa creada. Descarga su QR desde la lista.');
    }

    /** QR de la mesa generado en el servidor (SVG). Se muestra en la lista o se descarga. */
    public function qr(Request $request, Mesa $mesa)
    {
        $svg = QrCode::format('svg')->size(220)->margin(1)->errorCorrection('M')->generate($mesa->urlPublica());

        $respuesta = response($svg, 200, ['Content-Type' => 'image/svg+xml']);

        if ($request->boolean('descargar')) {
            $nombre = 'qr-' . Str::slug($mesa->nombre) . '.svg';

            return $respuesta->header('Content-Disposition', 'attachment; filename="' . $nombre . '"');
        }

        return $respuesta;
    }

    /** Cobra todas las comandas entregadas de la mesa y la deja libre. */
    public function cobrar(Mesa $mesa): RedirectResponse
    {
        $abiertas = $mesa->comandasAbiertas()->get();

        if ($abiertas->isEmpty()) {
            return back()->withErrors(['mesa' => 'La mesa no tiene comandas abiertas.']);
        }

        $pendientes = $abiertas->where('estado', '!=', 'entregada');
        if ($pendientes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'mesa' => 'Aún hay comandas que no se han entregado.',
            ]);
        }

        $total = 0.0;
        foreach ($abiertas as $comanda) {
            $comanda->update(['estado' => 'cobrada']);
            $total += (float) $comanda->total;
        }

        $mesa->update(['estado' => 'libre']);

        return back()->with('ok', 'Mesa cobrada: S/ ' . number_format($total, 2) . '.');
    }
}
