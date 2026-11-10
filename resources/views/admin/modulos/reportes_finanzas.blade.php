@extends('layouts.app')

@section('titulo', 'Reportes y finanzas')
@section('subtitulo', 'Delivery, rentabilidad y comparativo de ventas')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; }
        .tabs a { font-size:12px; font-weight:700; padding:6px 14px; border-radius:99px; border:1px solid var(--border); color:var(--fg2); text-decoration:none; }
        .tabs a.activo { background:var(--brand); color:#fff; border-color:var(--brand); }
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
    <div class="tabs">
        <a class="{{ $tipo === 'comparativo' ? 'activo' : '' }}" href="{{ route('admin.reportes.finanzas', ['tipo' => 'comparativo']) }}">Comparativo del mes</a>
        <a class="{{ $tipo === 'rentabilidad' ? 'activo' : '' }}" href="{{ route('admin.reportes.finanzas', ['tipo' => 'rentabilidad']) }}">Rentabilidad mensual</a>
        <a class="{{ $tipo === 'delivery' ? 'activo' : '' }}" href="{{ route('admin.reportes.finanzas', ['tipo' => 'delivery']) }}">Tiempos de delivery</a>
    </div>

    @if ($tipo === 'comparativo')
        <div class="kpis">
            <div class="panel kpi fade-in"><div class="n">S/ {{ number_format($datos['actual'], 2) }}</div><div class="l">Mes actual ({{ $datos['periodo_actual'] }})</div></div>
            <div class="panel kpi fade-in"><div class="n">S/ {{ number_format($datos['anterior'], 2) }}</div><div class="l">Mes anterior ({{ $datos['periodo_anterior'] }})</div></div>
            <div class="panel kpi fade-in">
                <div class="n {{ ($datos['variacion'] ?? 0) >= 0 ? 'pos' : 'neg' }}">{{ $datos['variacion'] === null ? '”' : ($datos['variacion'] > 0 ? '+' : '') . $datos['variacion'] . '%' }}</div>
                <div class="l">Variación frente al mes anterior</div>
            </div>
        </div>
        <div class="panel" style="padding:12px;font-size:12px;color:var(--fg3);">Ingresos = pedidos pagados + ventas del POS.</div>

    @elseif ($tipo === 'rentabilidad')
        <div class="panel fade-in" style="padding:0;">
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th class="num">Ingresos</th>
                            <th class="num">Gastos</th>
                            <th class="num">Utilidad</th>
                            <th class="num">Margen</th>
                            <th style="width:30%;">Ingresos vs gastos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $maximo = max(array_column($datos, 'ingresos')) ?: 1; @endphp
                        @foreach ($datos as $f)
                            <tr>
                                <td>{{ $f['mes'] }}</td>
                                <td class="num">S/ {{ number_format($f['ingresos'], 2) }}</td>
                                <td class="num">S/ {{ number_format($f['gastos'], 2) }}</td>
                                <td class="num {{ $f['utilidad'] >= 0 ? 'pos' : 'neg' }}">S/ {{ number_format($f['utilidad'], 2) }}</td>
                                <td class="num">{{ $f['margen'] === null ? '”' : $f['margen'] . '%' }}</td>
                                <td>
                                    <div class="barra-bg"><div class="barra" style="width: {{ round($f['ingresos'] / $maximo * 100) }}%"></div></div>
                                    <div class="barra-bg" style="margin-top:4px;"><div class="barra" style="width: {{ round($f['gastos'] / $maximo * 100) }}%;background:#F59E0B;"></div></div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
            <div class="panel kpi fade-in"><div class="n">{{ $datos['asignado_a_entrega'] ?? '”' }} min</div><div class="l">Promedio desde asignación</div></div>
            <div class="panel kpi fade-in"><div class="n">{{ $datos['confirmado_a_entrega'] ?? '”' }} min</div><div class="l">Promedio desde confirmación</div></div>
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
                                <td class="num">{{ $r['asignado_a_entrega'] ?? '”' }}</td>
                                <td class="num">{{ $r['confirmado_a_entrega'] ?? '”' }}</td>
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
