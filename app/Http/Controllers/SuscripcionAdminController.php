<?php

namespace App\Http\Controllers;

use App\Models\PagoManual;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Superadmin: confirma o rechaza los pagos de suscripcion reportados por los negocios (GEST-93). */
class SuscripcionAdminController extends Controller
{
    public function index() { return $this->pagina(); } public function pagina(): View
    {
        $pagos = PagoManual::withoutGlobalScopes()
            ->where('notas', 'like', 'Suscripcion|%')
            ->orderByRaw("FIELD(estado, 'pendiente', 'confirmado', 'rechazado')")
            ->orderByDesc('id')->limit(200)->get();

        $nombres = DB::table('tenants')->whereIn('id', $pagos->pluck('tenant_id'))->pluck('nombre_negocio', 'id');

        return view('superadmin.suscripciones', [
            'pagos' => $pagos->map(fn (PagoManual $p) => [
                'id' => $p->id,
                'negocio' => $nombres[$p->tenant_id] ?? '—',
                'plan' => explode(' · ', explode('|', (string) $p->notas)[2] ?? '—')[0],
                'metodo' => PagoManual::METODOS[$p->metodo] ?? $p->metodo,
                'monto' => (float) $p->monto,
                'referencia' => $p->referencia ?? '—',
                'estado' => $p->estado,
                'fecha' => $p->created_at?->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }

    public function confirmar(int $pago): RedirectResponse
    {
        $pago = PagoManual::withoutGlobalScopes()->whereKey($pago)->where('notas', 'like', 'Suscripcion|%')->firstOrFail();

        if ($pago->estado !== 'pendiente') {
            return back()->with('error', 'Este pago ya fue procesado.');
        }

        [, $planId] = array_pad(explode('|', (string) $pago->notas), 3, null);

        DB::transaction(function () use ($pago, $planId) {
            $negocio = DB::table('tenants')->where('id', $pago->tenant_id)->lockForUpdate()->first();

            // Si la suscripcion aun esta vigente, se suman 30 dias a esa fecha; si no, desde hoy.
            $base = $negocio->suscripcion_hasta && Carbon::parse($negocio->suscripcion_hasta)->isFuture()
                ? Carbon::parse($negocio->suscripcion_hasta)
                : now();

            DB::table('tenants')->where('id', $pago->tenant_id)->update([
                'plan_id' => $planId ? (int) $planId : $negocio->plan_id,
                'suscripcion_hasta' => $base->copy()->addDays(30)->toDateString(),
            ]);

            $pago->update([
                'estado' => 'confirmado',
                'confirmado_at' => now(),
                'notas' => $pago->notas . ' · confirmado por ' . (Auth::user()->name ?? 'superadmin'),
            ]);
        });

        return back()->with('ok', 'Pago confirmado. El plan quedó activo por 30 días.');
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
