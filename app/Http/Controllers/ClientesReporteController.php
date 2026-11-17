<?php

namespace App\Http\Controllers;

use App\Models\CrmCliente;
use App\Models\Pedido;
use App\Models\VentaPos;
use App\Support\Fidelizacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public const PERIODOS = ['30' => 'Últimos 30 días', '90' => 'Últimos 90 días', '365' => 'Último año', 'todo' => 'Todo el historial'];

    /** GEST-79: top 10/20/50 por monto o número de compras, con periodo, nivel y exportación. */
    public function ranking(Request $request)
    {
        $filtros = [
            'top' => in_array((int) $request->query('top'), [10, 20, 50], true) ? (int) $request->query('top') : 20,
            'orden' => $request->query('orden') === 'pedidos' ? 'pedidos' : 'gastado',
            'periodo' => array_key_exists((string) $request->query('periodo'), self::PERIODOS) ? (string) $request->query('periodo') : '90',
            'nivel' => in_array($request->query('nivel'), ['bronce', 'plata', 'oro'], true) ? $request->query('nivel') : '',
        ];
        $filas = $this->filasRanking($filtros);

        if ($request->query('exportar')) {
            $nombre = 'ranking-clientes-' . now()->format('Ymd') . '.csv';

            return response()->streamDownload(function () use ($filas) {
                $salida = fopen('php://output', 'w');
                fwrite($salida, "\xEF\xBB\xBF");
                fputcsv($salida, ['Puesto', 'Cliente', 'Teléfono', 'Nivel', 'Compras', 'Total comprado', 'Ticket promedio', 'Última compra'], ';');
                foreach ($filas as $i => $f) {
                    fputcsv($salida, [$i + 1, $f['nombre'], $f['telefono'], $f['nivel_nombre'], $f['pedidos'], number_format($f['gastado'], 2, '.', ''), number_format($f['ticket'], 2, '.', ''), $f['ultimo']], ';');
                }
                fclose($salida);
            }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $this->compartirLayout();

        return view('admin.modulos.clientes_ranking', [
            'titulo' => 'Clientes frecuentes',
            'modulo' => 'clientes',
            'filas' => $filas,
            'filtros' => $filtros,
            'periodos' => self::PERIODOS,
            'niveles' => Fidelizacion::nivelesDe((int) auth()->user()->tenant_id),
        ]);
    }

    private function filasRanking(array $f): Collection
    {
        $desde = $f['periodo'] === 'todo' ? null : now()->subDays((int) $f['periodo']);

        // Pedidos pagados + ventas POS no anuladas con cliente
        $pedidos = Pedido::query()->where('pagado', true)->whereNotNull('cliente_id')
            ->when($desde, fn ($q) => $q->where('created_at', '>=', $desde))
            ->selectRaw('cliente_id, COUNT(*) as n, SUM(total) as monto, MAX(created_at) as ultimo')->groupBy('cliente_id')->get();
        $pos = VentaPos::query()->whereNotNull('cliente_id')->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 'anulada'))
            ->when($desde, fn ($q) => $q->where('created_at', '>=', $desde))
            ->selectRaw('cliente_id, COUNT(*) as n, SUM(total) as monto, MAX(created_at) as ultimo')->groupBy('cliente_id')->get();

        $agregado = [];
        foreach ($pedidos->concat($pos) as $r) {
            $a = $agregado[$r->cliente_id] ?? ['pedidos' => 0, 'gastado' => 0.0, 'ultimo' => null];
            $a['pedidos'] += (int) $r->n;
            $a['gastado'] += (float) $r->monto;
            $a['ultimo'] = max($a['ultimo'] ?? '', (string) $r->ultimo);
            $agregado[$r->cliente_id] = $a;
        }

        $config = Fidelizacion::nivelesDe((int) auth()->user()->tenant_id);
        $clientes = CrmCliente::query()->whereIn('id', array_keys($agregado))
            ->get(['id', 'nombre', 'telefono', 'puntos_historicos', 'puntos_saldo'])->keyBy('id');

        return collect($agregado)->map(function ($a, $id) use ($clientes, $config) {
            $c = $clientes->get($id);
            if (! $c) {
                return null;
            }
            $nivel = Fidelizacion::nivelDe((int) $c->puntos_historicos, $config);

            return [
                'id' => (int) $id,
                'nombre' => $c->nombre,
                'telefono' => $c->telefono ?? '',
                'nivel' => $nivel,
                'nivel_nombre' => $config[$nivel]['nombre'],
                'nivel_color' => $config[$nivel]['color'],
                'nivel_fondo' => $config[$nivel]['fondo'],
                'puntos' => (int) $c->puntos_saldo,
                'pedidos' => $a['pedidos'],
                'gastado' => round($a['gastado'], 2),
                'ticket' => $a['pedidos'] ? round($a['gastado'] / $a['pedidos'], 2) : 0,
                'ultimo' => $a['ultimo'] ? Carbon::parse($a['ultimo'])->format('d/m/Y') : '',
            ];
        })->filter()
            ->when($f['nivel'], fn ($c) => $c->where('nivel', $f['nivel']))
            ->sortByDesc(fn ($r) => $f['orden'] === 'pedidos' ? [$r['pedidos'], $r['gastado']] : [$r['gastado'], $r['pedidos']])
            ->take($f['top'])->values();
    }
}
