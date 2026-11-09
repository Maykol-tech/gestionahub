<?php

namespace Database\Seeders;

use App\Models\BotFlujo;
use App\Models\CampanaGasto;
use App\Models\CampanaPublicidad;
use App\Models\CierreCaja;
use App\Models\Cita;
use App\Models\Insumo;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraItem;
use App\Models\OrdenProduccion;
use App\Models\Proveedor;
use App\Models\Receta;
use App\Models\RecetaInsumo;
use App\Models\Servicio;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Datos de demo para los modulos nuevos: insumos, recetas, lotes, citas, campanas, proveedores,
 * cierres de caja y flujos del bot. Cada bloque se salta si el negocio ya tiene datos.
 */
class DemoModulosSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->get()->each(fn (Tenant $t) => $this->sembrar((int) $t->id));
    }

    private function sembrar(int $tid): void
    {
        $this->produccion($tid);
        $this->servicios($tid);
        $this->publicidad($tid);
        $this->proveedores($tid);
        $this->caja($tid);
        $this->bot($tid);
    }

    private function produccion(int $tid): void
    {
        if (Insumo::withoutGlobalScopes()->where('tenant_id', $tid)->exists()) {
            return;
        }

        $insumos = collect([
            ['Harina', 'kg', 25, 5, 3.20],
            ['Azúcar', 'kg', 20, 4, 3.50],
            ['Huevos', 'und', 120, 30, 0.55],
            ['Mantequilla', 'kg', 6, 2, 18.00],
            ['Leche evaporada', 'l', 12, 4, 5.80],
        ])->mapWithKeys(fn ($d) => [$d[0] => Insumo::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'nombre' => $d[0], 'unidad' => $d[1], 'stock' => $d[2],
            'stock_minimo' => $d[3], 'costo_unitario' => $d[4], 'activo' => true,
        ])]);

        $receta = Receta::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'nombre' => 'Queque de vainilla', 'rendimiento' => 12,
            'notas' => 'Receta base para 12 porciones.', 'activo' => true,
        ]);

        $costoTanda = 0.0;

        foreach (['Harina' => 0.5, 'Azúcar' => 0.3, 'Huevos' => 4, 'Mantequilla' => 0.2, 'Leche evaporada' => 0.15] as $nombre => $cantidad) {
            RecetaInsumo::withoutGlobalScopes()->create([
                'tenant_id' => $tid, 'receta_id' => $receta->id,
                'insumo_id' => $insumos[$nombre]->id, 'cantidad' => $cantidad,
            ]);
            $costoTanda += $cantidad * (float) $insumos[$nombre]->costo_unitario;
        }

        // Mismo calculo que Produccion::costoPorUnidad, con los insumos ya en memoria.
        $costoUnidad = round($costoTanda / max(1, (int) $receta->rendimiento), 4);

        foreach ([[2, 24, 'Queque de vainilla · tanda mañana'], [5, 36, 'Queque de vainilla · pedido grande']] as [$hace, $cantidad, $titulo]) {
            OrdenProduccion::withoutGlobalScopes()->create([
                'tenant_id' => $tid, 'titulo' => $titulo, 'receta_id' => $receta->id, 'cantidad' => $cantidad,
                'fecha' => now()->subDays($hace)->toDateString(), 'estado' => 'terminada',
                'costo_total' => round($costoUnidad * $cantidad, 2), 'descontado_at' => now()->subDays($hace),
            ]);
        }

        OrdenProduccion::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'titulo' => 'Queque de vainilla · hoy', 'receta_id' => $receta->id, 'cantidad' => 12,
            'fecha' => now()->toDateString(), 'estado' => 'pendiente',
        ]);
    }

    private function servicios(int $tid): void
    {
        if (Servicio::withoutGlobalScopes()->where('tenant_id', $tid)->exists()) {
            return;
        }

        $consulta = Servicio::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'nombre' => 'Consulta general', 'descripcion' => 'Atención personalizada.',
            'precio' => 50, 'duracion_min' => 30, 'activo' => true,
        ]);
        $express = Servicio::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'nombre' => 'Servicio express', 'descripcion' => 'Atención rápida.',
            'precio' => 35, 'duracion_min' => 20, 'activo' => true,
        ]);

        $citas = [
            [-3, 10, $consulta, 'Lucía Torres', '987111222', 'completada'],
            [-1, 15, $express, 'Carlos Mendoza', '987333444', 'no_asistio'],
            [0, 11, $consulta, 'Ana Quispe', '987555666', 'confirmada'],
            [1, 9, $express, 'Miguel Rojas', '987777888', 'pendiente'],
            [2, 16, $consulta, 'Rosa Vega', '987999000', 'pendiente'],
            [3, 10, $express, 'Jorge Salas', '986111333', 'cancelada'],
        ];

        foreach ($citas as [$dia, $hora, $servicio, $cliente, $telefono, $estado]) {
            Cita::withoutGlobalScopes()->create([
                'tenant_id' => $tid, 'servicio_id' => $servicio->id, 'cliente_nombre' => $cliente,
                'cliente_telefono' => $telefono,
                'inicio' => now()->addDays($dia)->setTime($hora, 0, 0),
                'duracion_min' => $servicio->duracion_min, 'precio' => $servicio->precio,
                'estado' => $estado, 'notas' => null,
            ]);
        }
    }

    private function publicidad(int $tid): void
    {
        if (CampanaPublicidad::withoutGlobalScopes()->where('tenant_id', $tid)->exists()) {
            return;
        }

        $campanas = [
            [
                'nombre' => 'Promo fin de semana', 'canal' => 'facebook', 'objetivo' => 'ventas', 'inversion' => 300,
                'alcance' => 12000, 'clics' => 540, 'conversiones' => 38, 'ventas_atribuidas' => 1450,
                'inicio' => now()->subDays(12), 'fin' => now()->subDays(5), 'activo' => true,
                'gastos' => [[12, 150, 'Anuncio alcance local'], [8, 140, 'Remarketing a visitantes']],
            ],
            [
                'nombre' => 'Nuevos productos', 'canal' => 'instagram', 'objetivo' => 'trafico', 'inversion' => 200,
                'alcance' => 8000, 'clics' => 320, 'conversiones' => 15, 'ventas_atribuidas' => 690,
                'inicio' => now()->subDays(4), 'fin' => now()->addDays(20), 'activo' => true,
                'gastos' => [[4, 90, 'Reel de lanzamiento'], [2, 60, 'Impulso de publicación']],
            ],
        ];

        foreach ($campanas as $c) {
            $campana = CampanaPublicidad::withoutGlobalScopes()->create([
                'tenant_id' => $tid, 'nombre' => $c['nombre'], 'canal' => $c['canal'], 'objetivo' => $c['objetivo'],
                'inversion' => $c['inversion'], 'alcance' => $c['alcance'], 'clics' => $c['clics'],
                'conversiones' => $c['conversiones'], 'ventas_atribuidas' => $c['ventas_atribuidas'],
                'fecha_inicio' => $c['inicio']->toDateString(), 'fecha_fin' => $c['fin']->toDateString(),
                'activo' => $c['activo'], 'notas' => null,
            ]);

            foreach ($c['gastos'] as [$hace, $monto, $concepto]) {
                CampanaGasto::withoutGlobalScopes()->create([
                    'tenant_id' => $tid, 'campana_id' => $campana->id,
                    'fecha' => now()->subDays($hace)->toDateString(), 'monto' => $monto, 'concepto' => $concepto,
                ]);
            }
        }
    }

    private function proveedores(int $tid): void
    {
        if (Proveedor::withoutGlobalScopes()->where('tenant_id', $tid)->exists()) {
            return;
        }

        $proveedor = Proveedor::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'nombre' => 'Distribuidora Central SAC', 'ruc' => '20456789012',
            'contacto' => 'Pedro Huamán', 'telefono' => '014567890', 'email' => 'ventas@distribuidoracentral.example',
            'condicion_pago' => 'credito_15', 'notas' => 'Entrega los martes y viernes.', 'activo' => true,
        ]);

        $harina = Insumo::withoutGlobalScopes()->where('tenant_id', $tid)->where('nombre', 'Harina')->first();

        $orden = OrdenCompra::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'proveedor_id' => $proveedor->id, 'fecha' => now()->subDays(6)->toDateString(),
            'estado' => 'recibida', 'total' => 160, 'notas' => 'Compra mensual de harina.', 'recibida_at' => now()->subDays(5),
        ]);

        OrdenCompraItem::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'orden_compra_id' => $orden->id, 'insumo_id' => $harina?->id,
            'descripcion' => 'Harina', 'cantidad' => 50, 'precio_unitario' => 3.20, 'subtotal' => 160,
        ]);

        $borrador = OrdenCompra::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'proveedor_id' => $proveedor->id, 'fecha' => now()->toDateString(),
            'estado' => 'borrador', 'total' => 90, 'notas' => null,
        ]);

        OrdenCompraItem::withoutGlobalScopes()->create([
            'tenant_id' => $tid, 'orden_compra_id' => $borrador->id, 'insumo_id' => null,
            'descripcion' => 'Cajas de empaque 20x20', 'cantidad' => 30, 'precio_unitario' => 3, 'subtotal' => 90,
        ]);
    }

    private function caja(int $tid): void
    {
        if (CierreCaja::withoutGlobalScopes()->where('tenant_id', $tid)->exists()) {
            return;
        }

        $dias = [
            ['hace' => 2, 'inicial' => 100, 'ventas_ef' => 420, 'ventas_dig' => 610, 'gastos_ef' => 35, 'gastos_dig' => 0, 'diferencia' => 0],
            ['hace' => 1, 'inicial' => 100, 'ventas_ef' => 355, 'ventas_dig' => 480, 'gastos_ef' => 60, 'gastos_dig' => 120, 'diferencia' => -5],
        ];

        foreach ($dias as $d) {
            $esperado = round($d['inicial'] + $d['ventas_ef'] - $d['gastos_ef'], 2);

            CierreCaja::withoutGlobalScopes()->create([
                'tenant_id' => $tid, 'fecha' => now()->subDays($d['hace'])->toDateString(),
                'monto_inicial' => $d['inicial'], 'ventas_efectivo' => $d['ventas_ef'], 'ventas_digitales' => $d['ventas_dig'],
                'gastos_efectivo' => $d['gastos_ef'], 'gastos_digitales' => $d['gastos_dig'],
                'esperado' => $esperado, 'contado' => $esperado + $d['diferencia'], 'diferencia' => $d['diferencia'],
                'cerrado_por' => 'Demo', 'notas' => $d['diferencia'] < 0 ? 'Diferencia por cambio mal dado.' : null,
            ]);
        }
    }

    private function bot(int $tid): void
    {
        if (BotFlujo::withoutGlobalScopes()->where('tenant_id', $tid)->exists()) {
            return;
        }

        $flujos = [
            ['Saludo', 'hola', 'exacta', '¡Hola! Bienvenido. Escribe «horario», «precio» o «pedido» y te ayudamos.', 0],
            ['Horario', 'horario', 'contiene', 'Atendemos de lunes a sábado de 9:00 a 19:00.', 1],
            ['Precios', 'precio', 'contiene', 'Dinos el producto que te interesa y te enviamos el precio al momento.', 2],
            ['Pedidos', 'pedido', 'contiene', 'Para hacer tu pedido visita nuestra tienda online.', 3],
        ];

        foreach ($flujos as [$nombre, $disparador, $coincidencia, $respuesta, $orden]) {
            BotFlujo::withoutGlobalScopes()->create([
                'tenant_id' => $tid, 'nombre' => $nombre, 'disparador' => $disparador,
                'coincidencia' => $coincidencia, 'respuesta' => $respuesta, 'orden' => $orden, 'activo' => true,
            ]);
        }
    }
}
