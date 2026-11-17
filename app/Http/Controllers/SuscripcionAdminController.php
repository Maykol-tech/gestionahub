<?php

namespace App\Http\Controllers;

use App\Models\LandingContenido;
use App\Models\PagoManual;
use App\Support\Suscripciones;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * GEST-95: bandeja del superadmin para revisar los pagos de suscripción por Yape, Plin o transferencia
 * (captura + número de operación, que no se puede repetir). Aprobar activa el plan; rechazar avisa al negocio.
 * También muestra los cobros con tarjeta (Niubiz) y los pagos del sistema anterior (pagos_manuales).
 */
class SuscripcionAdminController extends Controller
{
    public function pagina(Request $request): View
    {
        $vista = in_array($request->query('ver'), ['pendientes', 'pagados', 'rechazados', 'todos'], true) ? $request->query('ver') : 'pendientes';
        $estado = ['pendientes' => 'pendiente', 'pagados' => 'pagado', 'rechazados' => 'fallido'][$vista] ?? null;

        $pagos = DB::table('pagos_saas')->whereNotNull('metodo')
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('id')->limit(200)->get();
        $negocios = DB::table('tenants')->whereIn('id', $pagos->pluck('tenant_id'))->get(['id', 'nombre_negocio', 'email'])->keyBy('id');
        $planes = DB::table('planes_saas')->pluck('nombre', 'id');

        $legado = PagoManual::withoutGlobalScopes()->where('notas', 'like', 'Suscripcion|%')->where('estado', 'pendiente')->orderByDesc('id')->get();

        return view('superadmin.suscripciones', [
            'vista' => $vista,
            'pagos' => $pagos->map(fn ($p) => [
                'id' => $p->id,
                'negocio' => $negocios[$p->tenant_id]->nombre_negocio ?? '—',
                'email' => $negocios[$p->tenant_id]->email ?? '',
                'plan' => ($planes[$p->plan_id] ?? '—') . ' · ' . ($p->ciclo === 'anual' ? 'anual' : 'mensual'),
                'metodo' => $p->metodo === 'tarjeta' ? 'Tarjeta' : (Suscripciones::METODOS_MANUALES[$p->metodo] ?? ucfirst((string) $p->metodo)),
                'monto' => (float) $p->monto,
                'operacion' => $p->numero_operacion ?: ($p->pasarela_pago_id ?: '—'),
                'captura' => $p->comprobante_path ? route('superadmin.suscripciones.captura', $p->id) : null,
                'estado' => $p->estado,
                'motivo' => $p->motivo_rechazo,
                'fecha' => Carbon::parse($p->created_at)->format('d/m/Y H:i'),
                'hace' => Carbon::parse($p->created_at)->locale('es')->diffForHumans(),
            ])->values(),
            'conteo' => [
                'pendientes' => DB::table('pagos_saas')->whereNotNull('metodo')->where('estado', 'pendiente')->count(),
                'mes' => (float) DB::table('pagos_saas')->where('estado', 'pagado')->where('fecha_pago', '>=', now()->startOfMonth())->sum('monto'),
                'activas' => DB::table('suscripciones_saas')->where('estado', 'activa')->count(),
                'abandonados' => DB::table('checkouts_saas')->where('estado', 'abandonado')->where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'legado' => $legado->map(fn (PagoManual $p) => [
                'id' => $p->id, 'negocio' => DB::table('tenants')->where('id', $p->tenant_id)->value('nombre_negocio') ?? '—',
                'plan' => explode('|', (string) $p->notas)[2] ?? '—', 'metodo' => PagoManual::METODOS[$p->metodo] ?? $p->metodo,
                'monto' => (float) $p->monto, 'referencia' => $p->referencia ?? '—', 'fecha' => $p->created_at?->format('d/m/Y H:i'),
            ])->values(),
            'cobros' => Suscripciones::cobros(),
        ]);
    }

    public function captura(int $pago): Response
    {
        $ruta = DB::table('pagos_saas')->where('id', $pago)->value('comprobante_path');
        abort_if(! $ruta || ! Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->response($ruta, null, ['Cache-Control' => 'private, max-age=600']);
    }

    public function aprobar(int $pago): RedirectResponse
    {
        Suscripciones::aprobar($pago, Auth::id());

        return back()->with('ok', 'Pago aprobado. El plan del negocio ya está activo y le avisamos por correo.');
    }

    public function rechazarPago(Request $request, int $pago): RedirectResponse
    {
        $motivo = $request->validate(['motivo' => ['required', 'string', 'max:200']], ['motivo.required' => 'Escribe por qué lo rechazas (el negocio lo verá).'])['motivo'];
        Suscripciones::rechazar($pago, $motivo, Auth::id());

        return back()->with('ok', 'Pago rechazado. Le avisamos al negocio con el motivo.');
    }

    /** Datos donde los negocios depositan (Yape, Plin, cuenta bancaria). */
    public function guardarCobros(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'yape' => ['nullable', 'regex:/^9\d{8}$/'], 'plin' => ['nullable', 'regex:/^9\d{8}$/'],
            'titular' => ['required', 'string', 'max:120'], 'banco' => ['nullable', 'string', 'max:60'],
            'cuenta' => ['nullable', 'string', 'max:30'], 'cci' => ['nullable', 'regex:/^\d{20}$/'], 'qr_url' => ['nullable', 'url', 'max:255'],
        ], ['yape.regex' => 'El Yape debe ser un celular de 9 dígitos.', 'plin.regex' => 'El Plin debe ser un celular de 9 dígitos.', 'cci.regex' => 'El CCI tiene 20 dígitos.']);
        LandingContenido::guardar('cobros', array_map(fn ($v) => (string) $v, $d));

        return back()->with('ok', 'Datos de cobro guardados.');
    }

    // ---- Sistema anterior (pagos_manuales con notas "Suscripcion|plan|nombre") ----

    public function confirmar(int $pago): RedirectResponse
    {
        $pago = PagoManual::withoutGlobalScopes()->whereKey($pago)->where('notas', 'like', 'Suscripcion|%')->firstOrFail();
        if ($pago->estado !== 'pendiente') {
            return back()->with('error', 'Este pago ya fue procesado.');
        }
        [, $planId] = array_pad(explode('|', (string) $pago->notas), 3, null);
        $planId = $planId ? (int) $planId : (int) DB::table('tenants')->where('id', $pago->tenant_id)->value('plan_id');

        DB::transaction(function () use ($pago, $planId) {
            Suscripciones::activar((int) $pago->tenant_id, $planId, 'mensual', (float) $pago->monto, 'manual');
            $pago->update(['estado' => 'confirmado', 'confirmado_at' => now(),
                'notas' => $pago->notas . ' · confirmado por ' . (Auth::user()->name ?? 'superadmin')]);
        });

        return back()->with('ok', 'Pago confirmado. El plan quedó activo por 1 mes.');
    }

    public function rechazar(Request $request, int $pago): RedirectResponse
    {
        $pago = PagoManual::withoutGlobalScopes()->whereKey($pago)->where('notas', 'like', 'Suscripcion|%')->firstOrFail();
        if ($pago->estado !== 'pendiente') {
            return back()->with('error', 'Este pago ya fue procesado.');
        }
        $pago->update(['estado' => 'rechazado']);

        return back()->with('ok', 'Pago rechazado.');
    }
}
