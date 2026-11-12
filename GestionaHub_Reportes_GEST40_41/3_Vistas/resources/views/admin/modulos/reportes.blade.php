@extends('layouts.app')

@section('titulo', 'Reportes')
@section('subtitulo', $desdeTexto . ' - ' . $hastaTexto)

@section('acciones')
    <div class="seg" role="tablist" aria-label="Periodo">
        @foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $dias => $texto)
            <a href="{{ route('admin.modulo', 'reportes') }}?rango={{ $dias }}&vista={{ $vista }}" data-rango="{{ $dias }}" class="{{ $rango === $dias ? 'on' : '' }}">{{ $texto }}</a>
        @endforeach
    </div>
    <button class="btn btn-g" type="button" x-data @click="$dispatch('exportar-reporte', 'todo')" title="Libro con las hojas Ventas por canal y Repartidores">
        <x-icon name="download" size="15" /> <span class="rep-exp-txt">Exportar Excel</span>
    </button>
@endsection

@section('contenido')
@php
    $iconosKpi = ['trending' => ['var(--acl)', 'var(--accent)'], 'clipboard' => ['var(--ctl)', 'var(--cta)'], 'tag' => ['var(--greenl)', 'var(--green)'], 'users' => ['var(--ambl)', 'var(--amber)']];
@endphp
<div x-data="reportesPage(@js([
        'rango' => $rango,
        'vista' => $vista,
        'grafico' => $grafico,
        'repartidores' => collect($delivery['lista'])->where('entregados', '>', 0)->values(),
        'exportarUrl' => route('admin.reportes.exportar'),
    ]))" @exportar-reporte.window="exportar($event.detail)">

    <div class="tabs rep-tabs" role="tablist" aria-label="Tipo de reporte">
        <button type="button" class="tab" role="tab" :class="{ on: vista === 'ventas' }" :aria-selected="vista === 'ventas'" @click="cambiar('ventas')">
            Ventas por canal<span class="n">S/ {{ number_format($ventas, 0) }}</span>
        </button>
        <button type="button" class="tab" role="tab" :class="{ on: vista === 'repartidores' }" :aria-selected="vista === 'repartidores'" @click="cambiar('repartidores')">
            Repartidores<span class="n">{{ $delivery['entregas'] }}</span>
        </button>
    </div>

    {{-- ================= GEST-40: VENTAS POR PERIODO Y MODULO ================= --}}
    <section x-show="vista === 'ventas'" {{ $vista === 'ventas' ? '' : 'x-cloak' }}>
        <div class="stat-row">
            <div class="stat fade-in">
                <div class="stat-lbl">Ventas totales <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="trending" size="14" /></span></div>
                <div class="stat-val">S/ {{ number_format($ventas, 2) }}</div>
                @if ($variacion === null)
                    <div class="stat-ch neutral">Sin periodo anterior para comparar</div>
                @else
                    <div class="stat-ch {{ $variacion >= 0 ? 'up' : 'dn' }}">{{ $variacion >= 0 ? '+' : '' }}{{ $variacion }}% vs. {{ $rango }} días anteriores</div>
                @endif
            </div>
            <div class="stat fade-in" style="animation-delay:.05s">
                <div class="stat-lbl">Pedidos y ventas <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="clipboard" size="14" /></span></div>
                <div class="stat-val">{{ number_format($cantidad) }}</div>
                <div class="stat-ch neutral">Todos los módulos, sin cancelados</div>
            </div>
            <div class="stat fade-in" style="animation-delay:.1s">
                <div class="stat-lbl">Ticket promedio <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="tag" size="14" /></span></div>
                <div class="stat-val">S/ {{ number_format($ticket, 2) }}</div>
                <div class="stat-ch neutral">Venta media por operación</div>
            </div>
            <div class="stat fade-in" style="animation-delay:.15s">
                <div class="stat-lbl">Clientes únicos <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="users" size="14" /></span></div>
                <div class="stat-val">{{ number_format($clientesUnicos) }}</div>
                <div class="stat-ch neutral">Con al menos un pedido</div>
            </div>
        </div>

        <div class="panel fade-in" style="animation-delay:.18s;margin-bottom:14px;">
            <div class="panel-hd">
                <div>
                    <div class="panel-ttl">{{ $grafico['titulo'] }}</div>
                    <div class="panel-sub">Últimos {{ $rango }} días · pasa el mouse sobre una barra para ver el detalle</div>
                </div>
            </div>
            <div class="rep-chart">
                @if ($ventas > 0)
                    <canvas x-ref="graficoVentas" role="img" aria-label="{{ $grafico['titulo'] }}"></canvas>
                    <div class="rep-chart-msg" x-show="sinGraficos" x-cloak>No se pudo cargar el gráfico. Los datos están completos en la tabla de abajo.</div>
                @else
                    <x-vacio icono="chart" titulo="Aún no hay ventas en este periodo" texto="El gráfico se completará con las ventas del POS, la tienda pública, WhatsApp y los registros manuales de los últimos {{ $rango }} días." />
                @endif
            </div>
        </div>

        <div class="g-11" style="margin-bottom:14px;">
            <div class="panel fade-in" style="animation-delay:.24s">
                <div class="panel-hd"><div class="panel-ttl">Ventas por módulo / canal</div></div>
                @foreach ($porCanal as $f)
                    <div class="kv" style="display:block;">
                        <div style="display:flex;justify-content:space-between;gap:10px;">
                            <span style="font-weight:600;display:flex;align-items:center;gap:8px;"><span class="rep-dot" style="background:{{ $f['color'] }}"></span>{{ $f['texto'] }}</span>
                            <span style="font-weight:700;font-variant-numeric:tabular-nums;">S/ {{ number_format($f['monto'], 2) }}</span>
                        </div>
                        <div style="font-size:11px;color:var(--fg3);">{{ number_format($f['cantidad']) }} {{ $f['cantidad'] === 1 ? 'pedido' : 'pedidos' }} · {{ rtrim(rtrim(number_format($f['porcentaje'], 1), '0'), '.') }}%</div>
                        <div class="hbar"><span style="width:{{ $f['porcentaje'] }}%;background:{{ $f['color'] }}"></span></div>
                    </div>
                @endforeach
            </div>
            <div class="panel fade-in" style="animation-delay:.29s">
                <div class="panel-hd"><div class="panel-ttl">Ventas por método de pago</div></div>
                @forelse ($porMetodo as $f)
                    <div class="kv" style="display:block;">
                        <div style="display:flex;justify-content:space-between;gap:10px;">
                            <span style="font-weight:600;">{{ $f['texto'] }}</span>
                            <span style="font-weight:700;font-variant-numeric:tabular-nums;">S/ {{ number_format($f['monto'], 2) }}</span>
                        </div>
                        <div style="font-size:11px;color:var(--fg3);">{{ number_format($f['cantidad']) }} {{ $f['cantidad'] === 1 ? 'operación' : 'operaciones' }} · {{ $ventas > 0 ? round($f['monto'] * 100 / $ventas) : 0 }}%</div>
                        <div class="hbar"><span style="width:{{ $ventas > 0 ? min(100, round($f['monto'] * 100 / $ventas)) : 0 }}%"></span></div>
                    </div>
                @empty
                    <x-vacio icono="card" titulo="Aún no hay datos registrados" texto="Aquí verás cuánto entra por efectivo, Yape, Plin y tarjeta." />
                @endforelse
            </div>
        </div>

        <div class="panel fade-in" style="animation-delay:.32s">
            <div class="panel-hd">
                <div>
                    <div class="panel-ttl">Detalle por día y módulo</div>
                    <div class="panel-sub">Las mismas columnas que la hoja de Excel</div>
                </div>
                <button class="btn btn-g btn-sm" type="button" style="margin-left:auto;" @click="exportar('ventas')"><x-icon name="download" size="14" /> Excel de ventas</button>
            </div>
            @if (count($detalle))
                <div class="tbl-wrap rep-scroll">
                    <table class="tbl rep-tbl">
                        <thead><tr><th>Fecha</th><th>Módulo/Canal</th><th class="num">N° Pedidos</th><th class="num">Total Ventas (S/)</th></tr></thead>
                        <tbody>
                            @foreach ($detalle as $f)
                                <tr>
                                    <td style="font-variant-numeric:tabular-nums;">{{ \Illuminate\Support\Carbon::parse($f['fecha'])->format('d/m/Y') }}</td>
                                    <td><span class="rep-dot" style="background:{{ \App\Services\ReportesService::COLORES[$f['canal']] }}"></span> {{ $f['modulo'] }}</td>
                                    <td class="num">{{ number_format($f['pedidos']) }}</td>
                                    <td class="num" style="font-weight:700;">S/ {{ number_format($f['total'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><td colspan="2">TOTAL</td><td class="num">{{ number_format($cantidad) }}</td><td class="num">S/ {{ number_format($ventas, 2) }}</td></tr></tfoot>
                    </table>
                </div>
            @else
                <x-vacio icono="clipboard" titulo="Sin ventas en este periodo" texto="Cuando registres ventas aparecerán aquí, día por día y por módulo." />
            @endif
        </div>
    </section>

    {{-- ================= GEST-41: DELIVERY, TIEMPOS Y REPARTIDORES ================= --}}
    <section x-show="vista === 'repartidores'" {{ $vista === 'repartidores' ? '' : 'x-cloak' }}>
        <div class="stat-row">
            <div class="stat fade-in">
                <div class="stat-lbl">Pedidos entregados <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="truck" size="14" /></span></div>
                <div class="stat-val">{{ number_format($delivery['entregas']) }}</div>
                <div class="stat-ch neutral">Por repartidores, últimos {{ $rango }} días</div>
            </div>
            <div class="stat fade-in" style="animation-delay:.05s">
                <div class="stat-lbl">Tiempo promedio <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="clock" size="14" /></span></div>
                <div class="stat-val">{{ $delivery['promedioGeneral'] !== null ? number_format($delivery['promedioGeneral'], 1) . ' min' : '—' }}</div>
                <div class="stat-ch neutral">De la asignación a la entrega</div>
            </div>
            <div class="stat fade-in" style="animation-delay:.1s">
                <div class="stat-lbl">Más rápido <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="activity" size="14" /></span></div>
                <div class="stat-val rep-nombre">{{ $delivery['masRapido']['nombre'] ?? '—' }}</div>
                <div class="stat-ch neutral">{{ $delivery['masRapido'] ? number_format($delivery['masRapido']['promedio'], 1) . ' min en promedio' : 'Aún sin entregas medidas' }}</div>
            </div>
            <div class="stat fade-in" style="animation-delay:.15s">
                <div class="stat-lbl">Repartidores activos <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="users" size="14" /></span></div>
                <div class="stat-val">{{ $delivery['activos'] }}</div>
                <div class="stat-ch neutral">Registrados en Repartos</div>
            </div>
        </div>

        <div class="rep-pila">
            <div class="panel fade-in" style="animation-delay:.18s">
                <div class="panel-hd">
                    <div>
                        <div class="panel-ttl">Tiempo promedio de entrega</div>
                        <div class="panel-sub">Minutos por repartidor (menos es mejor)</div>
                    </div>
                </div>
                <div class="rep-chart rep-chart-h">
                    @if ($delivery['entregas'] > 0)
                        <canvas x-ref="graficoRepartidores" role="img" aria-label="Tiempo promedio de entrega por repartidor"></canvas>
                        <div class="rep-chart-msg" x-show="sinGraficos" x-cloak>No se pudo cargar el gráfico. Los datos están completos en la tabla.</div>
                    @elseif (! $delivery['disponible'])
                        <x-vacio icono="truck" titulo="Falta activar el módulo de Repartos" texto="Ejecuta php artisan migrate para crear la tabla de repartidores y las horas de asignación y entrega." />
                    @else
                        <x-vacio icono="truck" titulo="Aún no hay entregas en este periodo" texto="Cuando un repartidor marque un pedido como entregado verás aquí su tiempo promedio." />
                    @endif
                </div>
            </div>

            <div class="panel fade-in" style="animation-delay:.22s">
                <div class="panel-hd">
                    <div>
                        <div class="panel-ttl">Rendimiento por repartidor</div>
                        <div class="panel-sub">Ordenado por pedidos entregados</div>
                    </div>
                    <button class="btn btn-g btn-sm" type="button" style="margin-left:auto;" @click="exportar('repartidores')"><x-icon name="download" size="14" /> Excel de repartidores</button>
                </div>
                @if (count($delivery['lista']))
                    <div class="tbl-wrap">
                        <table class="tbl rep-tbl">
                            <thead><tr><th>Repartidor</th><th class="num">Pedidos Entregados</th><th class="num">Tiempo Promedio (min)</th><th class="num">Rango (min)</th><th class="num">Monto entregado</th></tr></thead>
                            <tbody>
                                @foreach ($delivery['lista'] as $r)
                                    <tr>
                                        <td style="font-weight:600;">{{ $r['nombre'] }}</td>
                                        <td class="num">{{ number_format($r['entregados']) }}</td>
                                        <td class="num" style="font-weight:700;">{{ $r['promedio'] !== null ? number_format($r['promedio'], 1) : '—' }}</td>
                                        <td class="num" style="color:var(--fg3);">{{ $r['minimo'] !== null ? number_format($r['minimo'], 0) . ' – ' . number_format($r['maximo'], 0) : '—' }}</td>
                                        <td class="num">S/ {{ number_format($r['monto'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-vacio icono="users" titulo="Aún no tienes repartidores" texto="Regístralos en el módulo Repartos para medir sus entregas." />
                @endif
                <div class="nota rep-nota">El tiempo se mide desde que el pedido se asigna al repartidor hasta que se marca como entregado. Si el pedido no tiene hora de asignación se usa la de confirmación.</div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .rep-tabs{padding:0;margin:0 0 16px;}
        .rep-chart{position:relative;height:280px;padding:12px 16px 16px;}
        .rep-chart-h{height:auto;min-height:220px;}
        .rep-chart .vacio{height:100%;}
        .rep-chart-msg{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;text-align:center;padding:20px;font-size:13px;color:var(--fg3);}
        .rep-dot{display:inline-block;width:9px;height:9px;border-radius:3px;flex:none;vertical-align:middle;}
        .rep-scroll{max-height:420px;overflow-y:auto;}
        .rep-tbl thead th{position:sticky;top:0;background:var(--surface2);z-index:1;}
        .rep-tbl .num{text-align:right;font-variant-numeric:tabular-nums;}
        .rep-tbl tfoot td{font-weight:700;border-top:2px solid var(--border);background:var(--surface3);padding:10px 14px;}
        .rep-tbl.tbl{min-width:0;}
        .rep-nombre{font-size:20px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .rep-nota{margin:12px 16px 16px;}
        .rep-pila{display:grid;gap:14px;}
        .rep-tbl td:first-child{white-space:nowrap;}
        @media (max-width:860px){ .rep-chart{height:240px;padding:8px;} }
        @media (max-width:600px){ .rep-exp-txt{display:none;} }
    </style>
@endpush

@push('scripts')
<script>
    // Chart.js se carga una sola vez y solo en esta pagina (tambien al navegar sin recargar)
    window.ghChart = window.ghChart || function () {
        if (window.Chart) return Promise.resolve(window.Chart);
        window.ghChartCarga = window.ghChartCarga || new Promise((ok, falla) => {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js';
            s.onload = () => ok(window.Chart);
            s.onerror = () => { window.ghChartCarga = null; s.remove(); falla(new Error('Chart.js no disponible')); };
            document.head.appendChild(s);
        });
        return window.ghChartCarga;
    };

    window.reportesPage = function (datos) {
        let graficos = {};   // fuera del estado reactivo de Alpine: Chart.js no debe envolverse en Proxy

        return {
            vista: datos.vista,
            sinGraficos: false,

            init() {
                this.dibujar();
                this.$watch('vista', () => this.$nextTick(() => this.dibujar()));
            },

            destroy() {
                Object.values(graficos).forEach(g => g.destroy());
                graficos = {};
            },

            cambiar(vista) {
                this.vista = vista;
                // Recuerda la pestaña en la URL y en los botones de periodo
                const url = new URL(location.href);
                url.searchParams.set('vista', vista);
                url.searchParams.set('rango', datos.rango);
                history.replaceState(history.state, '', url);
                document.querySelectorAll('[data-rango]').forEach(a => {
                    const u = new URL(a.href);
                    u.searchParams.set('vista', vista);
                    a.href = u.toString();
                });
            },

            async dibujar() {
                const lienzo = this.vista === 'ventas' ? this.$refs.graficoVentas : this.$refs.graficoRepartidores;
                if (!lienzo || graficos[this.vista]) return;
                let Chart;
                try { Chart = await window.ghChart(); }
                catch (e) { this.sinGraficos = true; return; }
                if (!lienzo.isConnected || graficos[this.vista]) return;

                const css = getComputedStyle(document.documentElement);
                const texto = css.getPropertyValue('--fg3').trim() || '#94A3B8';
                const rejilla = css.getPropertyValue('--border').trim() || '#E2E7EF';
                Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
                Chart.defaults.color = texto;
                const animacion = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 700, easing: 'easeOutQuart' };

                if (this.vista === 'ventas') {
                    const g = datos.grafico;
                    graficos.ventas = new Chart(lienzo, {
                        type: 'bar',
                        data: {
                            labels: g.etiquetas,
                            datasets: g.series.map(s => ({
                                label: s.nombre, data: s.datos, backgroundColor: s.color,
                                borderRadius: 4, borderSkipped: false, maxBarThickness: 42,
                            })),
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, animation: animacion,
                            interaction: { mode: 'index', intersect: false },
                            scales: {
                                x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, autoSkipPadding: 8 } },
                                y: { stacked: true, beginAtZero: true, grid: { color: rejilla }, border: { display: false },
                                     ticks: { callback: v => 'S/ ' + Number(v).toLocaleString('es-PE') } },
                            },
                            plugins: {
                                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rectRounded', padding: 16 } },
                                tooltip: {
                                    filter: item => item.raw > 0,
                                    callbacks: {
                                        title: items => items.length ? g.detalles[items[0].dataIndex] : '',
                                        label: item => ' ' + item.dataset.label + ': ' + gh.soles(item.raw),
                                        footer: items => 'Total: ' + gh.soles(items.reduce((s, i) => s + i.raw, 0)),
                                    },
                                },
                            },
                        },
                    });
                } else {
                    const lista = datos.repartidores;
                    lienzo.parentElement.style.height = Math.max(220, lista.length * 42 + 60) + 'px';
                    graficos.repartidores = new Chart(lienzo, {
                        type: 'bar',
                        data: {
                            labels: lista.map(r => r.nombre),
                            datasets: [{
                                label: 'Minutos promedio', data: lista.map(r => r.promedio ?? 0),
                                backgroundColor: '#2B6EF6', borderRadius: 4, maxBarThickness: 26,
                            }],
                        },
                        options: {
                            indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: animacion,
                            scales: {
                                x: { beginAtZero: true, grid: { color: rejilla }, border: { display: false }, ticks: { callback: v => v + ' min' } },
                                y: { grid: { display: false } },
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: item => ' ' + Number(item.raw).toFixed(1) + ' min promedio',
                                        afterLabel: item => ' ' + lista[item.dataIndex].entregados + ' pedidos entregados',
                                    },
                                },
                            },
                        },
                    });
                }
            },

            // Descarga el .xlsx (hoja: todo | ventas | repartidores) con avisos de progreso
            async exportar(hoja = 'todo') {
                const toast = (t, tipo = 'ok') => Alpine.store('toasts').push(t, tipo);
                const url = datos.exportarUrl + '?rango=' + datos.rango + '&hoja=' + encodeURIComponent(hoja || 'todo');
                toast('Generando Excel…', 'info');
                try {
                    const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (res.status === 423) { window.dispatchEvent(new CustomEvent('gh-bloquear')); return; }
                    if (res.status === 401 || res.status === 419) { location.href = '/login'; return; }
                    if (!res.ok) {
                        const d = await res.json().catch(() => ({}));
                        toast((d.errors ? Object.values(d.errors)[0][0] : d.message) || 'No se pudo generar el Excel', 'error');
                        return;
                    }
                    const blob = await res.blob();
                    const m = (res.headers.get('Content-Disposition') || '').match(/filename="?([^";]+)"?/);
                    const nombre = m ? m[1] : 'reporte.xlsx';
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob); a.download = nombre;
                    document.body.appendChild(a); a.click(); a.remove();
                    setTimeout(() => URL.revokeObjectURL(a.href), 1500);
                    toast('Se descargó ' + nombre);
                } catch (e) {
                    toast('Sin conexión con el servidor. Intenta de nuevo.', 'error');
                }
            },
        };
    };
    gh.ready(() => Alpine.data('reportesPage', window.reportesPage));
</script>
@endpush
