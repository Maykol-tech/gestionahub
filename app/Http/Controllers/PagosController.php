<?php

namespace App\Http\Controllers;

use App\Models\CheckoutAbandonado;
use App\Models\Pedido;
use App\Models\PagoManual;
use App\Models\Tenant;
use App\Models\WhatsappConexion;
use App\Support\WhatsAppNegocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bandeja de pagos manuales (Yape, Plin, transferencia) y recuperacion de checkouts abandonados.
 * GEST-95, GEST-96 y GEST-85.
 */
class PagosController extends DashboardController
{
    public function pagina()
    {
        $this->compartirLayout();

        return view('admin.modulos.pagos_manuales', [
            'titulo' => 'Pagos por revisar',
            'modulo' => 'pagos',
            'pagos' => PagoManual::query()->with('pedido:id,numero,total,cliente_nombre')->orderByRaw("case estado when 'pendiente' then 0 else 1 end")->orderByDesc('id')->limit(100)->get(),
            'abandonados' => CheckoutAbandonado::query()->where('recuperado', false)
                ->orderByDesc('id')->limit(50)->get(),
            'metodos' => PagoManual::METODOS,
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', 'in:' . implode(',', array_keys(PagoManual::METODOS))],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'referencia' => ['nullable', 'string', 'max:60'],
            'pedido_numero' => ['nullable', 'string', 'max:20'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        $pedidoId = null;
        if (! empty($datos['pedido_numero'])) {
            $pedidoId = Pedido::query()->where('numero', $datos['pedido_numero'])->value('id');
            if (! $pedidoId) {
                return back()->withErrors(['pedido_numero' => 'No encontramos un pedido con ese número.'])->withInput();
            }
        }

        PagoManual::create([
            'tenant_id' => auth()->user()->tenant_id,
            'pedido_id' => $pedidoId,
            'metodo' => $datos['metodo'],
            'monto' => $datos['monto'],
            'referencia' => $datos['referencia'] ?? null,
            'notas' => $datos['notas'] ?? null,
            'estado' => 'pendiente',
        ]);

        return back()->with('ok', 'Pago registrado. Confírmalo cuando veas el abono.');
    }

    /** Confirma el pago: si tiene pedido, lo marca como pagado con el metodo y la referencia. */
    public function confirmar(PagoManual $pago): RedirectResponse
    {
        if ($pago->estado !== 'pendiente') {
            return back()->withErrors(['estado' => 'Este pago ya fue revisado.']);
        }

        DB::transaction(function () use ($pago) {
            // Bloquea el pago para que dos clics no confirmen dos veces
            $bloqueado = PagoManual::query()->whereKey($pago->id)->lockForUpdate()->first();
            if (! $bloqueado || $bloqueado->estado !== 'pendiente') {
                throw ValidationException::withMessages(['estado' => 'Este pago ya fue revisado.']);
            }

            if ($pago->pedido_id) {
                $pedido = Pedido::query()->whereKey($pago->pedido_id)->lockForUpdate()->first();
                if ($pedido && ! $pedido->pagado) {
                    $pedido->update([
                        'pagado' => true,
                        'metodo_pago' => $pago->metodo,
                        'pago_ref' => $pago->referencia,
                    ]);
                }
            }

            $pago->update(['estado' => 'confirmado', 'confirmado_at' => now()]);
        });

        return back()->with('ok', 'Pago confirmado.');
    }

    /** Muestra la captura que subió el cliente (se guarda privada, solo la ve el negocio). */
    public function captura(PagoManual $pago)
    {
        abort_unless($pago->captura && \Illuminate\Support\Facades\Storage::disk('local')->exists($pago->captura), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($pago->captura, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function rechazar(PagoManual $pago): RedirectResponse
    {
        if ($pago->estado !== 'pendiente') {
            return back()->withErrors(['estado' => 'Este pago ya fue revisado.']);
        }

        $pago->update(['estado' => 'rechazado']);

        return back()->with('ok', 'Pago rechazado.');
    }

    /** Envia por WhatsApp el recordatorio del carrito que quedo sin pagar. */
    public function recordar(CheckoutAbandonado $carrito): RedirectResponse
    {
        $conexion = WhatsappConexion::query()->where('activo', true)->first();
        if (! $conexion) {
            return back()->withErrors(['conexion' => 'Conecta tu número de WhatsApp para enviar recordatorios.']);
        }

        if (! $carrito->telefono) {
            return back()->withErrors(['telefono' => 'Este carrito no tiene teléfono.']);
        }

        $texto = ($carrito->nombre ? "Hola {$carrito->nombre}, " : 'Hola, ')
            . 'dejaste tu pedido por S/ ' . number_format((float) $carrito->total, 2)
            . '. ¿Te ayudamos a completarlo? Responde a este mensaje.';

        $ok = WhatsAppNegocio::enviarTexto($conexion, preg_replace('/\D/', '', $carrito->telefono), $texto, 'carrito');

        $carrito->update(['recordado_at' => now()]);

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Recordatorio enviado.' : 'Meta no aceptó el recordatorio.');
    }

    /** Endpoint publico: el checkout de la tienda lo llama cuando el cliente deja datos y no paga. */
    public function capturarAbandono(Request $request, string $slug): Response
    {
        $negocio = Tenant::query()->where('slug', $slug)->whereIn('estado', ['activo', 'trial'])->firstOrFail();

        $datos = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'carrito' => ['nullable', 'array', 'max:50'],
            'total' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (empty($datos['telefono']) && empty($datos['email'])) {
            return response()->json(['ok' => false], 422);
        }

        // Un solo registro abierto por telefono o correo en las ultimas 24 horas
        $query = CheckoutAbandonado::withoutGlobalScopes()->where('tenant_id', $negocio->id)
            ->where('recuperado', false)->where('created_at', '>=', now()->subDay());

        $existente = (clone $query)->where(function ($q) use ($datos) {
            if (! empty($datos['telefono'])) {
                $q->orWhere('telefono', $datos['telefono']);
            }
            if (! empty($datos['email'])) {
                $q->orWhere('email', $datos['email']);
            }
        })->first();

        $payload = [
            'tenant_id' => $negocio->id,
            'nombre' => $datos['nombre'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'email' => $datos['email'] ?? null,
            'carrito' => $datos['carrito'] ?? [],
            'total' => $datos['total'] ?? 0,
        ];

        if ($existente) {
            $existente->update($payload);
        } else {
            $existente = CheckoutAbandonado::withoutGlobalScopes()->create($payload + ['token' => bin2hex(random_bytes(16))]);
        }
        if (! $existente->token) {
            $existente->forceFill(['token' => bin2hex(random_bytes(16))])->save();
        }

        return response()->json(['ok' => true, 'token' => $existente->token]);
    }

    /** GEST-85: devuelve el carrito guardado para que el cliente lo retome desde el enlace de WhatsApp. */
    public function recuperarCarrito(string $slug, string $token): Response
    {
        $negocio = Tenant::query()->where('slug', $slug)->whereIn('estado', ['activo', 'trial'])->firstOrFail();
        $c = CheckoutAbandonado::withoutGlobalScopes()->where('tenant_id', $negocio->id)->where('token', $token)->firstOrFail();
        if (! $c->abierto_at) {
            $c->forceFill(['abierto_at' => now()])->saveQuietly();
        }

        return response()->json([
            'recuperado' => (bool) $c->recuperado,
            'nombre' => $c->nombre, 'telefono' => $c->telefono, 'cupon' => $c->cupon,
            'carrito' => collect((array) $c->carrito)->map(fn ($l) => ['producto_id' => (int) ($l['producto_id'] ?? $l['id'] ?? 0), 'cantidad' => (int) ($l['cantidad'] ?? 1)])->filter(fn ($l) => $l['producto_id'] > 0)->values(),
        ]);
    }

    /** Marca como recuperado cuando el cliente completa un pedido (la tienda llama este endpoint). */
    public function marcarRecuperado(Request $request, string $slug): Response
    {
        $negocio = Tenant::query()->where('slug', $slug)->whereIn('estado', ['activo', 'trial'])->firstOrFail();

        $telefono = (string) $request->input('telefono', '');
        if ($telefono !== '') {
            CheckoutAbandonado::withoutGlobalScopes()->where('tenant_id', $negocio->id)
                ->where('telefono', $telefono)->where('recuperado', false)->update(['recuperado' => true]);
        }

        return response()->json(['ok' => true]);
    }
}
