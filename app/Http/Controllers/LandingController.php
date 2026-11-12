<?php
namespace App\Http\Controllers;

use App\Models\LandingLead;
use App\Models\LandingConfig;
use App\Models\Reclamacion;
use App\Services\SeoIaService;
use Illuminate\Http\Request;

class LandingController extends Controller {
    public function index(SeoIaService $seo) {
        $config = LandingConfig::first() ?? LandingConfig::defaults();
        $meta = $seo->generar($config->hero_title, $config->hero_subtitle);
        $planes = \App\Models\PlanSaaS::where('activo', 1)->orderBy('precio_mensual')->get();
        return view('landing.index', compact('config', 'meta', 'planes'));
    }

    public function contacto(Request $request) {
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'telefono' => 'nullable|string|max:20',
            'rubro' => 'nullable|string|max:100',
            'tamano' => 'nullable|string|max:50',
            'mensaje' => 'nullable|string',
            'origen' => 'nullable|string|max:50',
            'website' => 'nullable|string', // honeypot
        ]);

        // Honeypot: si tiene valor, es un bot
        if (!empty($data['website'])) {
            return response()->json(['ok' => true, 'message' => 'Gracias']);
        }

        // Capturar UTM de cookie
        $data['utm_source'] = $request->cookie('utm_source');
        $data['utm_campaign'] = $request->cookie('utm_campaign');
        $data['origen'] = $data['origen'] ?? 'formulario';
        unset($data['website']);

        LandingLead::create($data);

        return response()->json(['ok' => true, 'message' => 'Lead registrado. Te contactaremos pronto.']);
    }

    public function reclamacion(Request $request) {
        $data = $request->validate([
            'nombre' => 'required', 'tipo_documento' => 'required', 'numero_documento' => 'required',
            'email' => 'required|email', 'telefono' => 'required', 'direccion' => 'required',
            'tipo_bien' => 'required', 'detalle' => 'required', 'pedido' => 'required'
        ]);
        $data['numero_caso'] = 'REC-' . strtoupper(uniqid());
        $reclamacion = Reclamacion::create($data);
        return back()->with('success', 'Reclamo registrado. Su caso es: ' . $reclamacion->numero_caso);
    }
}