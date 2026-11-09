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

        $comandas = Comanda::query()->where('estado', 'pendiente')->count();
        if ($comandas > 0) {
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
}
