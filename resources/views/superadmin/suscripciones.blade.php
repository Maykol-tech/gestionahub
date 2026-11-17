@extends('layouts.app')

@section('titulo', 'Pagos de suscripción')
@section('subtitulo', 'Revisa las capturas de Yape, Plin y transferencias y activa los planes')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .sp-grid { display:grid; grid-template-columns:minmax(0, 1fr) 320px; gap:16px; align-items:start; }
        .filtros { display:flex; gap:6px; flex-wrap:wrap; }
        .filtros a { border:1px solid var(--border); border-radius:999px; padding:5px 12px; font-size:13px; text-decoration:none; color:var(--fg2); }
        .filtros a.on { background:var(--accent); border-color:var(--accent); color:#fff; }
        .pago { display:grid; grid-template-columns:92px 1fr auto; gap:14px; padding:14px 16px; border-bottom:1px solid var(--border); align-items:center; animation:subirP .3s ease both; }
        .pago:last-child { border-bottom:0; }
        .thumb { width:92px; height:92px; border-radius:10px; border:1px solid var(--border); background:var(--surface2) center/cover no-repeat; cursor:zoom-in; display:grid; place-items:center; color:var(--fg3); font-size:24px; }
        .chip-e { font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; white-space:nowrap; }
        .chip-e.pendiente { background:var(--ambl); color:#92400E; } .chip-e.pagado { background:var(--greenl); color:var(--green); } .chip-e.fallido { background:#FEE2E2; color:#B91C1C; }
        .zoom { position:fixed; inset:0; background:rgba(15,23,42,.8); display:grid; place-items:center; z-index:95; padding:20px; cursor:zoom-out; }
        .zoom img { max-width:min(560px, 100%); max-height:90vh; border-radius:12px; animation:subirP .25s ease both; }
        @keyframes subirP { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
        @media (max-width: 1000px) { .sp-grid { grid-template-columns:1fr; } }
        @media (max-width: 600px) { .pago { grid-template-columns:64px 1fr; } .pago .acc { grid-column:1 / -1; } .thumb { width:64px; height:64px; } }
    </style>
@endpush

@section('contenido')
<div x-data="{ zoom: null, rechazando: null }" @keydown.escape.window="zoom = null; rechazando = null">
    @if (session('ok'))<div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#15803D;font-weight:600;">{{ session('ok') }}</div>@endif
    @if (session('error'))<div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#B91C1C;">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#B91C1C;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Por revisar</div><div class="stat-val" style="{{ $conteo['pendientes'] ? 'color:#B45309' : '' }}">{{ $conteo['pendientes'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Cobrado este mes</div><div class="stat-val">S/ {{ number_format($conteo['mes'], 2) }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Suscripciones activas</div><div class="stat-val">{{ $conteo['activas'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Checkouts sin terminar (30 días)</div><div class="stat-val">{{ $conteo['abandonados'] }}</div><div style="font-size:12px;"><a href="{{ route('superadmin.leads', ['fuente' => 'checkout']) }}">Ver en leads</a></div></div>
    </div>

    <div class="sp-grid">
        <div class="panel fade-in" style="padding:0;">
            <div class="panel-hd">
                <div class="filtros">
                    @foreach (['pendientes' => 'Por revisar', 'pagados' => 'Aprobados', 'rechazados' => 'Rechazados', 'todos' => 'Todos'] as $k => $v)
                        <a href="{{ route('superadmin.suscripciones', ['ver' => $k]) }}" class="{{ $vista === $k ? 'on' : '' }}">{{ $v }}</a>
                    @endforeach
                </div>
            </div>
            @forelse ($pagos as $i => $p)
                <div class="pago" style="animation-delay:{{ $i * 30 }}ms">
                    @if ($p['captura'])
                        <div class="thumb" style="background-image:url('{{ $p['captura'] }}')" @click="zoom = @js($p['captura'])" role="button" aria-label="Ver captura"></div>
                    @else
                        <div class="thumb">{{ $p['metodo'] === 'Tarjeta' ? '💳' : '🧾' }}</div>
                    @endif
                    <div style="min-width:0;">
                        <div style="font-weight:700;">{{ $p['negocio'] }} <span class="chip-e {{ $p['estado'] }}">{{ ['pendiente' => 'Por revisar', 'pagado' => 'Aprobado', 'fallido' => 'Rechazado'][$p['estado']] ?? $p['estado'] }}</span></div>
                        <div style="font-size:13px;color:var(--fg2);">{{ $p['plan'] }} · <b>S/ {{ number_format($p['monto'], 2) }}</b> · {{ $p['metodo'] }}</div>
                        <div style="font-size:12px;color:var(--fg3);">Operación <b style="color:var(--fg);">{{ $p['operacion'] }}</b> · {{ $p['hace'] }} ({{ $p['fecha'] }})</div>
                        @if ($p['motivo'])<div style="font-size:12px;color:#B91C1C;">Motivo: {{ $p['motivo'] }}</div>@endif
                    </div>
                    <div class="acc" style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                        @if ($p['estado'] === 'pendiente')
                            <form method="POST" action="{{ route('superadmin.suscripciones.aprobar', $p['id']) }}" onsubmit="return confirm('¿Confirmas que recibiste S/ {{ number_format($p['monto'], 2) }}? Se activará el plan.')">@csrf<button class="btn btn-p btn-sm" type="submit">Aprobar</button></form>
                            <button class="btn btn-g btn-sm" type="button" @click="rechazando = {{ $p['id'] }}">Rechazar</button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty" style="padding:40px 16px;text-align:center;">
                    <x-icon name="check-circle" size="34" />
                    <div style="font-weight:600;color:var(--fg2);margin-top:6px;">{{ $vista === 'pendientes' ? 'No hay pagos por revisar' : 'No hay pagos en esta vista' }}</div>
                    <div style="font-size:13px;color:var(--fg3);">Cuando un negocio pague con Yape, Plin o transferencia, su captura aparece aquí.</div>
                </div>
            @endforelse

            @if ($legado->isNotEmpty())
                <div class="panel-hd" style="border-top:1px solid var(--border);"><div class="panel-ttl">Pagos reportados con el sistema anterior</div></div>
                @foreach ($legado as $p)
                    <div class="pago" style="grid-template-columns:1fr auto;">
                        <div><b>{{ $p['negocio'] }}</b> · {{ $p['plan'] }} · S/ {{ number_format($p['monto'], 2) }} · {{ $p['metodo'] }} · Ref. {{ $p['referencia'] }} <span style="color:var(--fg3);font-size:12px;">{{ $p['fecha'] }}</span></div>
                        <div style="display:flex;gap:6px;">
                            <form method="POST" action="{{ route('superadmin.suscripciones.confirmar', $p['id']) }}">@csrf<button class="btn btn-p btn-sm" type="submit">Confirmar</button></form>
                            <form method="POST" action="{{ route('superadmin.suscripciones.rechazar', $p['id']) }}">@csrf<button class="btn btn-g btn-sm" type="submit">Rechazar</button></form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <form class="panel fade-in" method="POST" action="{{ route('superadmin.suscripciones.cobros') }}" style="padding:16px;animation-delay:.08s">
            @csrf
            <div class="panel-ttl" style="margin-bottom:4px;">¿Dónde te pagan?</div>
            <p style="font-size:12px;color:var(--fg3);margin-top:0;">Estos datos ven los negocios al pagar su plan.</p>
            <label class="lbl">Yape</label><input class="inp" name="yape" value="{{ old('yape', $cobros['yape']) }}" maxlength="9" placeholder="987654321">
            <label class="lbl">Plin</label><input class="inp" name="plin" value="{{ old('plin', $cobros['plin']) }}" maxlength="9" placeholder="987654321">
            <label class="lbl">Titular</label><input class="inp" name="titular" value="{{ old('titular', $cobros['titular']) }}" maxlength="120" required>
            <label class="lbl">Banco</label><input class="inp" name="banco" value="{{ old('banco', $cobros['banco']) }}" maxlength="60" placeholder="BCP, Interbank, BBVA…">
            <label class="lbl">Número de cuenta</label><input class="inp" name="cuenta" value="{{ old('cuenta', $cobros['cuenta']) }}" maxlength="30">
            <label class="lbl">CCI</label><input class="inp" name="cci" value="{{ old('cci', $cobros['cci']) }}" maxlength="20">
            <label class="lbl">Imagen del QR (enlace, opcional)</label><input class="inp" name="qr_url" value="{{ old('qr_url', $cobros['qr_url']) }}" maxlength="255">
            <button class="btn btn-p" type="submit" style="margin-top:12px;width:100%;justify-content:center;">Guardar datos de cobro</button>
        </form>
    </div>

    <template x-if="zoom"><div class="zoom" @click="zoom = null"><img :src="zoom" alt="Captura del pago"></div></template>
    <template x-if="rechazando">
        <div class="zoom" style="cursor:default;" @click.self="rechazando = null">
            <form method="POST" :action="'{{ url('superadmin/suscripciones/pagos') }}/' + rechazando + '/rechazar'" class="panel" style="padding:18px;width:min(420px, 100%);">
                @csrf
                <div class="panel-ttl">¿Por qué rechazas este pago?</div>
                <p style="font-size:12px;color:var(--fg3);">El negocio recibe este mensaje y puede volver a reportarlo.</p>
                <select class="inp" onchange="if (this.value) this.form.motivo.value = this.value">
                    <option value="">Elegir un motivo rápido…</option>
                    <option>No encontramos el pago en nuestra cuenta</option>
                    <option>El monto no coincide con el plan</option>
                    <option>La captura no se ve bien</option>
                </select>
                <textarea class="inp" name="motivo" rows="3" maxlength="200" required style="margin-top:8px;" placeholder="Motivo"></textarea>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
                    <button class="btn btn-g btn-sm" type="button" @click="rechazando = null">Cancelar</button>
                    <button class="btn btn-p btn-sm" type="submit" style="background:#B91C1C;border-color:#B91C1C;">Rechazar pago</button>
                </div>
            </form>
        </div>
    </template>
</div>
@endsection
