@extends('layouts.app')

@section('titulo', 'Clientes frecuentes')
@section('subtitulo', 'Quiénes compran más y con qué frecuencia')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .rk-filtros { display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; margin-bottom:12px; }
        .rk-filtros .field { margin:0; min-width:140px; }
        .puesto { font-weight:800; color:var(--fg3); font-variant-numeric: tabular-nums; }
        .puesto.top1 { color:#CA8A04; } .puesto.top2 { color:#64748B; } .puesto.top3 { color:#B45309; }
        .nivel-chip { display:inline-flex; font-size:11px; font-weight:700; padding:2px 9px; border-radius:99px; }
        .barra { height:6px; border-radius:99px; background:var(--acl); overflow:hidden; margin-top:4px; }
        .barra i { display:block; height:100%; background:var(--accent); border-radius:99px; transition:width .6s var(--ease, ease); }
    </style>
@endpush

@section('contenido')
@php $max = max(1, $filtros['orden'] === 'pedidos' ? (int) $filas->max('pedidos') : (float) $filas->max('gastado')); @endphp
<div>
    <form class="rk-filtros panel fade-in" style="padding:14px;" method="GET" action="{{ route('admin.clientes.ranking') }}">
        <div class="field">
            <label class="lbl" for="rk-top">Mostrar</label>
            <select class="inp" id="rk-top" name="top" onchange="this.form.submit()">
                @foreach ([10, 20, 50] as $n)<option value="{{ $n }}" @selected($filtros['top'] === $n)>Top {{ $n }}</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label class="lbl" for="rk-orden">Ordenar por</label>
            <select class="inp" id="rk-orden" name="orden" onchange="this.form.submit()">
                <option value="gastado" @selected($filtros['orden'] === 'gastado')>Total comprado</option>
                <option value="pedidos" @selected($filtros['orden'] === 'pedidos')>Número de compras</option>
            </select>
        </div>
        <div class="field">
            <label class="lbl" for="rk-periodo">Periodo</label>
            <select class="inp" id="rk-periodo" name="periodo" onchange="this.form.submit()">
                @foreach ($periodos as $k => $v)<option value="{{ $k }}" @selected($filtros['periodo'] === (string) $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label class="lbl" for="rk-nivel">Nivel</label>
            <select class="inp" id="rk-nivel" name="nivel" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($niveles as $k => $n)<option value="{{ $k }}" @selected($filtros['nivel'] === $k)>{{ $n['nombre'] }}</option>@endforeach
            </select>
        </div>
        <div style="margin-left:auto;display:flex;gap:8px;">
            <a class="btn btn-g" href="{{ route('admin.clientes.ranking', $filtros + ['exportar' => 1]) }}">Exportar ranking (Excel)</a>
            <a class="btn btn-g" href="{{ route('admin.clientes.exportar') }}">Todos los clientes</a>
        </div>
    </form>

    <div class="panel fade-in" style="padding:0;">
        <div class="panel-hd">
            <span style="font-weight:700;">Top {{ $filtros['top'] }} por {{ $filtros['orden'] === 'pedidos' ? 'número de compras' : 'total comprado' }} · {{ $periodos[$filtros['periodo']] }}</span>
            <span style="font-size:12px;color:var(--fg3);margin-left:auto;">Incluye pedidos pagados y ventas del punto de venta</span>
        </div>
        @if ($filas->isNotEmpty())
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Cliente</th>
                            <th>Nivel</th>
                            <th class="num">Compras</th>
                            <th class="num">Total comprado</th>
                            <th class="num">Ticket prom.</th>
                            <th class="num">Puntos</th>
                            <th>Última compra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filas as $i => $f)
                            @php $valor = $filtros['orden'] === 'pedidos' ? $f['pedidos'] : $f['gastado']; @endphp
                            <tr class="row-in" style="--d:{{ $i * 30 }}ms">
                                <td class="puesto {{ $i < 3 ? 'top' . ($i + 1) : '' }}">{{ $i + 1 }}</td>
                                <td>
                                    <div style="font-weight:600;">{{ $f['nombre'] }}</div>
                                    <div style="font-size:11px;color:var(--fg3);">{{ $f['telefono'] ?: 'Sin teléfono' }}</div>
                                    <div class="barra"><i style="width:{{ round($valor / $max * 100) }}%"></i></div>
                                </td>
                                <td><span class="nivel-chip" style="background:{{ $f['nivel_fondo'] }};color:{{ $f['nivel_color'] }}">{{ $f['nivel_nombre'] }}</span></td>
                                <td class="num">{{ $f['pedidos'] }}</td>
                                <td class="num">S/ {{ number_format($f['gastado'], 2) }}</td>
                                <td class="num">S/ {{ number_format($f['ticket'], 2) }}</td>
                                <td class="num">{{ number_format($f['puntos']) }}</td>
                                <td>{{ $f['ultimo'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">
                <x-icon name="users" size="34" />
                <div style="font-weight:600;color:var(--fg2);">No hay compras de clientes en este periodo</div>
                <div style="font-size:13px;color:var(--fg3);">Aparecen cuando un pedido pagado o una venta del punto de venta tiene cliente. Prueba con otro periodo o nivel.</div>
            </div>
        @endif
    </div>
</div>
@endsection
