@extends('layouts.app')

@section('titulo', 'Seguridad')
@section('subtitulo', 'Intentos de acceso, tiempo de bloqueo y bloqueo de pantalla por inactividad')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .seg-kpis { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:14px; }
        .seg-kpi { padding:14px 16px; }
        .seg-kpi .n { font-size:24px; font-weight:800; font-variant-numeric:tabular-nums; }
        .seg-kpi .t { font-size:12px; color:var(--fg3); }
        .regla { padding:16px 0; border-top:1px dashed var(--border); display:grid; grid-template-columns:minmax(0,1fr) 260px; gap:16px; align-items:center; }
        .regla:first-of-type { border-top:none; padding-top:4px; }
        .regla b { display:block; font-size:14px; }
        .regla .sub { font-size:12px; color:var(--fg3); }
        .contador { display:flex; align-items:center; gap:6px; }
        .contador button { width:36px; height:36px; border-radius:10px; border:1px solid var(--border); background:var(--surface2); font-size:18px; font-weight:700; cursor:pointer; color:var(--fg); transition:transform .12s; }
        .contador button:active { transform:scale(.92); }
        .contador input { width:70px; text-align:center; font-weight:700; }
        .puntos { display:flex; gap:4px; margin-top:8px; }
        .puntos i { width:16px; height:6px; border-radius:99px; background:var(--accent); transition:background .2s, transform .2s; }
        .puntos i.x { background:#DC2626; transform:scaleY(1.4); }
        .unidad { display:inline-flex; border:1px solid var(--border); border-radius:10px; overflow:hidden; }
        .unidad label { padding:8px 12px; font-size:13px; font-weight:600; cursor:pointer; color:var(--fg2); transition:background .15s, color .15s; }
        .unidad input { display:none; }
        .unidad label.on { background:var(--acl); color:var(--accent); }
        .switch { position:relative; width:46px; height:26px; flex:none; }
        .switch input { opacity:0; width:0; height:0; }
        .switch span { position:absolute; inset:0; background:#CBD5E1; border-radius:99px; transition:background .2s; cursor:pointer; }
        .switch span::after { content:''; position:absolute; left:3px; top:3px; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .2s var(--ease); box-shadow:0 1px 3px rgba(0,0,0,.25); }
        .switch input:checked + span { background:#16A34A; }
        .switch input:checked + span::after { transform:translateX(20px); }
        .resumen { font-size:13px; padding:12px 14px; border-radius:10px; background:var(--surface2); color:var(--fg2); margin-top:6px; }
        .hist { width:100%; border-collapse:collapse; font-size:13px; }
        .hist th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--fg3); padding:10px 14px; border-bottom:1px solid var(--border); }
        .hist td { padding:10px 14px; border-bottom:1px solid var(--border); vertical-align:top; }
        .hist tr:last-child td { border-bottom:none; }
        .res { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; white-space:nowrap; }
        .res-ingreso { background:#DCFCE7; color:#15803D; } .res-fallido { background:#FEF3C7; color:#B45309; }
        .res-bloqueado { background:#FEE2E2; color:#B91C1C; } .res-desbloqueado { background:#DBEAFE; color:#1D4ED8; } .res-salida { background:#E2E8F0; color:#475569; }
        .bloq { display:flex; align-items:center; gap:12px; padding:12px 16px; border-bottom:1px solid var(--border); }
        .bloq:last-child { border-bottom:none; }
        .bloq-ico { width:36px; height:36px; border-radius:10px; background:#FEE2E2; color:#B91C1C; display:grid; place-items:center; flex:none; animation:late 1.6s ease-in-out infinite; }
        @keyframes late { 50% { transform:scale(1.08); } }
        @media (max-width: 860px) { .seg-kpis { grid-template-columns:1fr 1fr; } .regla { grid-template-columns:1fr; } .hist .opc { display:none; } }
        @media (prefers-reduced-motion: reduce) { .bloq-ico { animation:none; } }
    </style>
@endpush

@section('contenido')
@php
    $etiquetas = ['ingreso' => 'Ingresó', 'fallido' => 'Contraseña incorrecta', 'bloqueado' => 'Bloqueado', 'desbloqueado' => 'Desbloqueado', 'salida' => 'Cerró sesión'];
    $navegador = function (?string $ua) {
        $ua = (string) $ua;
        if ($ua === '' ) return '—';
        if (str_starts_with($ua, 'Desbloqueado por')) return $ua;
        $so = str_contains($ua, 'Android') ? 'Android' : (preg_match('/iPhone|iPad/', $ua) ? 'iPhone' : (str_contains($ua, 'Windows') ? 'Windows' : (str_contains($ua, 'Mac') ? 'Mac' : (str_contains($ua, 'Linux') ? 'Linux' : 'Otro'))));
        $nav = str_contains($ua, 'Edg') ? 'Edge' : (str_contains($ua, 'Chrome') ? 'Chrome' : (str_contains($ua, 'Firefox') ? 'Firefox' : (str_contains($ua, 'Safari') ? 'Safari' : 'Navegador')));
        return $nav . ' en ' . $so;
    };
@endphp

@if (session('ok'))
    <div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#15803D;font-weight:600;">✓ {{ session('ok') }}</div>
@endif
@if ($errors->any())
    <div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#B91C1C;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<div class="seg-kpis">
    <div class="panel seg-kpi fade-in"><div class="n">{{ $reglas['intentos'] }}</div><div class="t">intentos antes de bloquear</div></div>
    <div class="panel seg-kpi fade-in" style="animation-delay:.04s"><div class="n">{{ \App\Support\Seguridad::textoDuracion($reglas['bloqueo_minutos']) }}</div><div class="t">dura el bloqueo</div></div>
    <div class="panel seg-kpi fade-in" style="animation-delay:.08s"><div class="n">{{ $ingresosHoy }}</div><div class="t">ingresos hoy</div></div>
    <div class="panel seg-kpi fade-in" style="animation-delay:.12s"><div class="n" style="{{ $fallidosHoy ? 'color:#B91C1C' : '' }}">{{ $fallidosHoy }}</div><div class="t">intentos fallidos hoy</div></div>
</div>

<div class="g-21">
    <form method="POST" action="{{ route('admin.seguridad.guardar') }}" class="panel fade-in" style="padding:18px 20px;"
          x-data="{ intentos: {{ (int) old('intentos', $reglas['intentos']) }}, valor: {{ (int) old('bloqueo_valor', $reglas['bloqueo_valor']) }}, unidad: '{{ old('bloqueo_unidad', $reglas['bloqueo_unidad']) }}',
                    inact: {{ old('inactividad_activa', $reglas['inactividad_activa']) ? 'true' : 'false' }}, minInact: {{ (int) old('inactividad_minutos', $reglas['inactividad_minutos']) }},
                    mas(k, d, min, max) { this[k] = Math.max(min, Math.min(max, Number(this[k]) + d)); },
                    get maxValor() { return this.unidad === 'horas' ? 24 : 1440; },
                    get duracion() { const v = Number(this.valor) || 1; return this.unidad === 'horas' ? v + (v === 1 ? ' hora' : ' horas') : v + (v === 1 ? ' minuto' : ' minutos'); } }"
          x-effect="if (valor > maxValor) valor = maxValor">
        @csrf
        <div style="font-weight:700;font-size:15px;margin-bottom:6px;">Reglas de acceso</div>

        <div class="regla">
            <div>
                <b>Intentos permitidos</b>
                <span class="sub">Cuántas veces se puede equivocar la contraseña antes de bloquear el acceso ({{ $limites['intentos'][0] }} a {{ $limites['intentos'][1] }}).</span>
                <div class="puntos" aria-hidden="true"><template x-for="i in Number(intentos)" :key="i"><i :class="{ x: i === Number(intentos) }"></i></template></div>
            </div>
            <div class="contador">
                <button type="button" @click="mas('intentos', -1, {{ $limites['intentos'][0] }}, {{ $limites['intentos'][1] }})" aria-label="Menos">−</button>
                <input class="inp" type="number" name="intentos" x-model.number="intentos" min="{{ $limites['intentos'][0] }}" max="{{ $limites['intentos'][1] }}" required aria-label="Intentos permitidos">
                <button type="button" @click="mas('intentos', 1, {{ $limites['intentos'][0] }}, {{ $limites['intentos'][1] }})" aria-label="Más">+</button>
            </div>
        </div>

        <div class="regla">
            <div>
                <b>Tiempo de bloqueo</b>
                <span class="sub">Cuánto debe esperar antes de volver a intentar. Puedes elegir minutos u horas (máximo 24 horas).</span>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <div class="contador">
                    <button type="button" @click="mas('valor', -1, 1, maxValor)" aria-label="Menos">−</button>
                    <input class="inp" type="number" name="bloqueo_valor" x-model.number="valor" min="1" :max="maxValor" required aria-label="Tiempo de bloqueo">
                    <button type="button" @click="mas('valor', 1, 1, maxValor)" aria-label="Más">+</button>
                </div>
                <div class="unidad">
                    <label :class="{ on: unidad === 'minutos' }"><input type="radio" name="bloqueo_unidad" value="minutos" x-model="unidad">Minutos</label>
                    <label :class="{ on: unidad === 'horas' }"><input type="radio" name="bloqueo_unidad" value="horas" x-model="unidad">Horas</label>
                </div>
            </div>
        </div>

        <div class="regla">
            <div>
                <b>Bloqueo de pantalla por inactividad</b>
                <span class="sub">Si nadie usa el panel, se cubre y pide la contraseña. La sesión no se cierra. 30 segundos antes avisa.</span>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <label class="switch"><input type="checkbox" name="inactividad_activa" value="1" x-model="inact"><span></span></label>
                <span style="font-size:13px;font-weight:600;" x-text="inact ? 'Activado' : 'Apagado'"></span>
                <div class="contador" x-show="inact" x-transition>
                    <button type="button" @click="mas('minInact', -1, {{ $limites['inactividad'][0] }}, {{ $limites['inactividad'][1] }})" aria-label="Menos">−</button>
                    <input class="inp" type="number" name="inactividad_minutos" x-model.number="minInact" min="{{ $limites['inactividad'][0] }}" max="{{ $limites['inactividad'][1] }}" required aria-label="Minutos de inactividad">
                    <button type="button" @click="mas('minInact', 1, {{ $limites['inactividad'][0] }}, {{ $limites['inactividad'][1] }})" aria-label="Más">+</button>
                    <span class="sub">min</span>
                </div>
            </div>
        </div>

        <div class="resumen">
            Si alguien se equivoca <b x-text="intentos"></b> veces, el acceso se bloquea por <b x-text="duracion"></b>.
            <span x-show="inact">La pantalla se bloquea tras <b x-text="minInact"></b> min sin uso.</span>
            <span x-show="!inact">La pantalla no se bloquea por inactividad (puedes bloquearla tú desde tu menú).</span>
        </div>
        <button class="btn btn-p" type="submit" style="margin-top:14px;">Guardar reglas</button>
    </form>

    <div class="panel fade-in" style="padding:0;animation-delay:.06s;">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-weight:700;">Bloqueados ahora</div>
        @forelse ($bloqueados as $b)
            <div class="bloq">
                <div class="bloq-ico"><x-icon name="shield" size="18" /></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;overflow:hidden;text-overflow:ellipsis;">{{ $b->email }}</div>
                    <div class="sub" style="font-size:12px;color:var(--fg3);">IP {{ $b->ip ?: '—' }} · faltan {{ $b->resta }}</div>
                </div>
                <form method="POST" action="{{ route('admin.seguridad.desbloquear') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $b->email }}"><input type="hidden" name="ip" value="{{ $b->ip }}">
                    <button class="btn btn-g btn-sm" type="submit">Desbloquear</button>
                </form>
            </div>
        @empty
            <div class="vacio" style="padding:30px 16px;">
                <div class="vacio-ico"><x-icon name="shield" size="22" /></div>
                <div class="vacio-ttl">Nadie está bloqueado</div>
                <div class="vacio-txt">Si alguien de tu equipo se equivoca muchas veces, aparecerá aquí y podrás desbloquearlo sin esperar.</div>
            </div>
        @endforelse
    </div>
</div>

<div class="panel fade-in" style="padding:0;margin-top:14px;animation-delay:.1s;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-weight:700;">Historial de accesos <span class="sub" style="font-weight:400;font-size:12px;color:var(--fg3);">últimos 60</span></div>
    @if ($historial->isEmpty())
        <div class="vacio"><div class="vacio-ttl">Aún no hay accesos registrados</div><div class="vacio-txt">Cada ingreso, salida o intento fallido de tu equipo quedará anotado aquí.</div></div>
    @else
        <div style="overflow-x:auto;">
            <table class="hist">
                <thead><tr><th>Cuándo</th><th>Usuario</th><th>Qué pasó</th><th class="opc">Desde</th><th class="opc">IP</th></tr></thead>
                <tbody>
                @foreach ($historial as $i => $h)
                    <tr class="row-in" style="--d:{{ min($i, 15) * 20 }}ms">
                        <td style="white-space:nowrap;" title="{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}">{{ \Illuminate\Support\Carbon::parse($h->created_at)->diffForHumans() }}</td>
                        <td>{{ $h->email }}</td>
                        <td><span class="res res-{{ $h->resultado }}">{{ $etiquetas[$h->resultado] ?? $h->resultado }}</span></td>
                        <td class="opc">{{ $navegador($h->navegador) }}</td>
                        <td class="opc" style="font-variant-numeric:tabular-nums;">{{ $h->ip ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
