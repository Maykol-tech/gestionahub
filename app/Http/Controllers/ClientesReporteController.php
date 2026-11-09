<?php

namespace App\Http\Controllers;

use App\Models\CrmCliente;
use App\Models\Pedido;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Reportes de clientes: exportacion a CSV (Excel) y ranking de clientes frecuentes. */
class ClientesReporteController extends DashboardController
{
    public function exportar(): StreamedResponse
    {
        $nombre = 'clientes-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel lea bien los acentos.
            fputcsv($salida, ['Nombre', 'Teléfono', 'Puntos disponibles', 'Puntos históricos'], ';');

            CrmCliente::query()->orderBy('nombre')->chunk(500, function ($clientes) use ($salida) {
                foreach ($clientes as $c) {
                    fputcsv($salida, [$c->nombre, $c->telefono, (int) $c->puntos_saldo, (int) $c->puntos_historicos], ';');
                }
            });

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function ranking()
    {
        $this->compartirLayout();

        $filas = Pedido::query()->where('pagado', true)->whereNotNull('cliente_id')
            ->selectRaw('cliente_id, COUNT(*) as pedidos, SUM(total) as gastado, MAX(created_at) as ultimo')
            ->groupBy('cliente_id')
            ->orderByDesc('gastado')
            ->limit(20)
            ->get();

        $clientes = CrmCliente::query()->whereIn('id', $filas->pluck('cliente_id'))
            ->get(['id', 'nombre', 'telefono'])->keyBy('id');

        return view('admin.modulos.clientes_ranking', [
            'titulo' => 'Clientes frecuentes',
            'modulo' => 'clientes',
            'filas' => $filas->map(function ($f) use ($clientes) {
                $c = $clientes->get($f->cliente_id);

                return [
                    'nombre' => $c->nombre ?? 'Cliente',
                    'telefono' => $c->telefono ?? '',
                    'pedidos' => (int) $f->pedidos,
                    'gastado' => round((float) $f->gastado, 2),
                    'ultimo' => Carbon::parse($f->ultimo)->format('d/m/Y'),
                ];
            })->values(),
        ]);
    }
}
