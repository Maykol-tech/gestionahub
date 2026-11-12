<?php

namespace App\Services;

use App\Models\IngresoManual;
use App\Models\Pedido;
use App\Models\Repartidor;
use App\Models\VentaPos;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Reportes GEST-40 y GEST-41.
 *  - GEST-40: ventas por periodo (7, 30 o 90 dias) desglosadas por modulo o canal de origen.
 *  - GEST-41: rendimiento de los repartidores (pedidos entregados y tiempo promedio de entrega).
 *
 * Fuentes de venta:
 *  - Ventas POS:       ventas_pos vigentes (no anuladas) + pedidos con canal "pos".
 *  - Tienda Publica:   pedidos con canal "tienda" (checkout publico) o "web".
 *  - WhatsApp / App:   pedidos con ese canal.
 *  - Ventas Manuales:  pedidos registrados a mano (canal "manual" o vacio) + ingresos manuales.
 * Los pedidos cancelados nunca suman.
 */
class ReportesService
{
    public const RANGOS = [7, 30, 90];

    /** Modulos del reporte, en el orden en que se muestran. */
    public const CANALES = [
        'pos' => 'Ventas POS',
        'tienda' => 'Tienda Pública',
        'whatsapp' => 'WhatsApp',
        'app' => 'App',
        'manual' => 'Ventas Manuales',
    ];

    /** Color de cada modulo en los graficos (el POS usa el azul de la marca). */
    public const COLORES = [
        'pos' => '#2B6EF6',
        'tienda' => '#8B5CF6',
        'whatsapp' => '#16A34A',
        'app' => '#F59E0B',
        'manual' => '#64748B',
    ];

    public const METODOS = [
        'efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'tarjeta' => 'Tarjeta',
        'mercadopago' => 'Mercado Pago', 'culqi' => 'Culqi', 'transferencia' => 'Transferencia',
    ];

    /** Canal guardado en pedidos.canal => modulo del reporte. */
    private const MAPA_CANAL = [
        'pos' => 'pos', 'tienda' => 'tienda', 'web' => 'tienda',
        'whatsapp' => 'whatsapp', 'app' => 'app', 'manual' => 'manual',
    ];

    public static function rangoValido(mixed $rango): int
    {
        $rango = (int) $rango;

        return in_array($rango, self::RANGOS, true) ? $rango : 7;
    }

    /** @return array{0: Carbon, 1: Carbon} desde el inicio del primer dia hasta ahora */
    public function periodo(int $rango): array
    {
        return [now()->startOfDay()->subDays($rango - 1), now()];
    }

    // =================================================================
    // GEST-40: VENTAS POR PERIODO Y MODULO
    // =================================================================

    /**
     * Una fila por dia y modulo con ventas (las combinaciones sin ventas no se listan).
     *
     * @return array<int, array{fecha: string, canal: string, modulo: string, pedidos: int, total: float}>
     */
    public function ventasPorDiaYCanal(int $tenantId, Carbon $desde, Carbon $hasta): array
    {
        $acumulado = [];
        $sumar = function (string $dia, string $canal, int $n, float $total) use (&$acumulado) {
            $clave = $dia . '|' . $canal;
            $acumulado[$clave] ??= ['fecha' => $dia, 'canal' => $canal, 'pedidos' => 0, 'total' => 0.0];
            $acumulado[$clave]['pedidos'] += $n;
            $acumulado[$clave]['total'] += $total;
        };

        // Pedidos (tienda, WhatsApp, app, POS y manuales), agrupados en la base de datos
        $pedidos = Pedido::query()->toBase()
            ->where('pedidos.tenant_id', $tenantId)
            ->where('estado', '!=', 'cancelado')
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw("DATE(created_at) as dia, COALESCE(canal, 'manual') as canal_origen, COUNT(*) as n, COALESCE(SUM(total), 0) as t")
            ->groupByRaw("DATE(created_at), COALESCE(canal, 'manual')")
            ->get();
        foreach ($pedidos as $f) {
            $sumar((string) $f->dia, self::MAPA_CANAL[$f->canal_origen] ?? 'manual', (int) $f->n, (float) $f->t);
        }

        // Ventas de mostrador (POS) no anuladas
        if ($this->existe('ventas_pos')) {
            $pos = VentaPos::query()->vigentes()->toBase()
                ->where('ventas_pos.tenant_id', $tenantId)
                ->whereBetween('created_at', [$desde, $hasta])
                ->selectRaw('DATE(created_at) as dia, COUNT(*) as n, COALESCE(SUM(total), 0) as t')
                ->groupByRaw('DATE(created_at)')
                ->get();
            foreach ($pos as $f) {
                $sumar((string) $f->dia, 'pos', (int) $f->n, (float) $f->t);
            }
        }

        // Ingresos registrados a mano (cuentan como ventas manuales)
        if ($this->existe('ingresos_manuales')) {
            $manuales = IngresoManual::query()->toBase()
                ->where('ingresos_manuales.tenant_id', $tenantId)
                ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
                ->selectRaw('DATE(fecha) as dia, COUNT(*) as n, COALESCE(SUM(monto), 0) as t')
                ->groupByRaw('DATE(fecha)')
                ->get();
            foreach ($manuales as $f) {
                $sumar((string) $f->dia, 'manual', (int) $f->n, (float) $f->t);
            }
        }

        $orden = array_flip(array_keys(self::CANALES));
        $filas = array_values($acumulado);
        usort($filas, fn ($a, $b) => [$a['fecha'], $orden[$a['canal']]] <=> [$b['fecha'], $orden[$b['canal']]]);

        return array_map(fn ($f) => array_merge($f, [
            'modulo' => self::CANALES[$f['canal']],
            'total' => round($f['total'], 2),
        ]), $filas);
    }

    /** Datos de la pestaña "Ventas por canal": KPIs, grafico apilado, resumen y detalle. */
    public function ventas(int $tenantId, int $rango): array
    {
        [$desde, $hasta] = $this->periodo($rango);
        $filas = $this->ventasPorDiaYCanal($tenantId, $desde, $hasta);
        $detalleAnterior = $this->ventasPorDiaYCanal($tenantId, $desde->copy()->subDays($rango), $desde->copy()->subSecond());

        $total = round(array_sum(array_column($filas, 'total')), 2);
        $cantidad = (int) array_sum(array_column($filas, 'pedidos'));
        $anterior = (float) array_sum(array_column($detalleAnterior, 'total'));

        // Resumen por modulo (siempre los cinco, aunque esten en cero)
        $porCanal = [];
        foreach (self::CANALES as $clave => $nombre) {
            $grupo = array_filter($filas, fn ($f) => $f['canal'] === $clave);
            $monto = round(array_sum(array_column($grupo, 'total')), 2);
            $porCanal[] = [
                'clave' => $clave,
                'texto' => $nombre,
                'color' => self::COLORES[$clave],
                'monto' => $monto,
                'cantidad' => (int) array_sum(array_column($grupo, 'pedidos')),
                'porcentaje' => $total > 0 ? round($monto * 100 / $total, 1) : 0,
            ];
        }
        usort($porCanal, fn ($a, $b) => $b['monto'] <=> $a['monto']);

        return [
            'ventas' => $total,
            'cantidad' => $cantidad,
            'ticket' => $cantidad ? round($total / $cantidad, 2) : 0,
            'clientesUnicos' => Pedido::query()->where('pedidos.tenant_id', $tenantId)
                ->where('estado', '!=', 'cancelado')->whereBetween('created_at', [$desde, $hasta])
                ->whereNotNull('cliente_id')->distinct()->count('cliente_id'),
            'variacion' => $anterior > 0 ? (int) round(($total - $anterior) * 100 / $anterior) : null,
            'porCanal' => $porCanal,
            'porMetodo' => $this->porMetodo($tenantId, $desde, $hasta),
            'grafico' => $this->grafico($filas, $desde, $rango),
            'detalle' => array_reverse($filas),   // en pantalla, lo mas reciente primero
            'filasExcel' => $filas,
        ];
    }

    /** Grafico apilado: por dia (7 y 30 dias) o por semana (90 dias), una serie por modulo. */
    private function grafico(array $filas, Carbon $desde, int $rango): array
    {
        $paso = $rango === 90 ? 7 : 1;
        $tramos = (int) ceil($rango / $paso);
        $etiquetas = [];
        $detalles = [];
        for ($i = 0; $i < $tramos; $i++) {
            $ini = $desde->copy()->addDays($i * $paso);
            $etiquetas[] = $paso === 7 ? $ini->format('d/m') : ($rango === 7 ? ucfirst($ini->translatedFormat('D d')) : $ini->format('d/m'));
            $detalles[] = $paso === 7 ? 'Semana del ' . $ini->format('d/m') : ucfirst($ini->translatedFormat('l d/m'));
        }

        $series = [];
        foreach (self::CANALES as $clave => $nombre) {
            $series[$clave] = ['clave' => $clave, 'nombre' => $nombre, 'color' => self::COLORES[$clave], 'datos' => array_fill(0, $tramos, 0.0)];
        }
        foreach ($filas as $f) {
            $i = intdiv((int) $desde->copy()->startOfDay()->diffInDays(Carbon::parse($f['fecha'])->startOfDay()), $paso);
            if (isset($series[$f['canal']]['datos'][$i])) {
                $series[$f['canal']]['datos'][$i] = round($series[$f['canal']]['datos'][$i] + $f['total'], 2);
            }
        }

        return [
            'etiquetas' => $etiquetas,
            'detalles' => $detalles,
            'titulo' => $paso === 7 ? 'Ventas por semana y módulo' : 'Ventas por día y módulo',
            // Solo las series con ventas, para no llenar la leyenda de ceros
            'series' => array_values(array_filter($series, fn ($s) => array_sum($s['datos']) > 0)),
        ];
    }

    /** Ventas por metodo de pago (pedidos + POS). */
    private function porMetodo(int $tenantId, Carbon $desde, Carbon $hasta): array
    {
        $montos = [];
        $sumar = function ($filas) use (&$montos) {
            foreach ($filas as $f) {
                $m = $f->metodo ?: 'efectivo';
                $montos[$m] ??= ['monto' => 0.0, 'cantidad' => 0];
                $montos[$m]['monto'] += (float) $f->t;
                $montos[$m]['cantidad'] += (int) $f->n;
            }
        };

        $sumar(Pedido::query()->toBase()->where('pedidos.tenant_id', $tenantId)
            ->where('estado', '!=', 'cancelado')->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('metodo_pago as metodo, COUNT(*) as n, COALESCE(SUM(total), 0) as t')
            ->groupBy('metodo_pago')->get());

        if ($this->existe('ventas_pos')) {
            $sumar(VentaPos::query()->vigentes()->toBase()->where('ventas_pos.tenant_id', $tenantId)
                ->whereBetween('created_at', [$desde, $hasta])
                ->selectRaw('metodo_pago as metodo, COUNT(*) as n, COALESCE(SUM(total), 0) as t')
                ->groupBy('metodo_pago')->get());
        }

        return collect($montos)
            ->map(fn ($v, $k) => ['texto' => self::METODOS[$k] ?? ucfirst((string) $k), 'monto' => round($v['monto'], 2), 'cantidad' => $v['cantidad']])
            ->sortByDesc('monto')->values()->all();
    }

    // =================================================================
    // GEST-41: DELIVERY, TIEMPOS Y REPARTIDORES
    // =================================================================

    /**
     * Rendimiento por repartidor en el periodo (pedidos entregados segun entregado_at).
     * Tiempo de entrega = desde que el pedido se asigna al repartidor hasta que se marca entregado
     * (si no hay hora de asignacion se usa la de confirmacion y, en ultimo caso, la de creacion).
     */
    public function repartidores(int $tenantId, int $rango): array
    {
        [$desde, $hasta] = $this->periodo($rango);

        $pedidos = Pedido::query()
            ->where('pedidos.tenant_id', $tenantId)
            ->where('estado', 'entregado')
            ->whereNotNull('repartidor_id')
            ->whereNotNull('entregado_at')
            ->whereBetween('entregado_at', [$desde, $hasta])
            ->with('repartidor:id,nombre')
            ->get(array_values(array_intersect(['id', 'repartidor_id', 'total', 'created_at', 'confirmado_at', 'asignado_at', 'entregado_at'], \Illuminate\Support\Facades\Schema::getColumnListing('pedidos'))));

        $filas = $pedidos->groupBy('repartidor_id')->map(function (Collection $grupo) {
            $minutos = $grupo->map(fn (Pedido $p) => $this->minutosEntrega($p))->filter(fn ($m) => $m !== null);

            return [
                'id' => (int) $grupo->first()->repartidor_id,
                'nombre' => $grupo->first()->repartidor?->nombre ?? 'Repartidor eliminado',
                'entregados' => $grupo->count(),
                'promedio' => $minutos->isNotEmpty() ? round($minutos->avg(), 1) : null,
                'minimo' => $minutos->isNotEmpty() ? round($minutos->min(), 1) : null,
                'maximo' => $minutos->isNotEmpty() ? round($minutos->max(), 1) : null,
                'monto' => round((float) $grupo->sum('total'), 2),
            ];
        });

        // Repartidores activos sin entregas en el periodo tambien aparecen (con cero)
        $sinEntregas = Repartidor::query()->where('tenant_id', $tenantId)->where('activo', true)
            ->whereNotIn('id', $filas->keys()->all())->orderBy('nombre')->get(['id', 'nombre'])
            ->map(fn ($r) => ['id' => $r->id, 'nombre' => $r->nombre, 'entregados' => 0, 'promedio' => null, 'minimo' => null, 'maximo' => null, 'monto' => 0.0]);

        $lista = $filas->values()
            ->sort(fn ($a, $b) => [$b['entregados'], $a['promedio'] ?? INF] <=> [$a['entregados'], $b['promedio'] ?? INF])
            ->values()->concat($sinEntregas)->all();

        $todos = $pedidos->map(fn (Pedido $p) => $this->minutosEntrega($p))->filter(fn ($m) => $m !== null);
        $masRapido = collect($lista)->filter(fn ($r) => $r['promedio'] !== null)->sortBy('promedio')->first();

        return [
            'entregas' => $pedidos->count(),
            'promedioGeneral' => $todos->isNotEmpty() ? round($todos->avg(), 1) : null,
            'masRapido' => $masRapido,
            'activos' => Repartidor::query()->where('tenant_id', $tenantId)->where('activo', true)->count(),
            'lista' => $lista,
        ];
    }

    private function minutosEntrega(Pedido $p): ?float
    {
        $inicio = $p->asignado_at ?? $p->confirmado_at ?? $p->created_at;
        if (! $inicio || ! $p->entregado_at || $p->entregado_at->lt($inicio)) {
            return null;
        }

        return ($p->entregado_at->getTimestamp() - $inicio->getTimestamp()) / 60;
    }

    // =================================================================
    // EXCEL
    // =================================================================

    /** Hoja GEST-40: Fecha, Modulo/Canal, N° Pedidos, Total Ventas (S/). */
    public function hojaVentas(int $tenantId, int $rango): array
    {
        [$desde, $hasta] = $this->periodo($rango);

        return [
            'titulo' => 'Ventas por canal',
            'columnas' => [
                ['titulo' => 'Fecha', 'tipo' => 'fecha'],
                ['titulo' => 'Módulo/Canal', 'tipo' => 'texto'],
                ['titulo' => 'N° Pedidos', 'tipo' => 'numero', 'suma' => true],
                ['titulo' => 'Total Ventas (S/)', 'tipo' => 'moneda'],
            ],
            'filas' => array_map(
                fn ($f) => [Carbon::parse($f['fecha'])->format('d/m/Y'), $f['modulo'], $f['pedidos'], $f['total']],
                $this->ventasPorDiaYCanal($tenantId, $desde, $hasta)
            ),
            'totales' => true,
        ];
    }

    /** Hoja GEST-41: Repartidor, Pedidos Entregados, Tiempo Promedio de Entrega (min). */
    public function hojaRepartidores(int $tenantId, int $rango): array
    {
        return [
            'titulo' => 'Repartidores',
            'columnas' => [
                ['titulo' => 'Repartidor', 'tipo' => 'texto'],
                ['titulo' => 'Pedidos Entregados', 'tipo' => 'numero', 'suma' => true],
                ['titulo' => 'Tiempo Promedio de Entrega (min)', 'tipo' => 'decimal'],
            ],
            'filas' => array_map(
                fn ($r) => [$r['nombre'], $r['entregados'], $r['promedio']],
                $this->repartidores($tenantId, $rango)['lista']
            ),
            'totales' => true,
        ];
    }

    private function existe(string $tabla): bool
    {
        static $tablas = [];

        return $tablas[$tabla] ??= Schema::hasTable($tabla);
    }
}
