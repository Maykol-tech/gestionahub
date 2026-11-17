<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Datos del negocio: nombre, contacto, RUC y descripcion. Se muestran en la tienda y en los comprobantes. */
class PerfilNegocioController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        $tenantId = (int) auth()->user()->tenant_id;

        return view('admin.modulos.perfil_negocio', [
            'titulo' => 'Perfil del negocio',
            'modulo' => 'perfil',
            'perfil' => DB::table('tenants')->where('id', $tenantId)->first(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_negocio' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ruc' => ['nullable', 'digits:11'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
        ]);

        // Usa el query builder para no depender de $fillable del modelo Tenant
        DB::table('tenants')->where('id', auth()->user()->tenant_id)->update([
            'nombre_negocio' => $datos['nombre_negocio'],
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'ruc' => $datos['ruc'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'sitio_web' => $datos['sitio_web'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Perfil del negocio actualizado.');
    }
}
