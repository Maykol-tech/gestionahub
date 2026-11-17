<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GEST-94: cliente de Niubiz (VisaNet Perú).
 * 1) token de seguridad  2) sesión del formulario  3) autorización del pago
 * 4) tokenización de la tarjeta para la renovación automática  5) cobro con la tarjeta guardada.
 * Nunca guardamos el número de la tarjeta: solo el token que da Niubiz (cifrado en la base).
 */
class Niubiz
{
    public static function configurado(): bool
    {
        return (bool) (config('services.niubiz.merchant_id') && config('services.niubiz.usuario') && config('services.niubiz.clave'));
    }

    public static function base(): string
    {
        return config('services.niubiz.produccion') ? 'https://apiprod.vnforapps.com' : 'https://apisandbox.vnforappstest.com';
    }

    public static function scriptCheckout(): string
    {
        return config('services.niubiz.produccion')
            ? 'https://static-content.vnforapps.com/v2/js/checkout.js'
            : 'https://static-content-qas.vnforapps.com/v2/js/checkout.js?qa=true';
    }

    public static function comercio(): string
    {
        return (string) config('services.niubiz.merchant_id');
    }

    /** Token de acceso (vale ~1 hora; lo guardamos 50 minutos). */
    public static function tokenSeguridad(): ?string
    {
        return Cache::remember('niubiz_token', 3000, function () {
            $r = Http::timeout(15)->withBasicAuth(config('services.niubiz.usuario'), config('services.niubiz.clave'))
                ->accept('text/plain')->get(self::base() . '/api.security/v1/security');

            return $r->successful() ? trim($r->body()) : null;
        }) ?: null;
    }

    private static function cliente()
    {
        $token = self::tokenSeguridad();
        if (! $token) {
            throw new \RuntimeException('Niubiz no respondió el token de seguridad.');
        }

        return Http::timeout(30)->acceptJson()->withHeaders(['Authorization' => $token]);
    }

    /** Sesión para abrir el formulario de pago en el navegador. */
    public static function sesion(float $monto, array $cliente): ?string
    {
        $r = self::cliente()->post(self::base() . '/api.ecommerce/v2/ecommerce/token/session/' . self::comercio(), [
            'channel' => 'web',
            'amount' => round($monto, 2),
            'antifraud' => [
                'clientIp' => request()->ip(),
                'merchantDefineData' => [
                    'MDD4' => $cliente['email'] ?? '',
                    'MDD21' => 0,
                    'MDD32' => (string) ($cliente['id'] ?? ''),
                    'MDD75' => 'Registrado',
                    'MDD77' => (int) ($cliente['dias'] ?? 0),
                ],
            ],
        ]);

        return $r->successful() ? ($r->json('sessionKey') ?: null) : null;
    }

    /** Autoriza el pago con el token que devuelve el formulario. Devuelve [ok, datos]. */
    public static function autorizar(string $transactionToken, float $monto, string $numeroCompra): array
    {
        return self::procesar('/api.authorization/v3/authorization/ecommerce/' . self::comercio(), [
            'channel' => 'web',
            'captureType' => 'manual',
            'countable' => true,
            'order' => [
                'tokenId' => $transactionToken,
                'purchaseNumber' => $numeroCompra,
                'amount' => round($monto, 2),
                'currency' => 'PEN',
            ],
        ]);
    }

    /** Convierte el token de la transacción en un token de tarjeta reutilizable para renovar. */
    public static function tokenizar(string $transactionToken): ?array
    {
        try {
            $r = self::cliente()->get(self::base() . '/api.ecommerce/v2/ecommerce/token/card/' . self::comercio() . '/' . $transactionToken);
        } catch (\Throwable $e) {
            Log::warning('Niubiz tokenizar', ['error' => $e->getMessage()]);

            return null;
        }
        if (! $r->successful() || ! $r->json('token.tokenId')) {
            return null;
        }

        return [
            'token' => $r->json('token.tokenId'),
            'marca' => $r->json('card.brand') ?? $r->json('token.brand'),
            'ultimos' => substr(preg_replace('/\D/', '', (string) ($r->json('card.cardNumber') ?? $r->json('token.cardNumber') ?? '')), -4) ?: null,
        ];
    }

    /** Cobro recurrente con la tarjeta guardada (sin que el cliente esté presente). */
    public static function cobrarTarjeta(string $tokenTarjeta, float $monto, string $numeroCompra): array
    {
        return self::procesar('/api.authorization/v3/authorization/ecommerce/' . self::comercio(), [
            'channel' => 'recurrent',
            'captureType' => 'manual',
            'countable' => true,
            'order' => [
                'purchaseNumber' => $numeroCompra,
                'amount' => round($monto, 2),
                'currency' => 'PEN',
            ],
            'card' => ['tokenId' => $tokenTarjeta],
            'cardHolder' => ['documentType' => '0'],
        ]);
    }

    private static function procesar(string $ruta, array $cuerpo): array
    {
        try {
            $r = self::cliente()->post(self::base() . $ruta, $cuerpo);
        } catch (\Throwable $e) {
            return [false, ['mensaje' => 'No pudimos conectar con Niubiz. Intenta de nuevo en unos minutos.']];
        }

        $codigo = $r->json('dataMap.ACTION_CODE') ?? $r->json('data.ACTION_CODE');
        $ok = $r->successful() && $codigo === '000';

        return [$ok, [
            'id' => $r->json('dataMap.TRANSACTION_ID') ?? $r->json('order.transactionId'),
            'codigo' => $codigo,
            'tarjeta' => $r->json('dataMap.CARD') ?? $r->json('data.CARD'),
            'marca' => $r->json('dataMap.BRAND') ?? $r->json('data.BRAND'),
            'mensaje' => $ok ? 'Pago aprobado' : ($r->json('data.ACTION_DESCRIPTION') ?? $r->json('dataMap.ACTION_DESCRIPTION') ?? 'El banco rechazó el pago.'),
        ]];
    }
}
