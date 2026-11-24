@extends('layouts.app')

@section('titulo', 'Repartos')
@section('subtitulo', count($enCamino) . (count($enCamino) === 1 ? ' pedido en camino' : ' pedidos en camino'))

@section('acciones')
    <a href="{{ route('admin.pedidos') }}" class="btn btn-g"><x-icon name="clipboard" size="15" /> Ver tablero</a>
@endsection

@section('contenido')
<div x-data="{ buscar: '', editZona: null }">
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif
    <x-ia modulo="repartos" />
    @php
        $pendHoy = $entregasHoy->where('grupo', '!=', 'entregados');
    @endphp
    <div class="rep-kpis">
        <div class="rk fade-in"><b>{{ $entregasHoy->count() }}</b><span>Pedidos totales</span></div>
        <div class="rk fade-in" style="animation-delay:.04s"><b style="color:var(--green)">{{ $entregasHoy->where('grupo', 'entregados')->count() }}</b><span>Entregados</span></div>
        <div class="rk fade-in" style="animation-delay:.08s"><b style="color:var(--cta, #F47B20)">{{ $pendHoy->count() }}</b><span>Pendientes · {{ count($enCamino) }} en camino</span></div>
        <div class="rk fade-in" style="animation-delay:.12s"><b>S/ {{ number_format($entregasHoy->sum('total'), 2) }}</b><span>Total ruta</span></div>
        <div class="rk fade-in" style="animation-delay:.16s"><b style="color:var(--accent)">S/ {{ number_format($pendHoy->where('pagado', false)->sum('total'), 2) }}</b><span>Por cobrar</span></div>
    </div>

    {{-- Entregas del día con filtros (prototipo) --}}
    <div class="panel fade-in" style="margin-bottom:16px;animation-delay:.18s" x-data="{ filtro: 'todos' }">
        <div class="panel-hd" style="flex-wrap:wrap;gap:8px;">
            <div class="panel-ttl">Entregas de hoy</div>
            <div class="rep-filtros">
                @foreach (['todos' => 'Todos', 'pendientes' => 'Pendientes', 'en_camino' => 'En camino', 'entregados' => 'Entregados'] as $k => $t)
                    <button type="button" :class="{ on: filtro === '{{ $k }}' }" @click="filtro = '{{ $k }}'">{{ $t }} ({{ $k === 'todos' ? $entregasHoy->count() : $entregasHoy->where('grupo', $k)->count() }})</button>
                @endforeach
            </div>
        </div>
        @forelse ($entregasHoy as $i => $e)
            <div class="ent-fila {{ $e['grupo'] }}" x-show="filtro === 'todos' || filtro === '{{ $e['grupo'] }}'">
                <span class="ent-n">{{ $i + 1 }}</span>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;">{{ $e['cliente'] }} @if ($e['programado'])<span class="ent-urg">Entregar {{ $e['programado'] }}</span>@endif</div>
                    <div style="font-size:12px;color:var(--fg3);">{{ $e['direccion'] ?: 'Sin dirección' }}@if ($e['telefono']) · {{ $e['telefono'] }}@endif · #{{ $e['numero'] }}{{ $e['repartidor'] ? ' · ' . $e['repartidor'] : ' · sin repartidor' }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:800;">S/ {{ number_format($e['total'], 2) }}</div>
                    <span class="ent-est">{{ ['entregados' => 'Entregado', 'en_camino' => 'En camino', 'pendientes' => 'Pendiente'][$e['grupo']] }}{{ $e['pagado'] ? ' · pagado' : '' }}</span>
                </div>
            </div>
        @empty
            <x-vacio icono="truck" titulo="Sin entregas hoy" texto="Los pedidos con delivery de hoy aparecerán aquí en el orden de la ruta." />
        @endforelse
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
                <div style="padding:10px 16px;"><a href="{{ route('admin.repartidores.crud.index') }}" class="btn btn-g btn-sm">Gestionar repartidores</a></div>
            </div>

            {{-- GEST-25: zonas de delivery con su costo (las usa la tienda online) --}}
            <div class="panel fade-in" id="zonas" style="animation-delay:.3s">
                <div class="panel-hd">
                    <div><div class="panel-ttl">Zonas de delivery</div><div class="panel-sub">El cliente elige su zona en la tienda y se cobra ese costo de envío.</div></div>
                </div>
                @foreach ($zonas as $z)
                    <div class="kv zona {{ $z->activo ? '' : 'off' }}">
                        <span class="zona-color" style="background:{{ $z->color }}"></span>
                        <div style="min-width:0;flex:1;" x-show="editZona !== {{ $z->id }}">
                            <div style="font-weight:600;">{{ $z->nombre }}</div>
                            <div style="font-size:11px;color:var(--fg3);">S/ {{ number_format((float) $z->costo, 2) }}{{ $z->tiempo_min ? ' · ' . $z->tiempo_min . ' min' : '' }}{{ $z->activo ? '' : ' · Pausada' }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.zonas.actualizar', $z->id) }}" class="zona-form" x-show="editZona === {{ $z->id }}" x-cloak>
                            @csrf @method('PUT')
                            <input class="inp inp-sm" name="nombre" value="{{ $z->nombre }}" maxlength="80" required aria-label="Nombre">
                            <input class="inp inp-sm" name="costo" type="number" step="0.01" min="0" value="{{ (float) $z->costo }}" required aria-label="Costo" style="width:90px;">
                            <input class="inp inp-sm" name="tiempo_min" type="number" min="1" value="{{ $z->tiempo_min }}" placeholder="min" aria-label="Minutos" style="width:70px;">
                            <input type="color" name="color" value="{{ $z->color }}" aria-label="Color">
                            <button class="btn btn-p btn-sm" type="submit">Guardar</button>
                        </form>
                        <span class="kv-v" style="display:flex;gap:4px;">
                            <button type="button" class="btn btn-g btn-sm" @click="editZona = editZona === {{ $z->id }} ? null : {{ $z->id }}" x-text="editZona === {{ $z->id }} ? 'Cerrar' : 'Editar'"></button>
                            <form method="POST" action="{{ route('admin.zonas.actualizar', $z->id) }}">@csrf @method('PUT')<input type="hidden" name="alternar" value="1"><button class="btn btn-g btn-sm" type="submit">{{ $z->activo ? 'Pausar' : 'Activar' }}</button></form>
                            <form method="POST" action="{{ route('admin.zonas.eliminar', $z->id) }}" onsubmit="return confirm('¿Eliminar la zona {{ $z->nombre }}?')">@csrf @method('DELETE')<button class="btn btn-g btn-sm" type="submit" style="color:#B91C1C;">Eliminar</button></form>
                        </span>
                    </div>
                @endforeach
                @if ($zonas->isEmpty())
                    <div style="padding:6px 16px 0;font-size:13px;color:var(--fg3);">Aún no tienes zonas. Agrega la primera, por ejemplo “Miraflores · S/ 7”.</div>
                @endif
                <form method="POST" action="{{ route('admin.zonas.guardar') }}" class="zona-nueva">
                    @csrf
                    <input class="inp" name="nombre" maxlength="80" placeholder="Nombre de la zona (ej. Surco)" required>
                    <input class="inp" name="costo" type="number" step="0.01" min="0" placeholder="Costo S/" required>
                    <input class="inp" name="tiempo_min" type="number" min="1" placeholder="Minutos">
                    <input type="color" name="color" value="#2B6EF6" aria-label="Color de la zona">
                    <button class="btn btn-p" type="submit">Agregar zona</button>
                </form>
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
        .rep-kpis { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:10px; margin-bottom:14px; }
        .rk { background:var(--surface, #fff); border:1px solid var(--border); border-radius:12px; padding:12px; text-align:center; }
        .rk b { display:block; font-size:22px; font-variant-numeric:tabular-nums; } .rk span { font-size:11px; color:var(--fg3); }
        .rep-filtros { display:flex; gap:6px; flex-wrap:wrap; margin-left:auto; }
        .rep-filtros button { border:1px solid var(--border); background:var(--surface, #fff); border-radius:99px; padding:5px 12px; font:inherit; font-size:12px; font-weight:600; color:var(--fg2); cursor:pointer; }
        .rep-filtros button.on { background:var(--accent); border-color:var(--accent); color:#fff; }
        .ent-fila { display:flex; gap:12px; align-items:center; padding:12px 16px; border-top:1px solid var(--border); }
        .ent-fila.entregados { background:#F0FDF4; } .ent-fila.en_camino { background:#FFF7ED; box-shadow:inset 3px 0 0 var(--cta, #F47B20); }
        .ent-n { width:26px; height:26px; border-radius:50%; display:grid; place-items:center; background:var(--acl, #EEF3FE); color:var(--accent); font-size:12px; font-weight:800; flex:none; }
        .ent-urg { font-size:11px; font-weight:700; color:#C2410C; margin-left:6px; }
        .ent-est { font-size:11px; color:var(--fg3); }
        @media (max-width: 900px) { .rep-kpis { grid-template-columns:repeat(2, minmax(0,1fr)); } }

        .rep-grid{display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:16px;align-items:start;margin-top:16px;}
        .rep-col{display:flex;flex-direction:column;gap:16px;min-width:0;}
        .rep-lado{position:sticky;top:72px;}
        .mapa-rep-box{position:relative;}
        .mapa-rep-el{height:440px;width:100%;background:var(--surface3);}
        .mapa-rep-aviso{position:absolute;left:12px;right:12px;bottom:12px;z-index:500;background:rgba(255,255,255,.94);border:1px solid var(--border);border-radius:8px;padding:9px 12px;font-size:12px;color:var(--fg2);text-align:center;box-shadow:var(--shadow);}
        @media (max-width:1200px){ .rep-grid{grid-template-columns:minmax(0,1fr) 340px;} }
        @media (max-width:1000px){ .rep-grid{grid-template-columns:1fr;} .rep-lado{position:static;order:-1;} .mapa-rep-el{height:300px;} }
        .zona-color{width:14px;height:14px;border-radius:4px;flex-shrink:0;}
        .zona.off{opacity:.55;}
        .zona-form{display:flex;gap:6px;flex-wrap:wrap;flex:1;align-items:center;}
        .zona-nueva{display:grid;grid-template-columns:1.6fr 1fr 1fr auto auto;gap:8px;padding:12px 16px 16px;align-items:center;border-top:1px dashed var(--border);margin-top:6px;}
        .zona-nueva input[type=color],.zona-form input[type=color]{width:38px;height:34px;padding:2px;border:1px solid var(--border);border-radius:8px;background:none;}
        @media (max-width:640px){ .zona-nueva{grid-template-columns:1fr 1fr;} }
        .rav{width:28px;height:28px;border-radius:50%;background:var(--acl);color:var(--accent);font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;}
    </style>
@endpush
