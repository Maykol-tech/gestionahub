<?php

namespace App\Support;

use App\Models\Pedido;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pago con tarjeta en la tienda online usando Mercado Pago (Checkout Pro) con la cuenta de CADA negocio.
 * El negocio pega su Access Token en Tienda online → Contenido → Pagos. Con un token "TEST-..." todo funciona en modo prueba.
 * 1) se crea la preferencia y el cliente paga en Mercado Pago  2) vuelve a la página del pedido
 * 3) consultamos el pago a Mercado Pago (nunca confiamos en lo que dice la URL) y marcamos el pedido como pagado.
 */
class MercadoPagoTienda
{
    public const API = 'https://api.mercadopago.com';

    public static function token(int $tenantId): ?string
    {
        $cifrado = (string) (AjustesNegocio::leer($tenantId)['mp_access_token'] ?? '');
        if ($cifrado === '') {
            return null;
        }
        try {
            return Crypt::decryptString($cifrado);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function activo(int $tenantId): bool
    {
        return self::token($tenantId) !== null;
    }

    public static function publicKey(int $tenantId): ?string
    {
        return ((string) (AjustesNegocio::leer($tenantId)['mp_public_key'] ?? '')) ?: null;
    }

    /** Formulario de tarjeta dentro de la tienda: necesita Access Token y Public Key. */
    public static function formulario(int $tenantId): bool
    {
        return self::activo($tenantId) && self::publicKey($tenantId) !== null;
    }

    public const MOTIVOS = [
        'cc_rejected_insufficient_amount' => 'La tarjeta no tiene fondos suficientes. Prueba con otra tarjeta o con Yape.',
        'cc_rejected_bad_filled_security_code' => 'Revisa el código de seguridad (CVV) de tu tarjeta.',
        'cc_rejected_bad_filled_date' => 'Revisa la fecha de vencimiento de tu tarjeta.',
        'cc_rejected_bad_filled_card_number' => 'Revisa el número de tu tarjeta.',
        'cc_rejected_bad_filled_other' => 'Revisa los datos de tu tarjeta.',
        'cc_rejected_call_for_authorize' => 'Tu banco pide que autorices este pago. Llámalo y vuelve a intentar.',
        'cc_rejected_card_disabled' => 'Tu tarjeta no está activada para compras por internet. Actívala con tu banco o usa otra.',
        'cc_rejected_duplicated_payment' => 'Ya hiciste un pago igual hace un momento. Si necesitas pagar de nuevo, usa otra tarjeta.',
        'cc_rejected_high_risk' => 'El pago fue rechazado por seguridad. Prueba con otra tarjeta o con Yape.',
        'cc_rejected_max_attempts' => 'Llegaste al límite de intentos con esta tarjeta. Usa otra.',
        'cc_rejected_blacklist' => 'No pudimos procesar esta tarjeta. Prueba con otra.',
    ];

    /**
     * Cobra con los datos que devuelve el formulario de Mercado Pago (token de la tarjeta, nunca el número).
     * El monto lo pone el servidor, no el navegador. Devuelve ['estado' => approved|in_process|rejected|error, 'mensaje', 'id'].
     */
    public static function cobrarTarjeta(int $tenantId, float $monto, string $referencia, string $descripcion, array $form): array
    {
        $token = self::token($tenantId);
        if (! $token) {
            return ['estado' => 'error', 'mensaje' => 'El pago con tarjeta no está disponible ahora.', 'id' => null];
        }
        $cuerpo = array_filter([
            'transaction_amount' => round($monto, 2),
            'token' => (string) ($form['token'] ?? ''),
            'description' => mb_substr($descripcion, 0, 120),
            'installments' => max(1, (int) ($form['installments'] ?? 1)),
            'payment_method_id' => (string) ($form['payment_method_id'] ?? ''),
            'issuer_id' => ! empty($form['issuer_id']) ? (string) $form['issuer_id'] : null,
            'payer' => array_filter([
                'email' => (string) data_get($form, 'payer.email', ''),
                'identification' => data_get($form, 'payer.identification.number') ? [
                    'type' => (string) data_get($form, 'payer.identification.type', 'DNI'),
                    'number' => (string) data_get($form, 'payer.identification.number'),
                ] : null,
            ]),
            'external_reference' => $referencia,
            'notification_url' => url('/api/pagos/mercadopago/webhook') . '?negocio=' . $tenantId,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $r = Http::withToken($token)->timeout(25)->withHeaders(['X-Idempotency-Key' => hash('sha256', $referencia . '|' . ($form['token'] ?? ''))])
                ->post(self::API . '/v1/payments', $cuerpo);
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago no respondió al cobrar con tarjeta', ['negocio' => $tenantId, 'error' => $e->getMessage()]);

            return ['estado' => 'error', 'mensaje' => 'Mercado Pago no respondió. No se hizo ningún cobro; intenta de nuevo en un momento.', 'id' => null];
        }
        if (! $r->successful() || ! $r->json('id')) {
            Log::warning('Mercado Pago rechazó el cobro con tarjeta', ['negocio' => $tenantId, 'status' => $r->status(), 'body' => $r->json()]);

            return ['estado' => 'error', 'mensaje' => 'No se pudo procesar la tarjeta. Revisa los datos o prueba con otra.', 'id' => null];
        }

        $id = (string) $r->json('id');
        $estado = (string) $r->json('status');
        if ($estado === 'approved') {
            self::verificar($tenantId, $id); // se confirma consultando el pago: referencia y monto exactos
        }

        return ['estado' => $estado, 'id' => $id, 'mensaje' => match ($estado) {
            'approved' => '¡Pago aprobado!',
            'in_process', 'pending', 'authorized' => 'Tu pago está en revisión. Te avisaremos cuando Mercado Pago lo apruebe.',
            default => self::MOTIVOS[(string) $r->json('status_detail')] ?? 'El banco rechazó el pago. Prueba con otra tarjeta o con Yape.',
        }, 'tarjeta' => $r->json('card.last_four_digits'), 'marca' => $r->json('payment_method_id')];
    }

    public static function esPrueba(string $token): bool
    {
        return str_starts_with($token, 'TEST-');
    }

    /** Crea la preferencia de pago y devuelve el enlace a Mercado Pago (o null si falló). */
    public static function enlacePago(object $negocio, Pedido $pedido): ?string
    {
        return self::preferencia($negocio, $pedido->numero, 'Pedido ' . $pedido->numero . ' · ' . $negocio->nombre_negocio,
            (float) $pedido->total, $negocio->id . ':' . $pedido->id, route('tienda.gracias', [$negocio->slug, $pedido->numero]));
    }

    /** GEST-57: pagar con tarjeta el cobro de una suscripción de cliente. */
    public static function enlaceCobro(object $negocio, \App\Models\SuscripcionCliente $s, \App\Models\CobroSuscripcion $c): ?string
    {
        return self::preferencia($negocio, 'S' . $c->id, (optional($s->plan)->nombre ?? 'Suscripción') . ' · ' . $negocio->nombre_negocio,
            (float) $c->monto, $negocio->id . ':m:' . $c->id, route('membresia.publica', $s->token));
    }

    private static function preferencia(object $negocio, string $id, string $titulo, float $monto, string $referencia, string $gracias): ?string
    {
        $token = self::token((int) $negocio->id);
        if (! $token) {
            return null;
        }
        $cuerpo = [
            'items' => [[
                'id' => $id,
                'title' => mb_substr($titulo, 0, 120),
                'quantity' => 1,
                'currency_id' => 'PEN',
                'unit_price' => round($monto, 2),
            ]],
            'external_reference' => $referencia,
            'back_urls' => ['success' => $gracias, 'pending' => $gracias, 'failure' => $gracias],
            'notification_url' => url('/api/pagos/mercadopago/webhook') . '?negocio=' . $negocio->id,
            'statement_descriptor' => mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', (string) $negocio->nombre_negocio) ?: 'TIENDA', 0, 13),
        ];
        if (! str_contains($gracias, '127.0.0.1') && ! str_contains($gracias, 'localhost')) {
            $cuerpo['auto_return'] = 'approved';
        }

        try {
            $r = Http::withToken($token)->timeout(15)->post(self::API . '/checkout/preferences', $cuerpo);
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago no respondió al crear la preferencia', ['negocio' => $negocio->id, 'error' => $e->getMessage()]);

            return null;
        }
        if (! $r->successful()) {
            Log::warning('Mercado Pago rechazó la preferencia', ['negocio' => $negocio->id, 'status' => $r->status(), 'body' => $r->json()]);

            return null;
        }

        return self::esPrueba($token) ? ($r->json('sandbox_init_point') ?: $r->json('init_point')) : $r->json('init_point');
    }

    /**
     * Consulta un pago a Mercado Pago y, si está aprobado y es de este pedido, lo marca como pagado.
     * Devuelve el estado del pago (approved, pending, rejected…) o null si no se pudo consultar.
     */
    public static function verificar(int $tenantId, string $pagoId): ?string
    {
        $token = self::token($tenantId);
        if (! $token || ! ctype_digit($pagoId)) {
            return null;
        }
        try {
            $r = Http::withToken($token)->timeout(15)->get(self::API . '/v1/payments/' . $pagoId);
        } catch (\Throwable) {
            return null;
        }
        if (! $r->successful()) {
            return null;
        }

        $partes = explode(':', (string) $r->json('external_reference'));
        $estado = (string) $r->json('status');
        // Cobro de una suscripción de cliente: "negocio:m:cobro"
        if (count($partes) === 3 && $partes[1] === 'm') {
            if ((int) $partes[0] !== $tenantId) {
                return null;
            }
            $cobro = \App\Models\CobroSuscripcion::withoutGlobalScopes()->where('tenant_id', $tenantId)->find((int) $partes[2]);
            if ($cobro && $estado === 'approved' && $cobro->estado !== 'pagado'
                && abs((float) $r->json('transaction_amount') - (float) $cobro->monto) < 0.01) {
                \App\Support\Membresias::pagar($cobro, 'mercadopago', 'MP ' . $pagoId);
            }

            return $estado;
        }
        [$negocioRef, $pedidoId] = array_pad($partes, 2, null);
        if ((int) $negocioRef !== $tenantId || ! $pedidoId) {
            return null;
        }
        $pedido = Pedido::withoutGlobalScopes()->where('tenant_id', $tenantId)->find((int) $pedidoId);
        if ($pedido && $estado === 'approved' && ! $pedido->pagado
            && abs((float) $r->json('transaction_amount') - (float) $pedido->total) < 0.01) {
            $pedido->forceFill(['pagado' => true, 'metodo_pago' => 'mercadopago', 'pago_ref' => 'MP ' . $pagoId])->save();
        }

        return $estado;
    }
}
