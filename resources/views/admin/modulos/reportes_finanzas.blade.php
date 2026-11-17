@extends('layouts.app')

@section('titulo', 'Reportes y finanzas')
@section('subtitulo', 'Delivery, rentabilidad y comparativo de ventas')

@section('acciones')
    <a class="btn btn-g" href="{{ route('admin.modulo', 'reportes') }}"><x-icon name="chart" size="15" /> Ventas y repartos</a>
    @if ($tipo === 'rentabilidad')
        <button class="btn btn-p" type="button" x-data @click="$dispatch('exportar-finanzas')"><x-icon name="download" size="15" /> Exportar Excel</button>
    @endif
@endsection

@push('scripts')
<script>
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
    window.finanzasPage = function (filas, categorias) {
        let grafico = null;
        return {
            sinGrafico: false,
            async init() {
                try {
                    const Chart = await ghChart();
                    const css = getComputedStyle(document.documentElement);
                    const c = (v, d) => (css.getPropertyValue(v) || d).trim();
                    const etiquetas = filas.map(f => { const [m, a] = f.mes.split('/'); return ['ene','feb','mar','abr','may','jun','jul','ago','set','oct','nov','dic'][m - 1] + ' ' + a.slice(2); });
                    if (grafico) grafico.destroy();
                    grafico = new Chart(this.$refs.grafico, {
                        data: {
                            labels: etiquetas,
                            datasets: [
                                { type: 'bar', label: 'Ingresos', data: filas.map(f => f.ingresos), backgroundColor: c('--green', '#16A34A') + 'CC', borderRadius: 6, maxBarThickness: 26, order: 2 },
                                { type: 'bar', label: 'Egresos', data: filas.map(f => f.gastos), backgroundColor: c('--red', '#DC2626') + 'B3', borderRadius: 6, maxBarThickness: 26, order: 2 },
                                { type: 'line', label: 'Utilidad', data: filas.map(f => f.utilidad), borderColor: c('--accent', '#2B6EF6'), backgroundColor: c('--accent', '#2B6EF6'), tension: .35, borderWidth: 3, pointRadius: 3, pointHoverRadius: 6, order: 1 },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                            animation: { duration: 900, easing: 'easeOutQuart' },
                            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                                tooltip: { callbacks: { label: (x) => ' ' + x.dataset.label + ': ' + gh.soles(x.parsed.y) } } },
                            scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'S/ ' + Number(v).toLocaleString('es-PE') }, grid: { color: 'rgba(148,163,184,.18)' } }, x: { grid: { display: false } } },
                        },
                    });
                } catch (e) { this.sinGrafico = true; }
            },
            exportar() {
                gh.excel('Rentabilidad mensual', [['Mes'], ['Ingresos', 'moneda'], ['Egresos', 'moneda'], ['Utilidad', 'moneda'], ['Margen %', 'decimal']],
                    filas.map(f => [f.mes, f.ingresos, f.gastos, f.utilidad, f.margen ?? '']));
            },
        };
    };
</script>
@endpush

@push('styles')
    @include('admin.modulos._base')
    <style>
        .fin-tabs { display:inline-flex; gap:4px; padding:4px; border-radius:12px; background:var(--card, #fff); border:1px solid var(--bd); margin-bottom:14px; flex-wrap:wrap; }
        .fin-tabs a { font-size:13px; font-weight:700; padding:8px 14px; border-radius:9px; color:var(--fg2); text-decoration:none; transition:background .15s, color .15s; }
        .fin-tabs a:hover { background:var(--bg); }
        .fin-tabs a.activo { background:var(--accent); color:#fff; box-shadow:0 4px 12px color-mix(in srgb, var(--accent) 35%, transparent); }
        .fin-grid { display:grid; grid-template-columns:2fr 1fr; gap:14px; margin-bottom:14px; }
        .fin-chart { position:relative; height:320px; padding:14px; }
        .var { display:inline-flex; align-items:center; gap:3px; font-size:12px; font-weight:700; padding:2px 8px; border-radius:99px; }
        .var.up { background:var(--greenl); color:var(--green); } .var.down { background:var(--redl); color:var(--red); } .var.eq { background:var(--bg); color:var(--fg3); }
        .cat-row { padding:10px 16px; border-top:1px solid var(--bd); }
        .cat-row:first-child { border-top:0; }
        .cat-bar { height:8px; border-radius:99px; background:var(--bg); overflow:hidden; margin-top:6px; }
        .cat-bar span { display:block; height:100%; border-radius:99px; background:linear-gradient(90deg, var(--red), #F97316); transform-origin:left; animation:crecer .8s cubic-bezier(.2,.8,.2,1) both; }
        @media (max-width: 960px) { .fin-grid { grid-template-columns:1fr; } }
        .kpis { display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap:12px; margin-bottom:12px; }
        .kpi { padding:14px; }
        .kpi .n { font-size:22px; font-weight:800; font-variant-numeric: tabular-nums; }
        .kpi .l { font-size:12px; color:var(--fg3); }
        .barra-bg { background:var(--bg); border-radius:6px; height:12px; overflow:hidden; min-width:120px; }
        .barra { height:100%; background:var(--brand); border-radius:6px; transform-origin:left; animation: crecer .6s ease both; }
        .neg { color:#B91C1C; }
        .pos { color:#15803D; }
        @keyframes crecer { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        @media (prefers-reduced-motion: reduce) { .barra { animation:none; } }
    </style>
@endpush

@section('contenido')
<div>
    <div class="fin-tabs" role="tablist">
        <a class="{{ $tipo === 'rentabilidad' ? 'activo' : '' }}" href="{{ route('admin.reportes.finanzas', ['tipo' => 'rentabilidad']) }}">Rentabilidad mensual</a>
        <a class="{{ $tipo === 'comparativo' ? 'activo' : '' }}" href="{{ route('admin.reportes.finanzas', ['tipo' => 'comparativo']) }}">Comparativo del mes</a>
        <a class="{{ $tipo === 'delivery' ? 'activo' : '' }}" href="{{ route('admin.reportes.finanzas', ['tipo' => 'delivery']) }}">Tiempos de delivery</a>
    </div>

    @if ($tipo === 'comparativo')
        <div class="kpis">
            <div class="panel kpi fade-in"><div class="n">S/ {{ number_format($datos['actual'], 2) }}</div><div class="l">Mes actual ({{ $datos['periodo_actual'] }})</div></div>
            <div class="panel kpi fade-in"><div class="n">S/ {{ number_format($datos['anterior'], 2) }}</div><div class="l">Mes anterior ({{ $datos['periodo_anterior'] }})</div></div>
            <div class="panel kpi fade-in">
                <div class="n {{ ($datos['variacion'] ?? 0) >= 0 ? 'pos' : 'neg' }}">{{ $datos['variacion'] === null ? '—' : ($datos['variacion'] > 0 ? '+' : '') . $datos['variacion'] . '%' }}</div>
                <div class="l">Variación frente al mes anterior</div>
            </div>
        </div>
        <div class="panel" style="padding:12px;font-size:12px;color:var(--fg3);">Ingresos = pedidos pagados + ventas del POS.</div>

    @elseif ($tipo === 'rentabilidad')
        @php
            $flecha = function ($v, $invertir = false) {
                if ($v === null) return '<span class="var eq">—</span>';
                $bueno = $invertir ? $v <= 0 : $v >= 0;
                return '<span class="var ' . ($v == 0 ? 'eq' : ($bueno ? 'up' : 'down')) . '">' . ($v > 0 ? '▲ ' : ($v < 0 ? '▼ ' : '')) . abs($v) . '%</span>';
            };
            $r = $resumen;
        @endphp
        <div x-data="finanzasPage(@js($datos), @js($categorias))" @exportar-finanzas.window="exportar()">
            <div class="stat-row">
                <div class="stat fade-in">
                    <div class="stat-lbl">Ingresos del mes <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="trending" size="14" /></span></div>
                    <div class="stat-val" data-contar="{{ $r['actual']['ingresos'] }}">S/ {{ number_format($r['actual']['ingresos'], 2) }}</div>
                    <div class="stat-ch neutral">{!! $flecha($r['variacion']['ingresos']) !!} vs. mes anterior (S/ {{ number_format($r['anterior']['ingresos'], 2) }})</div>
                </div>
                <div class="stat fade-in" style="animation-delay:.05s">
                    <div class="stat-lbl">Egresos del mes <span class="stat-ico" style="background:var(--redl);color:var(--red)"><x-icon name="cash" size="14" /></span></div>
                    <div class="stat-val" data-contar="{{ $r['actual']['gastos'] }}">S/ {{ number_format($r['actual']['gastos'], 2) }}</div>
                    <div class="stat-ch neutral">{!! $flecha($r['variacion']['gastos'], true) !!} vs. mes anterior (S/ {{ number_format($r['anterior']['gastos'], 2) }})</div>
                </div>
                <div class="stat fade-in" style="animation-delay:.1s">
                    <div class="stat-lbl">Utilidad <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="chart" size="14" /></span></div>
                    <div class="stat-val" style="color:{{ $r['actual']['utilidad'] < 0 ? 'var(--red)' : 'var(--fg)' }}" data-contar="{{ $r['actual']['utilidad'] }}">S/ {{ number_format($r['actual']['utilidad'], 2) }}</div>
                    <div class="stat-ch neutral">{!! $flecha($r['variacion']['utilidad']) !!} vs. mes anterior</div>
                </div>
                <div class="stat fade-in" style="animation-delay:.15s">
                    <div class="stat-lbl">Margen <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="layers" size="14" /></span></div>
                    <div class="stat-val">{{ $r['actual']['margen'] === null ? '—' : $r['actual']['margen'] . '%' }}</div>
                    <div class="stat-ch neutral">{{ $r['variacion']['margen'] === null ? 'Sin datos del mes anterior' : (($r['variacion']['margen'] >= 0 ? '+' : '') . $r['variacion']['margen'] . ' puntos vs. mes anterior') }}</div>
                </div>
            </div>

            <div class="fin-grid">
                <div class="panel fade-in" style="animation-delay:.18s">
                    <div class="panel-hd"><div><div class="panel-ttl">Rentabilidad de los últimos 12 meses</div><div class="panel-sub">Ingresos (pedidos pagados, POS y manuales), egresos y utilidad</div></div></div>
                    <div class="fin-chart"><canvas x-ref="grafico" aria-label="Gráfico de rentabilidad mensual"></canvas>
                        <div x-show="sinGrafico" x-cloak class="empty" style="position:absolute;inset:0;">No se pudo cargar el gráfico. La tabla de abajo tiene los mismos datos.</div>
                    </div>
                </div>
                <div class="panel fade-in" style="animation-delay:.24s">
                    <div class="panel-hd"><div><div class="panel-ttl">Egresos por categoría</div><div class="panel-sub">{{ $r['mes'] }}</div></div></div>
                    @php $maxCat = collect($categorias)->max('total') ?: 1; @endphp
                    @forelse ($categorias as $i => $c)
                        <div class="cat-row">
                            <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="font-weight:600;">{{ $c['categoria'] }}</span><span style="font-weight:700;">S/ {{ number_format($c['total'], 2) }}</span></div>
                            <div style="font-size:11px;color:var(--fg3);">{{ $c['cantidad'] }} {{ $c['cantidad'] === 1 ? 'gasto' : 'gastos' }}</div>
                            <div class="cat-bar"><span style="width:{{ round($c['total'] / $maxCat * 100) }}%;animation-delay:{{ 200 + $i * 80 }}ms"></span></div>
                        </div>
                    @empty
                        <x-vacio icono="cash" titulo="Sin egresos este mes" texto="Registra tus gastos desde Caja del día para ver en qué se va el dinero." />
                    @endforelse
                </div>
            </div>

            <div class="panel fade-in" style="padding:0;animation-delay:.3s">
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead><tr><th>Mes</th><th class="num">Ingresos</th><th class="num">Egresos</th><th class="num">Utilidad</th><th class="num">Margen</th></tr></thead>
                        <tbody>
                            @foreach (array_reverse($datos) as $i => $f)
                                <tr class="row-in" style="--d:{{ $i * 30 }}ms">
                                    <td style="font-weight:600;">{{ $f['mes'] }}</td>
                                    <td class="num">S/ {{ number_format($f['ingresos'], 2) }}</td>
                                    <td class="num">S/ {{ number_format($f['gastos'], 2) }}</td>
                                    <td class="num {{ $f['utilidad'] >= 0 ? 'pos' : 'neg' }}" style="font-weight:700;">S/ {{ number_format($f['utilidad'], 2) }}</td>
                                    <td class="num">{{ $f['margen'] === null ? '—' : $f['margen'] . '%' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @else
        <form method="GET" action="{{ route('admin.reportes.finanzas') }}" class="panel" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
            <input type="hidden" name="tipo" value="delivery">
            <div><label class="lbl" for="desde">Desde</label><input class="inp" type="date" id="desde" name="desde" value="{{ $desde }}"></div>
            <div><label class="lbl" for="hasta">Hasta</label><input class="inp" type="date" id="hasta" name="hasta" value="{{ $hasta }}"></div>
            <button class="btn btn-p" type="submit">Ver</button>
        </form>

        <div class="kpis">
            <div class="panel kpi fade-in"><div class="n">{{ $datos['entregas'] }}</div><div class="l">Entregas</div></div>
            <div class="panel kpi fade-in"><div class="n">{{ $datos['asignado_a_entrega'] ?? '—' }} min</div><div class="l">Promedio desde asignación</div></div>
            <div class="panel kpi fade-in"><div class="n">{{ $datos['confirmado_a_entrega'] ?? '—' }} min</div><div class="l">Promedio desde confirmación</div></div>
        </div>

        <div class="panel fade-in" style="padding:0;">
            <div class="tbl-wrap" @if (empty($datos['por_repartidor'])) style="display:none" @endif>
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Repartidor</th>
                            <th class="num">Entregas</th>
                            <th class="num">Min. desde asignación</th>
                            <th class="num">Min. desde confirmación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datos['por_repartidor'] as $r)
                            <tr>
                                <td>{{ $r['nombre'] }}</td>
                                <td class="num">{{ $r['entregas'] }}</td>
                                <td class="num">{{ $r['asignado_a_entrega'] ?? '—' }}</td>
                                <td class="num">{{ $r['confirmado_a_entrega'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (empty($datos['por_repartidor']))
                <div class="empty"><div style="font-weight:600;color:var(--fg2);">No hay entregas en este periodo</div></div>
            @endif
        </div>
    @endif
</div>
@endsection
