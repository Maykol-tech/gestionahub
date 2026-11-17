<?php

namespace App\Support;

use App\Models\CheckoutAbandonado;
use App\Models\WhatsappConexion;
use Illuminate\Support\Facades\DB;

/**
 * GEST-85: si el cliente deja su teléfono en el checkout y no termina en X minutos,
 * se le envía un WhatsApp con el enlace para recuperar su carrito (y cupón opcional).
 * Un solo recordatorio por carrito.
 */
class CarritosAbandonados
{
    public static function config(int $tenantId): array
    {
        $a = AjustesNegocio::leer($tenantId);

        return [
            'activo' => (bool) $a['carrito_activo'],
            'minutos' => max(10, (int) $a['carrito_minutos']),
            'cupon' => (string) $a['carrito_cupon'],
            'mensaje' => (string) $a['carrito_mensaje'],
        ];
    }

    public static function enlace(object $negocio, CheckoutAbandonado $c): string
    {
        return url('/tienda/' . $negocio->slug) . '?recuperar=' . $c->token;
    }

    public static function mensaje(object $negocio, CheckoutAbandonado $c, array $cfg): string
    {
        $texto = str_replace(
            ['{nombre}', '{negocio}', '{total}', '{enlace}', '{cupon}'],
            [trim(explode(' ', (string) $c->nombre)[0]) ?: 'hola', $negocio->nombre_negocio, 'S/ ' . number_format((float) $c->total, 2), self::enlace($negocio, $c), $cfg['cupon']],
            $cfg['mensaje']
        );
        if (! str_contains($cfg['mensaje'], '{enlace}')) {
            $texto .= "\n" . self::enlace($negocio, $c);
        }
        if ($cfg['cupon'] !== '' && ! str_contains($cfg['mensaje'], '{cupon}')) {
            $texto .= "\nUsa el cupón {$cfg['cupon']} al terminar tu pedido.";
        }

        return $texto;
    }

    /** Envía los recordatorios pendientes de un negocio. Devuelve cuántos se procesaron. */
    public static function recordar(int $tenantId): int
    {
        $cfg = self::config($tenantId);
        if (! $cfg['activo']) {
            return 0;
        }
        $negocio = DB::table('tenants')->where('id', $tenantId)->first(['id', 'slug', 'nombre_negocio']);
        if (! $negocio?->slug) {
            return 0;
        }
        $conexion = WhatsappConexion::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('activo', true)->first();

        $pendientes = CheckoutAbandonado::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->where('recuperado', false)->whereNull('recordado_at')->whereNotNull('telefono')
            ->where('updated_at', '<=', now()->subMinutes($cfg['minutos']))
            ->where('updated_at', '>=', now()->subDays(2))
            ->limit(100)->get();

        foreach ($pendientes as $c) {
            if (! $c->token) {
                $c->token = bin2hex(random_bytes(16));
            }
            $ok = false;
            if ($conexion) {
                try {
                    $ok = WhatsAppNegocio::enviarTexto($conexion, preg_replace('/\D/', '', (string) $c->telefono), self::mensaje($negocio, $c, $cfg), 'carrito');
                } catch (\Throwable $e) {
                    $ok = false;
                }
            }
            // Se marca aunque falle: máximo un recordatorio por carrito
            $c->forceFill(['recordado_at' => now(), 'recordatorio_ok' => $ok, 'cupon' => $cfg['cupon'] ?: null])->saveQuietly();
        }

        return $pendientes->count();
    }

    public static function resumen(int $tenantId): array
    {
        $base = CheckoutAbandonado::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('created_at', '>=', now()->subDays(30));
        $total = (clone $base)->count();
        $recordados = (clone $base)->whereNotNull('recordado_at')->count();
        $recuperados = (clone $base)->where('recuperado', true)->count();

        return [
            'total' => $total,
            'recordados' => $recordados,
            'recuperados' => $recuperados,
            'tasa' => $recordados ? round((clone $base)->whereNotNull('recordado_at')->where('recuperado', true)->count() / $recordados * 100, 1) : 0,
            'monto_recuperado' => round((float) (clone $base)->where('recuperado', true)->sum('total'), 2),
            'monto_pendiente' => round((float) (clone $base)->where('recuperado', false)->sum('total'), 2),
        ];
    }
}
