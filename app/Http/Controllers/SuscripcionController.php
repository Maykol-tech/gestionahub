<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\Niubiz;
use App\Support\Prueba;
use App\Support\Suscripciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * GEST-93..95: checkout de la suscripción del negocio.
 * Tarjeta con Niubiz (activa al instante y puede renovarse sola) o Yape / Plin / transferencia con captura.
 */
class SuscripcionController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function ciclo(Request $request): string
    {
        return $request->input('ciclo', $request->query('ciclo')) === 'anual' ? 'anual' : 'mensual';
    }

    public function checkout(Request $request, string $slug): View
    {
        $plan = Suscripciones::plan($slug);
        abort_if(! $plan, 404);
        $ciclo = $this->ciclo($request);
        $tenant = Tenant::find($this->tenantId());
        $checkoutId = Suscripciones::iniciarCheckout($this->tenantId(), $plan, $ciclo);

        $pendiente = DB::table('pagos_saas')->where('tenant_id', $this->tenantId())->where('estado', 'pendiente')
            ->whereIn('metodo', array_keys(Suscripciones::METODOS_MANUALES))->orderByDesc('id')->first();
        $suscripcion = DB::table('suscripciones_saas')->where('tenant_id', $this->tenantId())->orderByDesc('id')->first();

        session()->flash('evento', 'checkout_inicio');
        session()->flash('evento_datos', ['plan' => $plan->slug, 'ciclo' => $ciclo, 'monto' => Suscripciones::precio($plan, $ciclo)]);

        return view('admin.modulos.checkout', [
            'plan' => $plan,
            'ciclo' => $ciclo,
            'precios' => ['mensual' => Suscripciones::precio($plan, 'mensual'), 'anual' => Suscripciones::precio($plan, 'anual')],
            'checkoutId' => $checkoutId,
            'cobros' => Suscripciones::cobros(),
            'metodos' => Suscripciones::METODOS_MANUALES,
            'tarjeta' => Niubiz::configurado(),
            'pendiente' => $pendiente,
            'suscripcion' => $suscripcion,
            'prueba' => Prueba::de($tenant),
        ]);
    }

    /** GEST-95: reporta un pago por Yape, Plin o transferencia con su captura. */
    public function pagar(Request $request, string $slug): RedirectResponse
    {
        $plan = Suscripciones::plan($slug);
        abort_if(! $plan, 404);
        $datos = $request->validate([
            'metodo' => ['required', 'in:' . implode(',', array_keys(Suscripciones::METODOS_MANUALES))],
            'numero_operacion' => ['required', 'string', 'max:40'],
            'captura' => ['required', 'image', 'max:5120'],
            'checkout_id' => ['nullable', 'integer'],
        ], [
            'captura.required' => 'Sube la captura de tu pago.',
            'captura.image' => 'La captura debe ser una imagen (foto o pantallazo).',
            'numero_operacion.required' => 'Escribe el número de operación.',
        ]);

        Suscripciones::reportarManual($this->tenantId(), $plan, $this->ciclo($request), $datos['metodo'],
            $datos['numero_operacion'], $request->file('captura'), $datos['checkout_id'] ?? null);

        return redirect()->route('admin.modulo', 'planes')
            ->with('ok', 'Recibimos tu pago. Lo revisamos y tu plan se activa apenas lo confirmemos (normalmente en menos de 1 hora hábil).')
            ->with('evento', 'pago_reportado')->with('evento_datos', ['plan' => $plan->slug, 'metodo' => $datos['metodo']]);
    }

    /** GEST-94: prepara el formulario de Niubiz (sesión firmada para este checkout). */
    public function niubizSesion(Request $request, string $slug): JsonResponse
    {
        $plan = Suscripciones::plan($slug);
        abort_if(! $plan || ! Niubiz::configurado(), 404);
        $ciclo = $this->ciclo($request);
        $monto = Suscripciones::precio($plan, $ciclo);
        $checkoutId = Suscripciones::iniciarCheckout($this->tenantId(), $plan, $ciclo);
        $renovar = $request->boolean('renovar', true);
        DB::table('checkouts_saas')->where('id', $checkoutId)->update(['metodo' => 'tarjeta', 'updated_at' => now()]);

        $usuario = auth()->user();
        try {
            $sesion = Niubiz::sesion($monto, ['email' => $usuario->email, 'id' => $usuario->id, 'dias' => (int) $usuario->created_at?->diffInDays(now())]);
        } catch (\Throwable $e) {
            $sesion = null;
        }
        if (! $sesion) {
            return response()->json(['message' => 'No pudimos abrir el pago con tarjeta. Intenta de nuevo o usa Yape, Plin o transferencia.'], 502);
        }

        $compra = (string) (100000 + $checkoutId) . now()->format('His');

        return response()->json([
            'sessiontoken' => $sesion,
            'merchantid' => Niubiz::comercio(),
            'purchasenumber' => $compra,
            'amount' => number_format($monto, 2, '.', ''),
            'script' => Niubiz::scriptCheckout(),
            'action' => URL::temporarySignedRoute('publico.niubiz.respuesta', now()->addMinutes(30), [
                'checkout' => $checkoutId, 'compra' => $compra, 'renovar' => $renovar ? 1 : 0,
            ]),
        ]);
    }

    /** Niubiz devuelve aquí el transactionToken (POST desde su formulario; URL firmada, sin sesión). */
    public function niubizRespuesta(Request $request, int $checkout): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        $c = DB::table('checkouts_saas')->where('id', $checkout)->first();
        abort_if(! $c, 404);
        $plan = DB::table('planes_saas')->where('id', $c->plan_id)->first();
        $token = (string) $request->input('transactionToken');

        // Esta ruta no usa sesión (Niubiz hace el POST desde otro sitio): el mensaje viaja en la URL
        $volver = fn (string $estado, string $mensaje = '') => redirect()->to(route('admin.modulo', 'planes') . '?' . http_build_query(array_filter(['niubiz' => $estado, 'msg' => $mensaje])));

        if ($c->estado === 'pagado') {
            return $volver('aprobado');
        }
        if ($token === '' || ! $plan) {
            return $volver('rechazado', 'El pago no se completó.');
        }

        $monto = Suscripciones::precio($plan, $c->ciclo);
        [$ok, $res] = Niubiz::autorizar($token, $monto, (string) $request->query('compra'));

        $pagoId = DB::table('pagos_saas')->insertGetId([
            'tenant_id' => $c->tenant_id, 'plan_id' => $plan->id, 'ciclo' => $c->ciclo, 'monto' => $monto,
            'pasarela' => 'niubiz', 'metodo' => 'tarjeta', 'pasarela_pago_id' => $res['id'] ?? null,
            'estado' => $ok ? 'pagado' : 'fallido', 'fecha_pago' => $ok ? now() : null,
            'motivo_rechazo' => $ok ? null : mb_substr((string) $res['mensaje'], 0, 250),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        if (! $ok) {
            return $volver('rechazado', mb_substr((string) $res['mensaje'], 0, 120));
        }

        $tarjeta = $request->query('renovar') ? Niubiz::tokenizar($token) : null;
        $susId = Suscripciones::activar((int) $c->tenant_id, (int) $plan->id, $c->ciclo, $monto, 'niubiz', $tarjeta);
        DB::table('pagos_saas')->where('id', $pagoId)->update(['suscripcion_id' => $susId]);
        DB::table('checkouts_saas')->where('id', $c->id)->update(['estado' => 'pagado', 'pago_id' => $pagoId, 'updated_at' => now()]);

        return $volver('aprobado');
    }

    /** Apaga o prende la renovación automática con la tarjeta guardada. */
    public function renovacion(Request $request): RedirectResponse
    {
        $activar = $request->boolean('activar');
        $sus = DB::table('suscripciones_saas')->where('tenant_id', $this->tenantId())->orderByDesc('id')->first();
        abort_if(! $sus, 404);
        if ($activar && ! $sus->tarjeta_token) {
            return back()->with('ok', 'Para renovar solo, paga una vez con tarjeta.');
        }
        DB::table('suscripciones_saas')->where('id', $sus->id)->update([
            'renovacion_auto' => $activar,
            'tarjeta_token' => $activar ? $sus->tarjeta_token : null, // al apagar se olvida la tarjeta
            'updated_at' => now(),
        ]);

        return back()->with('ok', $activar ? 'Renovación automática activada.' : 'Renovación automática apagada. Borramos los datos de tu tarjeta.');
    }
}
