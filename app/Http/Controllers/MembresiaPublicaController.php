<?php

namespace App\Http\Controllers;

use App\Models\CobroSuscripcion;
use App\Models\CrmCliente;
use App\Models\PlanCliente;
use App\Models\SuscripcionCliente;
use App\Support\Membresias;
use App\Support\MercadoPagoTienda;
use App\Support\TiendaConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** GEST-57: el cliente ve los planes del negocio, se suscribe y paga cada periodo desde su enlace. */
class MembresiaPublicaController extends Controller
{
    private function negocio(string $slug): object
    {
        $n = TiendaConfig::negocioPorSlug($slug);
        abort_if(! $n, 404);

        return $n;
    }

    public function planes(string $slug)
    {
        $negocio = $this->negocio($slug);
        $config = TiendaConfig::de($negocio);
        $planes = PlanCliente::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('activo', true)->orderBy('precio')->get();

        return view('publico.membresias', [
            'negocio' => $negocio, 'config' => $config, 'color' => $config['diseno']['color_primario'],
            'planes' => $planes, 'periodos' => PlanCliente::PERIODOS,
        ]);
    }

    public function suscribirse(Request $request, string $slug): RedirectResponse
    {
        $negocio = $this->negocio($slug);
        $datos = $request->validate([
            'plan_id' => ['required', 'integer'],
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'regex:/^9\d{8}$/'],
            'email' => ['nullable', 'email', 'max:120'],
        ], [
            'nombre.required' => 'Escribe tu nombre.',
            'telefono.required' => 'Escribe tu celular.', 'telefono.regex' => 'Escribe tu celular de 9 dígitos (empieza con 9).',
        ]);
        $plan = PlanCliente::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('activo', true)->findOrFail($datos['plan_id']);

        $cliente = CrmCliente::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('telefono', $datos['telefono'])->first()
            ?? CrmCliente::withoutGlobalScopes()->create([
                'tenant_id' => $negocio->id, 'nombre' => $datos['nombre'], 'telefono' => $datos['telefono'],
                'email' => $datos['email'] ?? null, 'fuente' => 'landing', 'notas' => 'Se suscribió desde la página de planes', 'activo' => true,
            ]);
        $s = Membresias::suscribir($plan, $cliente);

        return redirect()->route('membresia.publica', $s->token);
    }

    public function ver(Request $request, string $token)
    {
        $s = SuscripcionCliente::withoutGlobalScopes()->with(['plan', 'cliente'])->where('token', $token)->firstOrFail();
        $negocio = \App\Models\Tenant::findOrFail($s->tenant_id);
        if ($request->filled('payment_id')) {
            MercadoPagoTienda::verificar((int) $negocio->id, (string) $request->query('payment_id'));
            $s->refresh();
        }
        $config = TiendaConfig::de($negocio);
        $cobros = $s->cobros()->latest('id')->limit(12)->get();
        $abierto = $cobros->first(fn ($c) => in_array($c->estado, ['pendiente', 'por_revisar', 'vencido'], true));

        return response()->view('publico.membresia', [
            'negocio' => $negocio, 'config' => $config, 'color' => $config['diseno']['color_primario'],
            's' => $s, 'cobros' => $cobros, 'abierto' => $abierto,
            'pagos' => $this->metodos($negocio),
            'tarjeta' => MercadoPagoTienda::activo((int) $negocio->id),
            'mpFormulario' => MercadoPagoTienda::formulario((int) $negocio->id) ? MercadoPagoTienda::publicKey((int) $negocio->id) : null,
            'periodos' => PlanCliente::PERIODOS,
        ])->header('X-Robots-Tag', 'noindex');
    }

    /** Yape y Plin se muestran si el negocio tiene el número, aunque no los use en la tienda. */
    private function metodos(object $negocio): array
    {
        $m = [];
        if (! empty($negocio->yape_numero)) {
            $m[] = ['clave' => 'yape', 'nombre' => 'Yape', 'numero' => $negocio->yape_numero, 'qr' => $negocio->yape_qr ?? null];
        }
        if (! empty($negocio->plin_numero)) {
            $m[] = ['clave' => 'plin', 'nombre' => 'Plin', 'numero' => $negocio->plin_numero, 'qr' => null];
        }

        return $m;
    }

    public function reportar(Request $request, string $token): RedirectResponse
    {
        $s = SuscripcionCliente::withoutGlobalScopes()->where('token', $token)->firstOrFail();
        $cobro = CobroSuscripcion::withoutGlobalScopes()->where('suscripcion_id', $s->id)->whereIn('estado', ['pendiente', 'vencido', 'por_revisar'])->latest('id')->firstOrFail();
        $datos = $request->validate([
            'captura' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'metodo' => ['required', 'in:yape,plin,transferencia'],
            'referencia' => ['nullable', 'string', 'max:60'],
        ], [
            'captura.required' => 'Elige la foto o captura de tu pago.', 'captura.image' => 'La captura debe ser una imagen.',
            'captura.max' => 'La imagen pesa más de 6 MB.', 'metodo.required' => 'Elige cómo pagaste.',
        ]);
        if ($cobro->captura) {
            Storage::disk('local')->delete($cobro->captura);
        }
        $cobro->update([
            'captura' => $request->file('captura')->store('capturas_membresia/' . $s->tenant_id, 'local'),
            'metodo' => $datos['metodo'], 'referencia' => $datos['referencia'] ?? null, 'estado' => 'por_revisar',
        ]);

        return redirect()->route('membresia.publica', $token)->with('ok', 'Recibimos tu captura. El negocio la revisará y tu suscripción se activará.');
    }

    /** Formulario de tarjeta dentro de la página de la membresía. */
    public function cobrarTarjeta(Request $request, string $token): \Illuminate\Http\JsonResponse
    {
        $s = SuscripcionCliente::withoutGlobalScopes()->with('plan')->where('token', $token)->firstOrFail();
        $cobro = CobroSuscripcion::withoutGlobalScopes()->where('suscripcion_id', $s->id)->whereIn('estado', ['pendiente', 'vencido'])->latest('id')->first();
        if (! $cobro) {
            return response()->json(['estado' => 'approved', 'mensaje' => 'No tienes pagos pendientes.']);
        }
        abort_unless(MercadoPagoTienda::formulario((int) $s->tenant_id) && $s->estado !== 'cancelada', 422, 'El pago con tarjeta no está disponible.');
        $form = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'payment_method_id' => ['required', 'string', 'max:40'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:36'],
            'issuer_id' => ['nullable'],
            'payer.email' => ['required', 'email', 'max:120'],
            'payer.identification.type' => ['nullable', 'string', 'max:10'],
            'payer.identification.number' => ['nullable', 'string', 'max:20'],
        ]);
        $negocio = \App\Models\Tenant::findOrFail($s->tenant_id);
        $r = MercadoPagoTienda::cobrarTarjeta((int) $s->tenant_id, (float) $cobro->monto, $s->tenant_id . ':m:' . $cobro->id,
            (optional($s->plan)->nombre ?? 'Suscripción') . ' · ' . $negocio->nombre_negocio, $form);

        return response()->json($r, $r['estado'] === 'error' ? 422 : 200);
    }

    public function pagarTarjeta(string $token): RedirectResponse
    {
        $s = SuscripcionCliente::withoutGlobalScopes()->with('plan')->where('token', $token)->firstOrFail();
        $cobro = CobroSuscripcion::withoutGlobalScopes()->where('suscripcion_id', $s->id)->whereIn('estado', ['pendiente', 'vencido'])->latest('id')->firstOrFail();
        $negocio = \App\Models\Tenant::findOrFail($s->tenant_id);
        $url = MercadoPagoTienda::enlaceCobro($negocio, $s, $cobro);

        return $url ? redirect()->away($url) : back()->withErrors(['tarjeta' => 'No se pudo abrir el pago con tarjeta. Intenta con Yape o Plin.']);
    }
}
