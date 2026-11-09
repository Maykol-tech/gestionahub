<?php

namespace App\Http\Controllers;

use App\Models\PagoManual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pago de la suscripcion SaaS del negocio (GEST-93).
 * Por ahora el pago se reporta por Yape, Plin o transferencia y queda pendiente
 * hasta que el superadmin lo confirme. Cuando haya pasarela (Niubiz o Mercado Pago)
 * se conecta aqui sin cambiar la pantalla.
 */
class SuscripcionController extends Controller
{
    public function pagar(Request $request, string $slug): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', 'in:' . implode(',', array_keys(PagoManual::METODOS))],
            'referencia' => ['nullable', 'string', 'max:60'],
        ]);

        $plan = DB::table('planes_saas')->where('slug', $slug)->where('activo', true)->first();
        abort_if(! $plan, 404);

        $tenantId = (int) auth()->user()->tenant_id;

        // Evita duplicar: si ya hay un pago pendiente de suscripcion, no crea otro.
        $pendiente = PagoManual::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->where('estado', 'pendiente')->where('notas', 'like', 'Suscripcion%')->exists();

        if ($pendiente) {
            return back()->with('ok', 'Ya tienes un pago de suscripción pendiente de confirmación.');
        }

        PagoManual::create([
            'tenant_id' => $tenantId,
            'pedido_id' => null,
            'metodo' => $datos['metodo'],
            'monto' => $plan->precio_mensual,
            'referencia' => $datos['referencia'] ?? null,
            'estado' => 'pendiente',
            // Formato fijo: Suscripcion|<plan_id>|<nombre>. El superadmin lo usa al confirmar.
            'notas' => 'Suscripcion|' . $plan->id . '|' . $plan->nombre,
        ]);

        return back()->with('ok', 'Pago reportado. Lo confirmaremos y tu plan quedará activo.');
    }
}
