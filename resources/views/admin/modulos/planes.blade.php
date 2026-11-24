@extends('layouts.app')

@section('titulo', 'Planes y facturación')
@section('subtitulo', $estado === 'trial' ? 'Prueba gratis' : 'Plan ' . ($actual ? ucfirst($actual) : 'activo'))

@section('contenido')
<div x-data="{ ciclo: 'mensual' }">
    @php($niubiz = request('niubiz'))
    @if ($niubiz === 'aprobado')
        <div class="pl-aviso ok fade-in"><x-icon name="check" size="16" /> ¡Pago aprobado! Tu plan ya está activo. Gracias por confiar en GestionaHub.</div>
    @elseif ($niubiz === 'rechazado')
        <div class="pl-aviso no fade-in"><x-icon name="alert" size="16" /> El banco no aprobó el pago{{ request('msg') ? ': ' . request('msg') : '' }}. Puedes intentar con otra tarjeta o pagar con Yape, Plin o transferencia.</div>
    @endif
    @if (session('ok'))<div class="pl-aviso ok fade-in"><x-icon name="check" size="16" /> {{ session('ok') }}</div>@endif
    @if ($enRevision ?? false)
        <div class="pl-aviso info fade-in"><x-icon name="clock" size="16" /> Tu pago está en revisión. Te avisamos por correo apenas lo confirmemos.</div>
    @endif
    @php($planActual = $actual ? collect($planes)->firstWhere('slug', $actual) : null)
    @if (! empty($suscripcion) && $suscripcion->estado === 'activa')
        <div class="pl-hero fade-in">
            <div>
                <div class="ph-lbl">Plan actual</div>
                <div class="ph-nom">{{ $planActual['nombre'] ?? 'Activo' }}</div>
                <div class="ph-sub">
                    @if ($planActual)S/ {{ number_format($suscripcion->ciclo === 'anual' ? $planActual['anual'] : $planActual['precio'], 0) }} / {{ $suscripcion->ciclo === 'anual' ? 'año' : 'mes' }} · @endif
                    {{ $suscripcion->renovacion_auto ? 'Renovación automática' : 'Renovación manual' }} · vence el {{ \Carbon\Carbon::parse($suscripcion->vence_at)->format('d/m/Y') }}
                </div>
            </div>
            <div class="ph-der">
                <div class="ph-lbl" style="text-transform:none;letter-spacing:0;">Método de pago</div>
                <div class="ph-met">
                    @if ($suscripcion->tarjeta_ultimos){{ $suscripcion->tarjeta_marca ? ucfirst(strtolower($suscripcion->tarjeta_marca)) : 'Tarjeta' }} •••• {{ $suscripcion->tarjeta_ultimos }}
                    @else{{ ucfirst($pagos->first()['pasarela'] ?? 'Yape / Plin') }}@endif
                </div>
                @if ($suscripcion->renovacion_auto)
                    <form method="POST" action="{{ route('admin.planes.renovacion') }}" onsubmit="return confirm('¿Apagar la renovación automática? Borraremos los datos de tu tarjeta.')">@csrf<input type="hidden" name="activar" value="0"><button class="ph-btn" type="submit">Apagar renovación automática</button></form>
                @else
                    <div class="ph-nota">Te avisaremos antes de que venza</div>
                @endif
            </div>
        </div>
    @elseif (! empty($suscripcion) && $suscripcion->estado === 'vencida')
        <div class="pl-aviso no fade-in"><x-icon name="alert" size="16" /> No pudimos renovar tu plan con tu tarjeta. Elige un plan abajo para pagarlo de nuevo.</div>
    @endif
    @if ($estado === 'trial')
        <div class="nota fade-in">
            <x-icon name="clock" size="16" />
            <div>
                <strong style="color:var(--fg);">Estás en el periodo de prueba gratis.</strong>
                @if ($diasPrueba !== null)
                    Te {{ $diasPrueba === 1 ? 'queda 1 día' : 'quedan ' . $diasPrueba . ' días' }} (vence el {{ $venceTexto }}). Elige un plan para seguir usando GestionaHub sin interrupciones.
                @endif
            </div>
        </div>
    @endif

    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;flex-wrap:wrap;" class="fade-in">
        <div class="sec-ttl" style="margin:0;">Planes disponibles</div>
        <div class="seg">
            <a href="#" :class="{ on: ciclo === 'mensual' }" @click.prevent="ciclo = 'mensual'">Mensual</a>
            <a href="#" :class="{ on: ciclo === 'anual' }" @click.prevent="ciclo = 'anual'">Anual · 2 meses gratis</a>
        </div>
    </div>

    <div class="g-3" style="margin-bottom:18px;">
        @foreach ($planes as $i => $p)
            @php($esActual = $actual === $p['slug'])
            @php($destacado = $p['slug'] === 'negocio')
            <div class="plan fade-in {{ $destacado ? 'plan-dest' : '' }} {{ $esActual ? 'plan-act' : '' }}" style="animation-delay:{{ .06 + $i * .05 }}s">
                @if ($esActual)
                    <span class="plan-cinta">Plan actual</span>
                @elseif ($destacado)
                    <span class="plan-cinta">Recomendado</span>
                @endif
                <div class="plan-nom">{{ $p['nombre'] }}</div>
                <div class="plan-pr">
                    <span x-show="ciclo === 'mensual'">S/ {{ number_format($p['precio'], 0) }}<small>/mes</small></span>
                    <span x-show="ciclo === 'anual'" x-cloak>S/ {{ number_format($p['anual'], 0) }}<small>/año</small></span>
                </div>
                <ul class="plan-lst">
                    @foreach ($p['beneficios'] as $b)
                        <li><x-icon name="check" size="14" stroke="2.4" style="color:var(--green)" /> {{ $b }}</li>
                    @endforeach
                    @foreach ($p['faltan'] as $b)
                        <li class="no"><x-icon name="x" size="14" /> {{ $b }}</li>
                    @endforeach
                </ul>
                @if ($esActual)
                    <a class="btn btn-g" style="width:100%;justify-content:center;" :href="'{{ route('admin.planes.checkout', $p['slug']) }}?ciclo=' + ciclo">{{ $estado === 'trial' ? 'Activar este plan' : 'Pagar o renovar' }}</a>
                @else
                    <a class="btn {{ $destacado ? 'btn-p' : 'btn-g' }}" style="width:100%;justify-content:center;"
                       :href="'{{ route('admin.planes.checkout', $p['slug']) }}?ciclo=' + ciclo">{{ $estado === 'trial' ? 'Elegir este plan' : 'Cambiar a este plan' }}</a>
                @endif
            </div>
        @endforeach
    </div>

    <div class="panel fade-in" style="animation-delay:.25s">
        <div class="panel-hd"><div class="panel-ttl">Historial de cobros</div></div>
        @if (count($pagos))
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr><th>Fecha</th><th>Pasarela</th><th class="num">Monto</th><th>Estado</th></tr></thead>
                    <tbody>
                        @foreach ($pagos as $pg)
                            <tr>
                                <td>{{ $pg['fecha'] }}</td>
                                <td>{{ $pg['pasarela'] }}</td>
                                <td class="num" style="font-weight:700;">S/ {{ number_format($pg['monto'], 2) }}</td>
                                <td><span class="badge {{ ['pagado' => 'b-ok', 'pendiente' => 'b-a', 'fallido' => 'b-r'][$pg['estado']] ?? 'b-n' }}"><span class="dot"></span>{{ ['pagado' => 'Pagado', 'pendiente' => 'En revisión', 'fallido' => 'Rechazado'][$pg['estado']] ?? ucfirst($pg['estado']) }}</span>
                                    @if (! empty($pg['motivo']))<div style="font-size:11px;color:var(--fg3);margin-top:3px;">{{ $pg['motivo'] }}</div>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-vacio icono="card" titulo="Aún no hay cobros registrados" texto="Aquí verás cada pago de tu suscripción con su comprobante." />
        @endif
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .pl-hero { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; border-radius:16px; padding:22px 24px; margin-bottom:20px; color:#fff;
                   background:linear-gradient(120deg, var(--accent, #4B7CF5) 0%, #1E3A8A 70%, #1A2332 100%); box-shadow:0 12px 30px rgba(30,58,138,.18); }
        .pl-hero .ph-lbl { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; opacity:.8; }
        .pl-hero .ph-nom { font-size:24px; font-weight:800; margin:4px 0; }
        .pl-hero .ph-sub { font-size:13px; opacity:.85; }
        .pl-hero .ph-der { text-align:right; display:grid; gap:4px; justify-items:end; }
        .pl-hero .ph-met { font-size:14px; font-weight:700; }
        .pl-hero .ph-nota { font-size:11px; opacity:.75; }
        .pl-hero .ph-btn { margin-top:4px; background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.35); border-radius:8px; padding:6px 12px; font:inherit; font-size:12px; cursor:pointer; }
        @media (max-width:640px) { .pl-hero .ph-der { text-align:left; justify-items:start; } }
        .plan{position:relative;display:flex;flex-direction:column;border:1px solid var(--border);border-radius:12px;padding:20px;background:#fff;transition:transform .2s var(--ease),box-shadow .2s var(--ease);}
        .plan:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(26,35,50,.08);}
        .plan-dest{border-color:var(--acm);box-shadow:0 0 0 1px var(--acm);}
        .plan-act{border-color:var(--accent);box-shadow:0 0 0 1px var(--accent);background:linear-gradient(180deg,#fff 0%,#EEF3FE 100%);}
        .plan-cinta{position:absolute;top:-10px;left:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;background:var(--accent);color:#fff;padding:3px 10px;border-radius:99px;}
        .plan-act .plan-cinta{background:var(--accent);left:auto;right:16px;}
        .plan-nom{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--fg3);}
        .plan-pr{font-size:28px;font-weight:700;letter-spacing:-.5px;margin:6px 0 14px;font-variant-numeric:tabular-nums;}
        .plan-pr small{font-size:13px;font-weight:500;color:var(--fg3);margin-left:2px;}
        .plan-lst{list-style:none;display:flex;flex-direction:column;gap:8px;margin-bottom:18px;flex:1;font-size:13px;}
        .plan-lst li{display:flex;align-items:center;gap:8px;}
        .plan-lst li.no{color:var(--fg3);}
        .pl-aviso{display:flex;gap:10px;align-items:center;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:14px;border:1px solid;}
        .pl-aviso.ok{background:#F0FDF4;border-color:#BBF7D0;color:#166534;}
        .pl-aviso.no{background:#FEF2F2;border-color:#FECACA;color:#991B1B;}
        .pl-aviso.info{background:#EFF6FF;border-color:#BFDBFE;color:#1E40AF;}
    </style>
@endpush
