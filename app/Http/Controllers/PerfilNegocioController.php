<?php

namespace App\Http\Controllers;

use App\Models\PerfilNegocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PerfilNegocioController extends Controller
{
    public function guardarLogo(Request $request)
    {
        $request->validate(['logo' => 'required|image|max:2048']);
        
        $tenantId = 2;
        $perfil = PerfilNegocio::firstOrCreate(['tenant_id' => $tenantId]);
        
        $ruta = $request->file('logo')->store("logos", 'public');
        $perfil->update(['logo_url' => Storage::disk('public')->url($ruta)]);

        return back()->with('success', 'Logo subido correctamente');
    }
}