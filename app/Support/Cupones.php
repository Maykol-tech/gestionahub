<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** GEST-106: cupones de descuento de la tienda (porcentaje o monto fijo, con mínimo, usos y vencimiento). */
class Cupones
{
    public static function normalizar(?string $codigo): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $codigo));
    }

    /** Devuelve [descuento, cupón] o lanza un error claro. */
    public static function aplicar(int $tenantId, ?string $codigo, float $subtotal, bool $bloquear = false): array
    {
        $codigo = self::normalizar($codigo);
        if ($codigo === '') {
            return [0.0, null];
        }
        $c = DB::table('cupones_tienda')->where('tenant_id', $tenantId)->where('codigo', $codigo)->where('activo', true)
            ->when($bloquear, fn ($q) => $q->lockForUpdate())->first();
        $error = fn ($m) => throw ValidationException::withMessages(['cupon' => $m]);

        if (! $c) {
            $error('Ese cupón no existe o ya no está activo.');
        }
        if ($c->vence && now()->startOfDay()->greaterThan($c->vence)) {
            $error('Ese cupón ya venció.');
        }
        if ($c->usos_max !== null && (int) $c->usos >= (int) $c->usos_max) {
            $error('Ese cupón ya se usó todas las veces permitidas.');
        }
        if ($subtotal < (float) $c->minimo) {
            $error('Este cupón es para compras desde ' . number_format((float) $c->minimo, 2) . '.');
        }

        $descuento = $c->tipo === 'monto' ? (float) $c->valor : round($subtotal * min(100, (float) $c->valor) / 100, 2);

        return [min($descuento, $subtotal), $c];
    }

    public static function usar(object $cupon): void
    {
        DB::table('cupones_tienda')->where('id', $cupon->id)->increment('usos');
    }
}
