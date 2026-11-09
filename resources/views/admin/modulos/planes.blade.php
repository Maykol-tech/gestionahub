@extends('layouts.app')

@section('titulo', 'Planes y facturación')
@section('subtitulo', $estado === 'trial' ? 'Prueba gratis' : 'Plan ' . ($actual ? ucfirst($actual) : 'activo'))

@section('contenido')
<div x-data="{ ciclo: 'mensual' }">
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
                    <span class="plan-cinta">Tu plan</span>
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
                    <button class="btn btn-g" style="width:100%;justify-content:center;" disabled>Plan actual</button>
                @else
                    <button class="btn {{ $destacado ? 'btn-p' : 'btn-g' }}" style="width:100%;justify-content:center;" x-data
                            @click="$store.toasts.push('El cambio de plan con Mercado Pago estará disponible pronto', 'info')">Actualizar plan</button>
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
                                <td><span class="badge {{ ['pagado' => 'b-ok', 'pendiente' => 'b-a', 'fallido' => 'b-r'][$pg['estado']] ?? 'b-n' }}"><span class="dot"></span>{{ ucfirst($pg['estado']) }}</span></td>
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
        .plan{position:relative;display:flex;flex-direction:column;border:1px solid var(--border);border-radius:12px;padding:20px;background:#fff;transition:transform .2s var(--ease),box-shadow .2s var(--ease);}
        .plan:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(26,35,50,.08);}
        .plan-dest{border-color:var(--acm);box-shadow:0 0 0 1px var(--acm);}
        .plan-act{border-color:var(--green);box-shadow:0 0 0 1px var(--green);}
        .plan-cinta{position:absolute;top:-10px;left:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;background:var(--accent);color:#fff;padding:3px 10px;border-radius:99px;}
        .plan-act .plan-cinta{background:var(--green);}
        .plan-nom{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--fg3);}
        .plan-pr{font-size:28px;font-weight:700;letter-spacing:-.5px;margin:6px 0 14px;font-variant-numeric:tabular-nums;}
        .plan-pr small{font-size:13px;font-weight:500;color:var(--fg3);margin-left:2px;}
        .plan-lst{list-style:none;display:flex;flex-direction:column;gap:8px;margin-bottom:18px;flex:1;font-size:13px;}
        .plan-lst li{display:flex;align-items:center;gap:8px;}
        .plan-lst li.no{color:var(--fg3);}
    </style>
@endpush
