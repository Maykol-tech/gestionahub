@extends('layouts.app')

@section('titulo', 'Carritos abandonados')
@section('subtitulo', 'Clientes que dejaron su pedido a medias en tu tienda online')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .cr-grid { display:grid; grid-template-columns: 1fr 360px; gap:16px; align-items:start; }
        .chip-e { font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; white-space:nowrap; }
        .chip-e.pendiente { background:var(--ambl); color:#92400E; } .chip-e.recordado { background:var(--acl); color:var(--accent); } .chip-e.recuperado { background:var(--greenl); color:var(--green); }
        .filtros { display:flex; gap:6px; flex-wrap:wrap; }
        .filtros a { border:1px solid var(--bd); border-radius:999px; padding:5px 12px; font-size:13px; text-decoration:none; color:var(--fg2); }
        .filtros a.on { background:var(--accent); border-color:var(--accent); color:#fff; }
        @media (max-width: 1000px) { .cr-grid { grid-template-columns:1fr; } }
    </style>
@endpush

@section('contenido')
<div>
    @if (session('ok'))<div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#15803D;font-weight:600;">{{ session('ok') }}</div>@endif
    @if ($errors->any())<div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#B91C1C;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Carritos (30 días)</div><div class="stat-val">{{ $resumen['total'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Recordados</div><div class="stat-val">{{ $resumen['recordados'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Recuperados</div><div class="stat-val" style="color:var(--green);">{{ $resumen['recuperados'] }}</div><div style="font-size:12px;color:var(--fg3);">{{ $resumen['tasa'] }}% de los recordados</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Ventas recuperadas</div><div class="stat-val">S/ {{ number_format($resumen['monto_recuperado'], 2) }}</div><div style="font-size:12px;color:var(--fg3);">S/ {{ number_format($resumen['monto_pendiente'], 2) }} por recuperar</div></div>
    </div>

    <div class="cr-grid">
        <div class="panel fade-in" style="padding:0;">
            <div class="panel-hd">
                <div class="filtros">
                    @foreach (['todos' => 'Todos', 'pendientes' => 'Pendientes', 'recordados' => 'Recordados', 'recuperados' => 'Recuperados'] as $k => $v)
                        <a href="{{ route('admin.carritos', ['estado' => $k]) }}" class="{{ $filtro === $k ? 'on' : '' }}">{{ $v }}</a>
                    @endforeach
                </div>
            </div>
            @if ($lista->isNotEmpty())
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead><tr><th>Cliente</th><th>Carrito</th><th class="num">Total</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($lista as $i => $c)
                                <tr class="row-in" style="--d:{{ $i * 25 }}ms">
                                    <td><div style="font-weight:600;">{{ $c['nombre'] }}</div><div style="font-size:11px;color:var(--fg3);">{{ $c['telefono'] }} · {{ $c['fecha'] }}</div></td>
                                    <td style="max-width:260px;"><div style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $c['detalle'] }}">{{ $c['detalle'] ?: $c['productos'] . ' productos' }}</div></td>
                                    <td class="num">S/ {{ number_format($c['total'], 2) }}</td>
                                    <td><span class="chip-e {{ $c['estado'] }}">{{ ucfirst($c['estado']) }}</span>@if ($c['abierto'] && $c['estado'] !== 'recuperado')<div style="font-size:11px;color:var(--fg3);margin-top:3px;">Abrió el enlace</div>@endif</td>
                                    <td style="text-align:right;">
                                        @if ($c['estado'] === 'pendiente' && $c['wa'])
                                            <form method="POST" action="{{ route('admin.carritos.recordado', $c['id']) }}" style="display:inline;" onsubmit="window.open('{{ $c['wa'] }}', '_blank')">
                                                @csrf <button class="btn btn-wa btn-sm" type="submit">Recordar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty">
                    <x-icon name="clipboard" size="34" />
                    <div style="font-weight:600;color:var(--fg2);">No hay carritos en esta vista</div>
                    <div style="font-size:13px;color:var(--fg3);max-width:420px;text-align:center;">Aparecen cuando un cliente escribe su teléfono en el checkout de tu tienda y no termina el pedido.</div>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.carritos.guardar') }}" class="panel fade-in" style="padding:18px;">
            @csrf
            <div style="font-weight:700;margin-bottom:10px;">Recordatorio automático</div>
            <label style="display:flex;gap:8px;align-items:center;font-weight:600;margin-bottom:12px;">
                <input type="hidden" name="carrito_activo" value="0"><input type="checkbox" name="carrito_activo" value="1" @checked($cfg['activo'])> Enviar WhatsApp automático
            </label>
            <div class="field"><label class="lbl" for="cr-min">Esperar (minutos)</label><input class="inp" id="cr-min" type="number" min="10" max="1440" name="carrito_minutos" value="{{ old('carrito_minutos', $cfg['minutos']) }}" required></div>
            <div class="field"><label class="lbl" for="cr-cupon">Cupón (opcional)</label><input class="inp" id="cr-cupon" name="carrito_cupon" maxlength="40" value="{{ old('carrito_cupon', $cfg['cupon']) }}" placeholder="VUELVE5"></div>
            <div class="field"><label class="lbl" for="cr-msg">Mensaje · usa {nombre}, {total}, {negocio}, {enlace} y {cupon}</label><textarea class="inp" id="cr-msg" name="carrito_mensaje" rows="4" maxlength="500" required>{{ old('carrito_mensaje', $cfg['mensaje']) }}</textarea></div>
            <div style="font-size:12px;color:var(--fg3);margin-bottom:10px;">Se envía un solo recordatorio por carrito, desde tu número de WhatsApp conectado.@unless ($tieneTienda) Activa tu tienda online para usarlo.@endunless</div>
            <button class="btn btn-p" type="submit" style="width:100%;">Guardar</button>
        </form>
    </div>
</div>
@endsection
