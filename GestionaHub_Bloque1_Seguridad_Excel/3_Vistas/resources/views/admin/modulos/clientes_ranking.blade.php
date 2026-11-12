@extends('layouts.app')

@section('titulo', 'Clientes frecuentes')
@section('subtitulo', 'Quiénes compran más y con qué frecuencia')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .acciones-top { display:flex; gap:8px; justify-content:flex-end; margin-bottom:12px; flex-wrap:wrap; }
        .puesto { font-weight:800; color:var(--fg3); font-variant-numeric: tabular-nums; }
    </style>
@endpush

@section('contenido')
<div>
    <div class="acciones-top">
        <a class="btn btn-g" href="{{ route('admin.clientes.exportar') }}">Exportar clientes (Excel)</a>
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="panel-hd"><span style="font-weight:700;">Top 20 por total comprado</span></div>
        <div class="tbl-wrap" @if ($filas->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Cliente</th>
                        <th class="num">Pedidos</th>
                        <th class="num">Total comprado</th>
                        <th>Última compra</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $i => $f)
                        <tr class="row-in" style="--d:{{ $i * 30 }}ms">
                            <td class="puesto">{{ $i + 1 }}</td>
                            <td>
                                <div style="font-weight:600;">{{ $f['nombre'] }}</div>
                                <div style="font-size:11px;color:var(--fg3);">{{ $f['telefono'] ?: 'Sin teléfono' }}</div>
                            </td>
                            <td class="num">{{ $f['pedidos'] }}</td>
                            <td class="num">S/ {{ number_format($f['gastado'], 2) }}</td>
                            <td>{{ $f['ultimo'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($filas->isEmpty())
            <div class="empty">
                <div style="font-weight:600;color:var(--fg2);">Aún no hay compras de clientes registrados</div>
                <div style="font-size:13px;color:var(--fg3);">Aparecerán cuando un pedido pagado tenga cliente.</div>
            </div>
        @endif
    </div>
</div>
@endsection
