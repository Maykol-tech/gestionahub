<?php

namespace App\Support;

use App\Models\Comanda;
use App\Models\Mesa;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Crea comandas desde una mesa, descuenta stock y controla el avance en cocina. */
class Comandas
{
    /**
     * @param  array<int, array{producto_id:int, cantidad:int}>  $solicitados
     */
    public static function crear(Mesa $mesa, array $solicitados, ?string $notas = null): Comanda
    {
        return DB::transaction(function () use ($mesa, $solicitados, $notas) {
            $lineas = [];
            $total = 0.0;

            foreach ($solicitados as $s) {
                $producto = Producto::withoutGlobalScopes()
                    ->where('tenant_id', $mesa->tenant_id)
                    ->lockForUpdate()
                    ->findOrFail($s['producto_id']);

                $cantidad = (int) $s['cantidad'];

                if (! $producto->activo || (int) $producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Sin stock suficiente de {$producto->nombre} (disponible: " . (int) $producto->stock . ').',
                    ]);
                }

                $precio = (float) ($producto->precio_oferta ?? $producto->precio);
                $subtotal = round($precio * $cantidad, 2);
                $total += $subtotal;

                $producto->decrement('stock', $cantidad);

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                ];
            }

            if (empty($lineas)) {
                throw ValidationException::withMessages(['items' => 'Agrega al menos un producto.']);
            }

            $comanda = Comanda::withoutGlobalScopes()->create([
                'tenant_id' => $mesa->tenant_id,
                'mesa_id' => $mesa->id,
                'estado' => 'pendiente',
                'items' => $lineas,
                'total' => round($total, 2),
                'notas' => $notas,
            ]);

            if ($mesa->estado === 'libre') {
                $mesa->update(['estado' => 'ocupada']);
            }

            return $comanda;
        });
    }

    /** Avanza la comanda segun TRANSICIONES. Al cancelar devuelve el stock. */
    public static function cambiarEstado(Comanda $comanda, string $nuevo): Comanda
    {
        $permitidos = Comanda::TRANSICIONES[$comanda->estado] ?? [];

        if (! in_array($nuevo, $permitidos, true)) {
            throw ValidationException::withMessages([
                'estado' => "No puedes pasar de {$comanda->estado} a {$nuevo}.",
            ]);
        }

        DB::transaction(function () use ($comanda, $nuevo) {
            if ($nuevo === 'cancelada') {
                foreach ($comanda->items ?? [] as $linea) {
                    Producto::withoutGlobalScopes()->whereKey($linea['producto_id'])
                        ->where('tenant_id', $comanda->tenant_id)
                        ->increment('stock', (int) $linea['cantidad']);
                }
            }

            $comanda->update(['estado' => $nuevo]);
        });

        return $comanda->fresh();
    }
}
