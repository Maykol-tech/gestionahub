<?php
namespace App\Http\Controllers;

use App\Models\LandingConfig;
use Illuminate\Http\Request;

class PopupEditorController extends Controller {
    public function edit() {
        $config = LandingConfig::first() ?? LandingConfig::defaults();
        return view('superadmin.popup_editor', compact('config'));
    }

    public function update(Request $request) {
        $data = $request->validate([
            'popup_activo' => 'nullable|boolean',
            'popup_delay_seconds' => 'required|integer|min:5|max:600',
            'popup_exit_intent' => 'nullable|boolean',
            'popup_frecuencia_dias' => 'required|integer|min:1|max:365',
            'popup_cuenta_regresiva_min' => 'required|integer|min:1|max:60',
            'popup_badge' => 'required|string|max:100',
            'popup_titulo' => 'required|string|max:150',
            'popup_texto' => 'nullable|string',
            'popup_beneficios' => 'nullable|string',
            'popup_cta' => 'required|string|max:50',
            'popup_oferta_codigo' => 'required|string|max:20',
        ]);

        $data['popup_activo'] = $request->boolean('popup_activo');
        $data['popup_exit_intent'] = $request->boolean('popup_exit_intent');
        if (!empty($data['popup_beneficios'])) {
            $data['popup_beneficios'] = array_filter(array_map('trim', explode("\n", $data['popup_beneficios'])));
        } else {
            $data['popup_beneficios'] = [];
        }

        $config = LandingConfig::first() ?? LandingConfig::defaults();
        $config->fill($data)->save();

        return back()->with('success', 'Configuración del popup actualizada.');
    }
}