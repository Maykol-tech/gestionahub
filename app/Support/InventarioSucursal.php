<?php

namespace App\Support;

use App\Models\Producto;
use App\Models\StockSucursal;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Inventario por sucursal.
 *  - productos.stock es el total del negocio (así la tienda, pedidos y reportes siguen igual).
 *  - stock_sucursales guarda cuántas unidades hay en cada sucursal.
 *  - El local principal tiene lo que sobra: total - suma de sucursales.
 * Sucursal 0 (o null) = local principal.
 */
class InventarioSucursal
{
    public static function disponible(): bool
    {
        return Schema::hasTable('stock_sucursales');
    }

    /** Unidades por sucursal de un producto: [sucursal_id => stock]. */
    public static function reparto(int $productoId): array
    {
        return StockSucursal::query()->where('producto_id', $productoId)->pluck('stock', 'sucursal_id')->map(fn ($v) => (int) $v)->all();
    }

    public static function enLocal(Producto $p, ?int $sucursalId): int
    {
        $reparto = self::reparto($p->id);
        if ($sucursalId) {
            return (int) ($reparto[$sucursalId] ?? 0);
        }

        return max(0, (int) $p->stock - array_sum($reparto));
    }

    /** Mueve unidades entre locales sin cambiar el total. */
    public static function mover(Producto $p, ?int $desde, ?int $hacia, int $cantidad, string $motivo = 'traslado', ?string $usuario = null): void
    {
        $desde = $desde ?: null;
        $hacia = $hacia ?: null;
        if ($desde === $hacia) {
            throw ValidationException::withMessages(['hacia' => 'Elige un local de destino distinto al de origen.']);
        }
        if ($cantidad < 1) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser al menos 1.']);
        }

        DB::transaction(function () use ($p, $desde, $hacia, $cantidad, $motivo, $usuario) {
            $p = Producto::query()->lockForUpdate()->findOrFail($p->id);
            $hay = self::enLocal($p, $desde);
            if ($hay < $cantidad) {
                throw ValidationException::withMessages(['cantidad' => "Solo hay {$hay} unidades de {$p->nombre} en " . self::nombre($desde) . '.']);
            }
            if ($desde) {
                StockSucursal::query()->where('producto_id', $p->id)->where('sucursal_id', $desde)->decrement('stock', $cantidad);
            }
            if ($hacia) {
                $fila = StockSucursal::query()->firstOrCreate(
                    ['producto_id' => $p->id, 'sucursal_id' => $hacia],
                    ['tenant_id' => $p->tenant_id, 'stock' => 0]
                );
                $fila->increment('stock', $cantidad);
            }
            self::anotar($p, $desde, $hacia, $cantidad, $motivo, $usuario);
        });
    }

    /**
     * Una venta hecha en una sucursal: el total ya bajó en productos.stock,
     * aquí se baja también lo de esa sucursal. Llamar dentro de la transacción de la venta.
     */
    public static function descontarVenta(Producto $p, ?int $sucursalId, int $cantidad): void
    {
        if (! $sucursalId || ! self::disponible()) {
            return;
        }
        StockSucursal::query()->where('producto_id', $p->id)->where('sucursal_id', $sucursalId)
            ->where('stock', '>=', $cantidad)->decrement('stock', $cantidad);
        self::anotar($p, $sucursalId, null, $cantidad, 'venta', auth()->user()?->name);
    }

    /** Tabla de productos x locales para la pantalla. */
    public static function matriz(): array
    {
        $sucursales = Sucursal::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $repartos = StockSucursal::query()->get(['producto_id', 'sucursal_id', 'stock'])->groupBy('producto_id');

        $productos = Producto::query()->with('categoria:id,nombre')->where('activo', true)
            ->where(fn ($q) => $q->whereNull('tiene_variantes')->orWhere('tiene_variantes', false))
            ->orderBy('nombre')->limit(500)->get(['id', 'nombre', 'sku', 'stock', 'stock_minimo', 'categoria_id'])
            ->map(function (Producto $p) use ($repartos, $sucursales) {
                $por = ($repartos[$p->id] ?? collect())->pluck('stock', 'sucursal_id')->map(fn ($v) => (int) $v);
                $locales = ['0' => max(0, (int) $p->stock - $por->sum())];
                foreach ($sucursales as $s) {
                    $locales[(string) $s->id] = (int) ($por[$s->id] ?? 0);
                }

                return [
                    'id' => $p->id, 'nombre' => $p->nombre, 'sku' => $p->sku, 'categoria' => $p->categoria->nombre ?? null,
                    'total' => (int) $p->stock, 'minimo' => (int) $p->stock_minimo, 'locales' => $locales,
                ];
            })->values();

        return ['sucursales' => $sucursales, 'productos' => $productos];
    }

    public static function nombre(?int $sucursalId): string
    {
        return $sucursalId ? (Sucursal::query()->whereKey($sucursalId)->value('nombre') ?? 'la sucursal') : 'el local principal';
    }

    private static function anotar(Producto $p, ?int $desde, ?int $hacia, int $cantidad, string $motivo, ?string $usuario): void
    {
        if (! Schema::hasTable('movimientos_sucursal')) {
            return;
        }
        DB::table('movimientos_sucursal')->insert([
            'tenant_id' => $p->tenant_id, 'producto_id' => $p->id, 'desde_id' => $desde, 'hacia_id' => $hacia,
            'cantidad' => $cantidad, 'motivo' => $motivo, 'usuario' => $usuario ? mb_substr($usuario, 0, 120) : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
