@extends('layouts.app')

@section('titulo', 'Reportes')
@section('subtitulo', $desdeTexto . ' - ' . $hastaTexto)

@section('acciones')
    <div class="seg" role="tablist" aria-label="Periodo">
        @foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $dias => $texto)
            <a href="{{ route('admin.modulo', 'reportes') }}?rango={{ $dias }}" class="{{ $rango === $dias ? 'on' : '' }}">{{ $texto }}</a>
        @endforeach
    </div>
    <button class="btn btn-g" x-data @click="$dispatch('exportar-reporte')"><x-icon name="download" size="15" /> Exportar</button>
@endsection

@section('contenido')
@php($maximo = max(1, collect($serie)->max('monto')))
<div x-data="reportesPage(@js($serie))" @exportar-reporte.window="exportar()">

    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Ventas totales <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="trending" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($ventas, 2) }}</div>
            @if ($variacion === null)
                <div class="stat-ch neutral">Sin periodo anterior para comparar</div>
            @else
                <div class="stat-ch {{ $variacion >= 0 ? 'up' : 'dn' }}">{{ $variacion >= 0 ? '+' : '' }}{{ $variacion }}% vs. periodo anterior</div>
            @endif
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Pedidos <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="clipboard" size="14" /></span></div>
            <div class="stat-val">{{ $cantidad }}</div>
            <div class="stat-ch neutral">Sin contar cancelados</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Ticket promedio <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="tag" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($ticket, 2) }}</div>
            <div class="stat-ch neutral">Venta media por pedido</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Clientes únicos <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="users" size="14" /></span></div>
            <div class="stat-val">{{ $clientesUnicos }}</div>
            <div class="stat-ch neutral">Con al menos un pedido</div>
        </div>
    </div>

    <div class="panel fade-in" style="animation-delay:.18s;margin-bottom:14px;">
        <div class="panel-hd">
            <div>
                <div class="panel-ttl">{{ $rango === 90 ? 'Ventas por semana' : 'Ventas por día' }}</div>
                <div class="panel-sub">Últimos {{ $rango }} días</div>
            </div>
            <div style="margin-left:auto;font-size:12px;color:var(--fg3);" x-show="sel !== null" x-cloak>
                <span x-text="sel !== null ? serie[sel].detalle : ''"></span> ·
                <strong style="color:var(--fg);" x-text="sel !== null ? gh.soles(serie[sel].monto) : ''"></strong>
            </div>
        </div>

        @if ($ventas > 0)
            <div class="chart" @mouseleave="sel = null">
                @foreach ($serie as $i => $s)
                    <div class="chart-col" @mouseenter="sel = {{ $i }}" :class="{ 'on': sel === {{ $i }} }">
                        <div class="chart-bar" style="height:{{ max(1.5, round($s['monto'] * 100 / $maximo, 1)) }}%;animation-delay:{{ min($i * 30, 600) }}ms" title="{{ $s['detalle'] }}: S/ {{ number_format($s['monto'], 2) }}"></div>
                        <div class="chart-lbl">{{ $s['etiqueta'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="chart chart-off">
                @foreach ($serie as $s)
                    <div class="chart-col"><div class="chart-bar" style="height:2px;"></div><div class="chart-lbl">{{ $s['etiqueta'] }}</div></div>
                @endforeach
                <div class="chart-vacio">
                    <x-vacio icono="chart" titulo="Aún no hay ventas en este periodo" texto="El gráfico se completará con los pedidos registrados en los últimos {{ $rango }} días." />
                </div>
            </div>
        @endif
    </div>

    <div class="g-11">
        @foreach ([['Ventas por canal', 'globe', $porCanal, 'Aquí verás qué canal (web, WhatsApp, punto de venta) vende más.'], ['Ventas por método de pago', 'card', $porMetodo, 'Aquí verás cuánto entra por efectivo, Yape, Plin y tarjeta.']] as $j => [$ttl, $ico, $lista, $ayuda])
            <div class="panel fade-in" style="animation-delay:{{ .24 + $j * .05 }}s">
                <div class="panel-hd"><div class="panel-ttl">{{ $ttl }}</div></div>
                @forelse ($lista as $f)
                    <div class="kv" style="display:block;">
                        <div style="display:flex;justify-content:space-between;gap:10px;">
                            <span style="font-weight:600;">{{ $f['texto'] }}</span>
                            <span style="font-weight:700;font-variant-numeric:tabular-nums;">S/ {{ number_format($f['monto'], 2) }}</span>
                        </div>
                        <div style="font-size:11px;color:var(--fg3);">{{ $f['cantidad'] }} {{ $f['cantidad'] === 1 ? 'pedido' : 'pedidos' }} · {{ $ventas > 0 ? round($f['monto'] * 100 / $ventas) : 0 }}%</div>
                        <div class="hbar"><span style="width:{{ $ventas > 0 ? round($f['monto'] * 100 / $ventas) : 0 }}%"></span></div>
                    </div>
                @empty
                    <x-vacio :icono="$ico" titulo="Aún no hay datos registrados" :texto="$ayuda" />
                @endforelse
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .chart{position:relative;display:flex;align-items:flex-end;gap:6px;height:240px;padding:20px 16px 12px;background:linear-gradient(var(--border) 1px,transparent 1px) 0 20px/100% 52px;}
        .chart-col{flex:1;min-width:0;height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:6px;cursor:default;}
        .chart-bar{width:100%;max-width:42px;background:var(--acm);border-radius:5px 5px 0 0;transform-origin:bottom;animation:barUp .55s var(--ease) backwards;transition:background .15s;}
        .chart-col.on .chart-bar,.chart-col:hover .chart-bar{background:var(--accent);}
        .chart-lbl{font-size:10px;color:var(--fg3);white-space:nowrap;height:14px;}
        .chart-off .chart-bar{background:var(--border);animation:none;}
        .chart-vacio{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.82);}
        @keyframes barUp{from{transform:scaleY(0)}}
        @media (max-width:860px){ .chart{gap:2px;padding:16px 8px 10px;} .chart-lbl{font-size:9px;} }
    </style>
@endpush

@push('scripts')
<script>
    gh.ready(() => {
        Alpine.data('reportesPage', (serie) => ({
            serie, sel: null,
            exportar() {
                gh.excel('Reporte {{ $rango }} días', [['Periodo'], ['Ventas', 'moneda']], this.serie.map(s => [s.detalle, s.monto]));
            },
        }));
    });
</script>
@endpush
