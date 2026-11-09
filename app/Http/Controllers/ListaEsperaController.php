<?php

namespace App\Http\Controllers;

use App\Models\ListaEsperaCita;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Lista de espera de citas y configuracion del boletin automatico para clientes inactivos (GEST-78 y 86). */
class ListaEsperaController extends DashboardController
{
    public function pagina(Request $request)
    {
        $this->compartirLayout();

        $tenantId = (int) auth()->user()->tenant_id;
        $negocio = DB::table('tenants')->where('id', $tenantId)->first(['boletin_activo', 'boletin_texto']);

        return view('admin.modulos.lista_espera', [
            'titulo' => 'Lista de espera y boletín',
            'modulo' => 'espera',
            'espera' => ListaEsperaCita::query()->with('servicio:id,nombre')
                ->orderBy('fecha_deseada')->orderBy('created_at')->limit(200)->get(),
            'servicios' => Servicio::query()->orderBy('nombre')->get(['id', 'nombre']),
            'boletinActivo' => (bool) ($negocio->boletin_activo ?? false),
            'boletinTexto' => $negocio->boletin_texto ?? '',
            'estados' => ListaEsperaCita::ESTADOS,
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:120'],
            'cliente_telefono' => ['required', 'string', 'max:30'],
            'fecha_deseada' => ['required', 'date', 'after_or_equal:today'],
            'servicio_id' => ['nullable', 'integer'],
        ]);

        ListaEsperaCita::create($datos + ['tenant_id' => auth()->user()->tenant_id]);

        return back()->with('ok', 'Cliente agregado a la lista de espera.');
    }

    public function estado(Request $request, ListaEsperaCita $espera): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', 'in:' . implode(',', array_keys(ListaEsperaCita::ESTADOS))]]);

        $espera->update(['estado' => $datos['estado']]);

        return back()->with('ok', 'Estado actualizado.');
    }

    public function boletin(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'boletin_activo' => ['sometimes', 'boolean'],
            'boletin_texto' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::table('tenants')->where('id', auth()->user()->tenant_id)->update([
            'boletin_activo' => (bool) ($datos['boletin_activo'] ?? false),
            'boletin_texto' => $datos['boletin_texto'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Boletín guardado. Se enviará el primer día de cada mes a clientes inactivos.');
    }
}
