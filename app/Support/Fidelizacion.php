<?php

namespace App\Support;

use App\Models\CrmCliente;
use App\Models\FidelizacionMovimiento;
use App\Models\Pedido;
use App\Models\Recompensa;
use App\Models\VentaPos;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de fidelizacion:
 *  - Se ganan N puntos por cada S/ 1 (configurable, GEST-75) del pedido o venta POS, al pagarse o entregarse.
 *  - Los puntos pueden usarse como descuento con un tope del total (GEST-77).
 *  - Si el cliente no tiene movimientos en X meses, su saldo vence.
 *  - El nivel depende de los puntos ganados en total (historicos) y multiplica los puntos nuevos.
 *  - Si un pedido se cancela, sus puntos se revierten.
 *  - Canjear descuenta puntos y no cambia el nivel.
 */
class Fidelizacion
{
    /** Configuracion del programa del negocio (GEST-75). */
    public static function programa(?int $tenantId): array
    {
        $a = $tenantId ? AjustesNegocio::leer($tenantId) : AjustesNegocio::POR_DEFECTO;

        return [
            'activo' => (bool) $a['fid_activo'],
            'puntos_por_sol' => max(0, (float) $a['fid_puntos_por_sol']),
            'valor_punto' => max(0, (float) $a['fid_valor_punto']),
            'tope_pct' => min(100, max(0, (int) $a['fid_tope_pct'])),
            'vence_meses' => max(0, (int) $a['fid_vence_meses']),
        ];
    }

    /** Puntos que se pueden usar y su valor en soles, segun saldo, total y tope. */
    public static function maximoCanje(int $saldo, float $total, array $programa): array
    {
        if (! $programa['activo'] || $programa['valor_punto'] <= 0 || $saldo <= 0 || $total <= 0) {
            return ['puntos' => 0, 'soles' => 0.0];
        }
        $topeSoles = round($total * $programa['tope_pct'] / 100, 2);
        $puntos = (int) min($saldo, floor($topeSoles / $programa['valor_punto'] + 1e-9));

        return ['puntos' => $puntos, 'soles' => round($puntos * $programa['valor_punto'], 2)];
    }

    /** Registra un movimiento dejando el saldo resultante (GEST-76). Debe llamarse dentro de una transaccion. */
    private static function movimiento(CrmCliente $cliente, array $datos, int $delta, bool $sumaHistorico): void
    {
        $cliente->increment('puntos_saldo', $delta);
        if ($sumaHistorico && $delta > 0) {
            $cliente->increment('puntos_historicos', $delta);
        }
        FidelizacionMovimiento::withoutGlobalScopes()->create($datos + [
            'tenant_id' => $cliente->tenant_id, 'cliente_id' => $cliente->id, 'puntos' => $delta,
            'saldo_resultante' => (int) $cliente->fresh()->puntos_saldo,
        ]);
    }

    public const NIVELES = [
        'bronce' => ['nombre' => 'Bronce', 'desde' => 0, 'multiplicador' => 1.0, 'color' => '#B45309', 'fondo' => '#FEF3C7'],
        'plata' => ['nombre' => 'Plata', 'desde' => 500, 'multiplicador' => 1.2, 'color' => '#475569', 'fondo' => '#E2E8F0'],
        'oro' => ['nombre' => 'Oro', 'desde' => 1500, 'multiplicador' => 1.5, 'color' => '#A16207', 'fondo' => '#FEF9C3'],
    ];

    /** Niveles del negocio: los que configuro en el panel (GEST-75) o los NIVELES por defecto. */
    public static function nivelesDe(?int $tenantId): array
    {
        if ($tenantId === null) {
            return self::NIVELES;
        }

        $raw = DB::table('tenants')->where('id', $tenantId)->value('niveles_fidelizacion');
        $guardados = $raw ? json_decode((string) $raw, true) : null;

        if (! is_array($guardados)) {
            return self::NIVELES;
        }

        $niveles = self::NIVELES;
        foreach (array_keys(self::NIVELES) as $clave) {
            if (isset($guardados[$clave]['nombre'], $guardados[$clave]['desde'], $guardados[$clave]['multiplicador'])) {
                $niveles[$clave]['nombre'] = (string) $guardados[$clave]['nombre'];
                $niveles[$clave]['desde'] = (int) $guardados[$clave]['desde'];
                $niveles[$clave]['multiplicador'] = (float) $guardados[$clave]['multiplicador'];
                $niveles[$clave]['beneficio'] = (string) ($guardados[$clave]['beneficio'] ?? '');
            }
        }

        return $niveles;
    }

    public static function nivelDe(int $historicos, ?array $niveles = null): string
    {
        $niveles ??= self::NIVELES;
        $nivel = 'bronce';
        foreach ($niveles as $clave => $n) {
            if ($historicos >= $n['desde']) {
                $nivel = $clave;
            }
        }

        return $nivel;
    }

    public static function multiplicador(int $historicos, ?array $niveles = null): float
    {
        $niveles ??= self::NIVELES;

        return $niveles[self::nivelDe($historicos, $niveles)]['multiplicador'];
    }

    /** Se llama cada vez que cambia un pedido: da o revierte puntos segun su estado. */
    public static function alActualizarPedido(Pedido $pedido): void
    {
        $pagadoAhora = $pedido->wasChanged('pagado') && $pedido->pagado;
        $entregadoAhora = $pedido->wasChanged('estado') && $pedido->estado === 'entregado';

        if ($pagadoAhora || $entregadoAhora) {
            self::acreditar($pedido);
        }
        if ($pedido->wasChanged('estado') && $pedido->estado === 'cancelado') {
            self::revertir($pedido);
        }
    }

    public static function acreditar(Pedido $pedido): void
    {
        if (! $pedido->cliente_id || (float) $pedido->total <= 0) {
            return;
        }

        DB::transaction(function () use ($pedido) {
            $cliente = CrmCliente::withoutGlobalScopes()->lockForUpdate()->find($pedido->cliente_id);
            if (! $cliente) {
                return;
            }

            $puntos = self::puntosPorMonto($cliente, (float) $pedido->total);
            if ($puntos <= 0) {
                return;
            }

            if (FidelizacionMovimiento::withoutGlobalScopes()->where('pedido_id', $pedido->id)->where('tipo', 'ganado')->exists()) {
                return;
            }

            self::movimiento($cliente, ['pedido_id' => $pedido->id, 'tipo' => 'ganado', 'descripcion' => 'Pedido ' . $pedido->numero], $puntos, true);
        });
    }

    /** Puntos que gana un cliente por un monto, segun el programa y su nivel. */
    public static function puntosPorMonto(CrmCliente $cliente, float $monto): int
    {
        $tenant = $cliente->tenant_id ? (int) $cliente->tenant_id : null;
        $programa = self::programa($tenant);
        if (! $programa['activo']) {
            return 0;
        }
        $niveles = self::nivelesDe($tenant);

        return (int) round(floor($monto) * $programa['puntos_por_sol'] * self::multiplicador((int) $cliente->puntos_historicos, $niveles));
    }

    /** Venta del POS con cliente: descuenta los puntos usados y acredita los ganados (GEST-76/77). */
    public static function alCobrarVentaPos(VentaPos $venta): void
    {
        if (! $venta->cliente_id) {
            return;
        }
        DB::transaction(function () use ($venta) {
            $cliente = CrmCliente::withoutGlobalScopes()->lockForUpdate()->find($venta->cliente_id);
            if (! $cliente) {
                return;
            }
            $ya = fn (string $tipo) => FidelizacionMovimiento::withoutGlobalScopes()->where('venta_pos_id', $venta->id)->where('tipo', $tipo)->exists();

            if ((int) $venta->puntos_usados > 0 && ! $ya('canje')) {
                self::movimiento($cliente, ['venta_pos_id' => $venta->id, 'tipo' => 'canje',
                    'descripcion' => 'Descuento en venta ' . $venta->numero . ' (S/ ' . number_format((float) $venta->descuento, 2) . ')'], -(int) $venta->puntos_usados, false);
            }
            $puntos = self::puntosPorMonto($cliente, (float) $venta->total);
            if ($puntos > 0 && ! $ya('ganado')) {
                self::movimiento($cliente, ['venta_pos_id' => $venta->id, 'tipo' => 'ganado', 'descripcion' => 'Venta ' . $venta->numero], $puntos, true);
            }
        });
    }

    /** Venta del POS anulada: quita lo ganado y devuelve lo canjeado. */
    public static function alAnularVentaPos(VentaPos $venta): void
    {
        if (! $venta->cliente_id) {
            return;
        }
        DB::transaction(function () use ($venta) {
            $cliente = CrmCliente::withoutGlobalScopes()->lockForUpdate()->find($venta->cliente_id);
            $movs = FidelizacionMovimiento::withoutGlobalScopes()->where('venta_pos_id', $venta->id)->get()->keyBy('tipo');
            if (! $cliente || $movs->has('reversion')) {
                return;
            }
            $delta = -(int) ($movs['ganado']->puntos ?? 0) - (int) ($movs['canje']->puntos ?? 0);
            if ($delta === 0) {
                return;
            }
            if (isset($movs['ganado'])) {
                $cliente->decrement('puntos_historicos', min((int) $movs['ganado']->puntos, (int) $cliente->puntos_historicos));
            }
            self::movimiento($cliente, ['venta_pos_id' => $venta->id, 'tipo' => 'reversion', 'descripcion' => 'Venta ' . $venta->numero . ' anulada'], $delta, false);
        });
    }

    /** Vence el saldo de clientes sin movimientos en X meses. Devuelve cuantos clientes vencieron. */
    public static function vencerSaldos(int $tenantId): int
    {
        $meses = self::programa($tenantId)['vence_meses'];
        if ($meses <= 0) {
            return 0;
        }
        $limite = now()->subMonths($meses);
        $conMovimiento = FidelizacionMovimiento::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $limite)->select('cliente_id');
        $clientes = CrmCliente::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('puntos_saldo', '>', 0)
            ->whereNotIn('id', $conMovimiento)->get();

        foreach ($clientes as $c) {
            DB::transaction(fn () => self::movimiento($c, ['tipo' => 'vencido', 'descripcion' => "Puntos vencidos tras {$meses} meses sin compras"], -(int) $c->puntos_saldo, false));
        }

        return $clientes->count();
    }

    public static function revertir(Pedido $pedido): void
    {
        DB::transaction(function () use ($pedido) {
            $ganado = FidelizacionMovimiento::withoutGlobalScopes()->where('pedido_id', $pedido->id)->where('tipo', 'ganado')->first();
            if (! $ganado) {
                return;
            }

            if (FidelizacionMovimiento::withoutGlobalScopes()->where('pedido_id', $pedido->id)->where('tipo', 'reversion')->exists()) {
                return;
            }
            $cliente = CrmCliente::withoutGlobalScopes()->lockForUpdate()->find($ganado->cliente_id);
            if (! $cliente) {
                return;
            }
            $cliente->decrement('puntos_historicos', min($ganado->puntos, (int) $cliente->puntos_historicos));
            self::movimiento($cliente, ['pedido_id' => $pedido->id, 'tipo' => 'reversion', 'descripcion' => 'Pedido ' . $pedido->numero . ' cancelado'], -(int) $ganado->puntos, false);
        });
    }

    public static function canjear(CrmCliente $cliente, Recompensa $recompensa): void
    {
        DB::transaction(function () use ($cliente, $recompensa) {
            $cliente = CrmCliente::query()->lockForUpdate()->findOrFail($cliente->id);

            if ((int) $cliente->puntos_saldo < (int) $recompensa->puntos_requeridos) {
                throw ValidationException::withMessages([
                    'recompensa_id' => "{$cliente->nombre} necesita {$recompensa->puntos_requeridos} puntos y tiene {$cliente->puntos_saldo}.",
                ]);
            }

            self::movimiento($cliente, ['tipo' => 'canje', 'descripcion' => 'Canje: ' . $recompensa->nombre], -(int) $recompensa->puntos_requeridos, false);
        });
    }

    /** Ajuste manual (por ejemplo, una promocion o un error corregido). */
    public static function ajustar(CrmCliente $cliente, int $puntos, string $motivo): void
    {
        DB::transaction(function () use ($cliente, $puntos, $motivo) {
            $cliente = CrmCliente::query()->lockForUpdate()->findOrFail($cliente->id);

            if ($puntos < 0 && (int) $cliente->puntos_saldo < abs($puntos)) {
                throw ValidationException::withMessages(['puntos' => "El saldo de {$cliente->nombre} es {$cliente->puntos_saldo} puntos."]);
            }

            self::movimiento($cliente, ['tipo' => 'ajuste', 'descripcion' => $motivo], $puntos, true);
        });
    }

    /** GEST-106: el cliente usa puntos al comprar en la tienda online (dentro de la transacción del pedido). */
    public static function canjearEnPedido(CrmCliente $cliente, Pedido $pedido, int $puntos): void
    {
        if ($puntos <= 0) {
            return;
        }
        $cliente = CrmCliente::withoutGlobalScopes()->lockForUpdate()->findOrFail($cliente->id);
        if ((int) $cliente->puntos_saldo < $puntos) {
            throw ValidationException::withMessages(['puntos' => 'Ya no tienes esos puntos disponibles.']);
        }
        self::movimiento($cliente, ['pedido_id' => $pedido->id, 'tipo' => 'canje', 'descripcion' => 'Canje en el pedido ' . $pedido->numero], -$puntos, false);
    }
}
