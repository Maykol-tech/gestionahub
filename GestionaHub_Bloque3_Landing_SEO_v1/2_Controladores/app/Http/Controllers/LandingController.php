<?php

namespace App\Http\Controllers;

use App\Models\LandingContenido;
use App\Models\LandingLead;
use App\Models\LibroReclamacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Landing publica de GestionaHub: planes, captura de leads y libro de reclamaciones. */
class LandingController extends Controller
{
    public function inicio()
    {
        $planes = DB::table('planes_saas')->where('activo', true)->orderBy('precio_mensual')
            ->get(['slug', 'nombre', 'precio_mensual', 'max_sucursales'])
            ->map(fn ($p) => [
                'slug' => $p->slug,
                'nombre' => $p->nombre,
                'mensual' => (float) $p->precio_mensual,
                // Precio anual = 10 meses (2 meses de descuento). Ajustar si el plan anual es otro.
                'anual' => round((float) $p->precio_mensual * 10, 2),
                'sucursales' => (int) $p->max_sucursales,
            ])->values();

        return view('publico.landing', [
            'contenido' => LandingContenido::todo(),
            'planes' => $planes,
        ]);
    }

    public function contacto(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'mensaje' => ['nullable', 'string', 'max:1000'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'fuente' => ['nullable', 'in:' . implode(',', LandingLead::FUENTES)],
            'oferta' => ['nullable', 'string', 'max:40'],
        ]);

        if (empty($datos['email']) && empty($datos['telefono'])) {
            return back()->withErrors(['email' => 'Déjanos un correo o un teléfono para contactarte.'])->withInput();
        }

        LandingLead::create([
            'nombre' => $datos['nombre'],
            'email' => $datos['email'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'mensaje' => $datos['mensaje'] ?? null,
            'fuente' => $datos['fuente'] ?? 'landing',
            'oferta' => $datos['oferta'] ?? null,
            'utm' => array_filter($request->only(['utm_source', 'utm_medium', 'utm_campaign'])) ?: null,
        ]);

        return redirect()->to(url('/landing') . '#contacto')->with('ok', '¡Gracias! Te contactaremos pronto.');
    }

    public function reclamacion(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:' . implode(',', array_keys(LibroReclamacion::TIPOS))],
            'nombre' => ['required', 'string', 'max:150'],
            'documento_tipo' => ['required', 'in:' . implode(',', LibroReclamacion::DOCUMENTOS)],
            'documento' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'pedido' => ['nullable', 'string', 'max:255'],
            'detalle' => ['required', 'string', 'max:2000'],
        ]);

        // Numero correlativo LR-000001, bloqueando la tabla para que dos envios no tomen el mismo numero
        $reclamo = DB::transaction(function () use ($datos, $request) {
            $ultimo = LibroReclamacion::query()->lockForUpdate()->max('id') ?? 0;

            return LibroReclamacion::create($datos + [
                'numero' => 'LR-' . str_pad((string) ($ultimo + 1), 6, '0', STR_PAD_LEFT),
                'ip' => $request->ip(),
            ]);
        });

        Log::info('Libro de reclamaciones: nuevo registro ' . $reclamo->numero);

        return redirect()->to(url('/landing') . '#reclamos')
            ->with('ok', "Tu hoja de reclamación fue registrada con el número {$reclamo->numero}. Te responderemos en un plazo máximo de 30 días.");
    }
}
