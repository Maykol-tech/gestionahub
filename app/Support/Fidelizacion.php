<?php

namespace App\Support;

use App\Models\CrmCliente;
use App\Models\FidelizacionMovimiento;
use App\Models\Pedido;
use App\Models\Recompensa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de fidelizacion:
 *  - Se gana 1 punto por cada S/ 1 del pedido, al pagarse o entregarse.
 *  - El nivel depende de los puntos ganados en total (historicos) y multiplica los puntos nuevos.
 *  - Si un pedido se cancela, sus puntos se revierten.
 *  - Canjear descuenta puntos y no cambia el nivel.
 */
class Fidelizacion
{
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

            $niveles = self::nivelesDe($cliente->tenant_id ? (int) $cliente->tenant_id : null);
            $puntos = (int) round(floor((float) $pedido->total) * self::multiplicador((int) $cliente->puntos_historicos, $niveles));
            if ($puntos <= 0) {
                return;
            }

            $movimiento = FidelizacionMovimiento::withoutGlobalScopes()->firstOrCreate(
                ['pedido_id' => $pedido->id, 'tipo' => 'ganado'],
                ['tenant_id' => $pedido->tenant_id, 'cliente_id' => $cliente->id, 'puntos' => $puntos, 'descripcion' => 'Pedido ' . $pedido->numero]
            );

            if ($movimiento->wasRecentlyCreated) {
                $cliente->increment('puntos_saldo', $puntos);
                $cliente->increment('puntos_historicos', $puntos);
            }
        });
    }

    public static function revertir(Pedido $pedido): void
    {
        DB::transaction(function () use ($pedido) {
            $ganado = FidelizacionMovimiento::withoutGlobalScopes()->where('pedido_id', $pedido->id)->where('tipo', 'ganado')->first();
            if (! $ganado) {
                return;
            }

            $reversion = FidelizacionMovimiento::withoutGlobalScopes()->firstOrCreate(
                ['pedido_id' => $pedido->id, 'tipo' => 'reversion'],
                ['tenant_id' => $pedido->tenant_id, 'cliente_id' => $ganado->cliente_id, 'puntos' => -$ganado->puntos, 'descripcion' => 'Pedido ' . $pedido->numero . ' cancelado']
            );

            if ($reversion->wasRecentlyCreated) {
                $cliente = CrmCliente::withoutGlobalScopes()->lockForUpdate()->find($ganado->cliente_id);
                $cliente?->decrement('puntos_saldo', $ganado->puntos);
                $cliente?->decrement('puntos_historicos', min($ganado->puntos, (int) $cliente->puntos_historicos));
            }
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

            FidelizacionMovimiento::create([
                'tenant_id' => $cliente->tenant_id, 'cliente_id' => $cliente->id, 'tipo' => 'canje',
                'puntos' => -(int) $recompensa->puntos_requeridos, 'descripcion' => 'Canje: ' . $recompensa->nombre,
            ]);
            $cliente->decrement('puntos_saldo', (int) $recompensa->puntos_requeridos);
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

            FidelizacionMovimiento::create([
                'tenant_id' => $cliente->tenant_id, 'cliente_id' => $cliente->id, 'tipo' => 'ajuste',
                'puntos' => $puntos, 'descripcion' => $motivo,
            ]);
            $cliente->increment('puntos_saldo', $puntos);
            if ($puntos > 0) {
                $cliente->increment('puntos_historicos', $puntos);
            }
        });
    }
}
