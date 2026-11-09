<?php

namespace App\Http\Controllers;

use App\Models\GaleriaItem;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Galeria de trabajos: administracion en el panel (GEST-80) y pagina publica (GEST-81). */
class GaleriaController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        return view('admin.modulos.galeria', [
            'titulo' => 'Galería de trabajos',
            'modulo' => 'galeria',
            'items' => GaleriaItem::query()->orderBy('orden')->orderByDesc('id')->get(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'imagen_url' => ['required', 'url', 'max:500', 'starts_with:https://'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        GaleriaItem::create($datos + ['tenant_id' => auth()->user()->tenant_id, 'orden' => $datos['orden'] ?? 0]);

        return back()->with('ok', 'Trabajo agregado a la galería.');
    }

    public function alternar(GaleriaItem $item): RedirectResponse
    {
        $item->update(['activo' => ! $item->activo]);

        return back()->with('ok', $item->activo ? 'Trabajo visible.' : 'Trabajo oculto.');
    }

    public function eliminar(GaleriaItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('ok', 'Trabajo eliminado.');
    }

    /** Pagina publica con los trabajos activos del negocio. */
    public function publica(string $slug): Response
    {
        $negocio = Tenant::query()->where('slug', $slug)->where('estado', 'activo')->firstOrFail();

        $items = GaleriaItem::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('activo', true)->orderBy('orden')->orderByDesc('id')->get();

        return response()->view('publico.galeria', ['negocio' => $negocio, 'items' => $items]);
    }
}
