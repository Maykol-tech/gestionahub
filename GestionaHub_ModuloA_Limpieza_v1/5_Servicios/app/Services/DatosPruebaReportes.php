<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Repartidor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Crea repartidores de prueba con entregas de los ultimos 30 dias, para ver los reportes con datos.
 * Solo se usa en desarrollo (la ruta se bloquea en produccion). Las entregas llevan numero "DEMO-"
 * para distinguirlas. Es idempotente: un repartidor que ya existe por nombre no se vuelve a crear.
 */
class DatosPruebaReportes
{
    public const NOMBRES = [
        ['Luis Quispe', 'moto'], ['Rosa Huamán', 'moto'], ['Carlos Ramos', 'bicicleta'],
        ['Milagros Soto', 'moto'], ['Jorge Paredes', 'auto'], ['Andrea Vilca', 'bicicleta'],
        ['Diego Mendoza', 'moto'], ['Sofía Ríos', 'pie'],
    ];

    /**
     * Completa hasta $cantidad repartidores de prueba (los que ya existen cuentan).
     *
     * @return array{repartidores: int, entregas: int}
     */
    public function generar(int $tenantId, int $cantidad = 5): array
    {
        $nombres = array_column(self::NOMBRES, 0);
        $existentes = Repartidor::query()->where('tenant_id', $tenantId)->whereIn('nombre', $nombres)->pluck('nombre')->all();
        $faltan = max(0, min($cantidad, count($nombres)) - count($existentes));

        $creados = 0;
        $entregas = 0;
        foreach (self::NOMBRES as [$nombre, $vehiculo]) {
            if ($creados >= $faltan) {
                break;
            }
            if (in_array($nombre, $existentes, true)) {
                continue;
            }

            $repartidor = Repartidor::query()->create([
                'tenant_id' => $tenantId,
                'nombre' => $nombre,
                'telefono' => '9' . random_int(10000000, 99999999),
                'vehiculo' => $vehiculo,
                'activo' => true,
                'disponible' => true,
            ]);
            $creados++;
            $entregas += $this->entregas($tenantId, $repartidor);
        }

        return ['repartidores' => $creados, 'entregas' => $entregas];
    }

    private function entregas(int $tenantId, Repartidor $repartidor): int
    {
        $total = random_int(12, 20);
        $hoy = now();

        for ($i = 0; $i < $total; $i++) {
            $dias = random_int(0, 29);
            $inicio = $hoy->copy()->subDays($dias)->setTime(random_int(10, 21), random_int(0, 59));
            if ($inicio->isFuture()) {
                $inicio = $hoy->copy()->subHour();
            }
            $asignado = $inicio->copy()->addMinutes(random_int(6, 15));
            $entregado = $asignado->copy()->addMinutes(random_int(12, 40) + $repartidor->id % 3 * 5);

            $pedido = new Pedido;
            $pedido->forceFill([
                'tenant_id' => $tenantId,
                'numero' => 'DEMO-' . Str::upper(Str::random(8)),
                'canal' => random_int(0, 2) === 0 ? 'whatsapp' : 'tienda',
                'tipo' => 'delivery',
                'estado' => 'entregado',
                'metodo_pago' => ['efectivo', 'yape', 'plin'][random_int(0, 2)],
                'pagado' => true,
                'subtotal' => $subtotal = random_int(1500, 9000) / 100,
                'costo_envio' => 5,
                'igv' => 0,
                'total' => $subtotal + 5,
                'repartidor_id' => $repartidor->id,
                'confirmado_at' => $inicio->copy()->addMinutes(2),
                'asignado_at' => $asignado,
                'entregado_at' => $entregado,
                'created_at' => $inicio,
                'updated_at' => $entregado,
            ]);
            $pedido->save();
        }

        return $total;
    }
}
