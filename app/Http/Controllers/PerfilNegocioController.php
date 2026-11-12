<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PerfilNegocioController extends Controller {
    public function edit() {
        $tenant = auth()->user()->tenant;
        return view('admin.modulos.perfil_negocio', compact('tenant'));
    }

    public function update(Request $request) {
        $tenant = auth()->user()->tenant;
        $data = $request->validate([
            'razon_social' => 'nullable|string|max:200',
            'nombre_comercial' => 'nullable|string|max:200',
            'ruc' => 'nullable|digits:11',
            'direccion' => 'nullable|string|max:300',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'rubro' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($tenant->logo_url && Storage::disk('public')->exists(str_replace('/storage/', '', $tenant->logo_url))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $tenant->logo_url));
            }
            $path = $request->file('logo')->store('tenants/' . $tenant->id, 'public');
            $data['logo_url'] = Storage::disk('public')->url($path);
        }
        unset($data['logo']);

        $tenant->update($data);
        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}