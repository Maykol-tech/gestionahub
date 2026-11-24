<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\CampanaWhatsapp;
use App\Models\Comanda;
use App\Models\Insumo;
use App\Models\Pedido;
use Illuminate\Http\JsonResponse;

/** Avisos para la campana de notificaciones del panel. Se consulta cada minuto desde el navegador. */
class NotificacionesController extends DashboardController
{
    public function lista(): JsonResponse
    {
        $items = [];

        $pedidosNuevos = Pedido::query()->where('created_at', '>=', now()->subDay())->count();
        if ($pedidosNuevos > 0) {
            $items[] = ['tipo' => 'pedidos', 'texto' => "{$pedidosNuevos} pedidos en las últimas 24 horas", 'url' => url('admin/pedidos')];
        }

        // GEST-108: pedidos de la tienda online que esperan confirmación
        $tienda = Pedido::query()->where('canal', 'tienda')->where('estado', 'pendiente')->count();
        if ($tienda > 0) {
            $items[] = ['tipo' => 'tienda', 'texto' => "{$tienda} " . ($tienda === 1 ? 'pedido nuevo' : 'pedidos nuevos') . ' de la tienda online por confirmar', 'url' => url('admin/pedidos')];
        }

        $pagos = \App\Models\PagoManual::query()->where('estado', 'pendiente')->count();
        if ($pagos > 0) {
            $items[] = ['tipo' => 'pagos', 'texto' => "{$pagos} " . ($pagos === 1 ? 'pago por revisar' : 'pagos por revisar') . ' (Yape, Plin o transferencia)', 'url' => url('admin/pagos')];
        }

        $comandas = Comanda::query()->where('estado', 'pendiente')->count();
        if ($comandas > 0 && ! \App\Support\ModulosPrototipo::bloqueada('admin.cocina')) {
            $items[] = ['tipo' => 'cocina', 'texto' => "{$comandas} comandas pendientes en cocina", 'url' => url('admin/cocina')];
        }

        // Insumos en o por debajo del stock minimo (GEST-71)
        $bajos = Insumo::query()->where('activo', true)->whereColumn('stock', '<=', 'stock_minimo')->count();
        if ($bajos > 0) {
            $items[] = ['tipo' => 'stock', 'texto' => "{$bajos} " . ($bajos === 1 ? 'insumo está' : 'insumos están') . ' bajo el stock mínimo', 'url' => url('admin/produccion')];
        }

        $citasHoy = Cita::query()->whereDate('inicio', today())->whereNotIn('estado', ['cancelada'])->count();
        if ($citasHoy > 0) {
            $items[] = ['tipo' => 'citas', 'texto' => "{$citasHoy} citas para hoy", 'url' => url('admin/servicios')];
        }

        $programadas = CampanaWhatsapp::query()->where('estado', 'programada')->count();
        if ($programadas > 0) {
            $items[] = ['tipo' => 'campanas', 'texto' => "{$programadas} campañas programadas", 'url' => url('admin/campanas')];
        }

        return response()->json(['total' => count($items), 'items' => $items]);
    }

    /**
     * Pulso liviano para el sonido de avisos (cada 20 s): último pedido y última comanda.
     * El navegador compara con lo que ya vio y suena si llegó algo nuevo.
     */
    public function pulso(): JsonResponse
    {
        $ultimo = Pedido::query()->latest('id')->first(['id', 'numero', 'canal', 'total']);
        $comanda = Comanda::query()->latest('id')->first(['id', 'mesa_id']);

        return response()->json([
            'pedido' => $ultimo ? ['id' => $ultimo->id, 'numero' => $ultimo->numero, 'canal' => $ultimo->canal, 'total' => (float) $ultimo->total] : null,
            'comanda' => $comanda ? ['id' => $comanda->id] : null,
            'pendientes' => Pedido::query()->where('estado', 'pendiente')->count(),
        ]);
    }
}
