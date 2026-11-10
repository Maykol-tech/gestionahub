@extends('layouts.app')

@section('titulo', 'Repartos')
@section('subtitulo', count($enCamino) . (count($enCamino) === 1 ? ' pedido en camino' : ' pedidos en camino'))

@section('acciones')
    <a href="{{ route('admin.pedidos') }}" class="btn btn-g"><x-icon name="clipboard" size="15" /> Ver tablero</a>
@endsection

@section('contenido')
<div x-data="{ buscar: '' }">
    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">En camino <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="truck" size="14" /></span></div>
            <div class="stat-val">{{ count($enCamino) }}</div>
            <div class="stat-ch neutral">Pedidos en ruta ahora</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Por cobrar en ruta <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="cash" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($porCobrarRuta, 2) }}</div>
            <div class="stat-ch neutral">Contra entrega</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Entregados hoy <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="check-circle" size="14" /></span></div>
            <div class="stat-val">{{ $entregadosHoy }}</div>
            <div class="stat-ch neutral">Desde las 00:00</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Repartidores <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="users" size="14" /></span></div>
            <div class="stat-val">{{ count($repartidores) }}</div>
            <div class="stat-ch neutral">{{ $hayRepartidores ? 'Registrados' : 'Aún sin registrar' }}</div>
        </div>
    </div>

    {{-- Izquierda: pedidos en camino y equipo. Derecha: mapa fijo al desplazar --}}
    <div class="rep-grid">
        <div class="rep-col">
            <div class="panel fade-in" style="animation-delay:.18s">
                <div class="panel-hd">
                    <div>
                        <div class="panel-ttl">Pedidos en camino</div>
                        <div class="panel-sub">Ordenados por hora de salida</div>
                    </div>
                    @if (count($enCamino))
                        <div class="search" style="margin-left:auto;">
                            <x-icon name="search" size="15" />
                            <input class="inp" placeholder="Buscar pedido o repartidor…" x-model="buscar">
                        </div>
                    @endif
                </div>
                @if (count($enCamino))
                    <div class="tbl-wrap">
                        <table class="tbl">
                            <thead><tr><th>Pedido</th><th>Cliente y destino</th><th>Repartidor</th><th class="num">Monto</th><th>Pago</th></tr></thead>
                            <tbody>
                                @foreach ($enCamino as $i => $p)
                                    <tr class="row-in" style="--d:{{ min($i * 50, 500) }}ms"
                                        x-show="!buscar || gh.norm(@js($p['numero'] . ' ' . $p['cliente'] . ' ' . ($p['repartidor'] ?? ''))).includes(gh.norm(buscar))">
                                        <td>
                                            <div style="font-weight:700;">#{{ $p['numero'] }}</div>
                                            <div style="font-size:11px;color:var(--fg3);">Salió {{ $p['salida'] }}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;">{{ $p['cliente'] }}</div>
                                            <div style="font-size:11px;color:var(--fg3);">{{ $p['direccion'] ?: ($p['distrito'] ?: 'Sin dirección registrada') }}</div>
                                        </td>
                                        <td>
                                            @if ($p['repartidor'])
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <span class="rav">{{ mb_strtoupper(mb_substr($p['repartidor'], 0, 1)) }}</span>
                                                    <span>{{ $p['repartidor'] }}</span>
                                                </div>
                                            @else
                                                <span class="badge b-n">Sin asignar</span>
                                            @endif
                                        </td>
                                        <td class="num" style="font-weight:700;">S/ {{ number_format($p['total'], 2) }}</td>
                                        <td><span class="badge {{ $p['pagado'] ? 'b-ok' : 'b-a' }}"><span class="dot"></span>{{ $p['pagado'] ? 'Pagado' : 'Cobrar' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-vacio icono="truck" titulo="No hay pedidos en camino"
                             texto="Cuando muevas un pedido a «En camino» en el tablero, aparecerá aquí con su repartidor.">
                        <a href="{{ route('admin.pedidos') }}" class="btn btn-g btn-sm">Ir al tablero de pedidos</a>
                    </x-vacio>
                @endif
            </div>

            <div class="panel fade-in" style="animation-delay:.24s">
                <div class="panel-hd"><div class="panel-ttl">Repartidores</div></div>
                @forelse ($repartidores as $r)
                    <div class="kv">
                        <span class="rav">{{ mb_strtoupper(mb_substr($r['nombre'], 0, 1)) }}</span>
                        <div style="min-width:0;">
                            <div style="font-weight:600;">{{ $r['nombre'] }}</div>
                            <div style="font-size:11px;color:var(--fg3);">{{ $r['telefono'] ?: 'Sin teléfono' }}</div>
                        </div>
                        <span class="kv-v"><span class="badge {{ $r['en_ruta'] ? 'b-g' : 'b-n' }}">{{ $r['en_ruta'] ? $r['en_ruta'] . ' en ruta' : 'Disponible' }}</span></span>
                    </div>
                @empty
                    <x-vacio icono="users" titulo="Aún no hay repartidores registrados" texto="Registra a tu equipo de reparto para asignarle pedidos y ver sus rutas." />
                @endforelse
            </div>
        </div>

        <div class="rep-lado">
            {{-- GEST-24: mapa en vivo de repartidores --}}
            @include('admin.partials.mapa_repartidores', ['repartidores' => $repartidores])
        </div>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .rep-grid{display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:16px;align-items:start;margin-top:16px;}
        .rep-col{display:flex;flex-direction:column;gap:16px;min-width:0;}
        .rep-lado{position:sticky;top:72px;}
        .mapa-rep-box{position:relative;}
        .mapa-rep-el{height:440px;width:100%;background:var(--surface3);}
        .mapa-rep-aviso{position:absolute;left:12px;right:12px;bottom:12px;z-index:500;background:rgba(255,255,255,.94);border:1px solid var(--border);border-radius:8px;padding:9px 12px;font-size:12px;color:var(--fg2);text-align:center;box-shadow:var(--shadow);}
        @media (max-width:1200px){ .rep-grid{grid-template-columns:minmax(0,1fr) 340px;} }
        @media (max-width:1000px){ .rep-grid{grid-template-columns:1fr;} .rep-lado{position:static;order:-1;} .mapa-rep-el{height:300px;} }
        .rav{width:28px;height:28px;border-radius:50%;background:var(--acl);color:var(--accent);font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;}
    </style>
@endpush
