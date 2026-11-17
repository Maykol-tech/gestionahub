@php
    $simbolo = $config['moneda']['simbolo'] ?? 'S/';
    $estados = \App\Models\SuscripcionCliente::ESTADOS;
    $estCobro = \App\Models\CobroSuscripcion::ESTADOS;
    $primerNombre = \Illuminate\Support\Str::of((string) optional($s->cliente)->nombre)->explode(' ')->first();
    $periodo = strtolower($periodos[$s->periodicidad] ?? $s->periodicidad);
    $puedePagar = $abierto && in_array($abierto->estado, ['pendiente', 'vencido'], true) && $s->estado !== 'cancelada';
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $color }}">
    <title>Mi suscripción · {{ $config['negocio']['nombre'] }}</title>
    @include('publico._membresia_estilos')
    <style>
        .resumen { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
        .fila { display:flex; justify-content:space-between; gap:10px; font-size:14px; padding:9px 0; border-top:1px solid var(--border); }
        .fila span { color:var(--fg3); }
        .metodo { display:flex; gap:12px; align-items:center; padding:12px; border:1px solid var(--border); border-radius:14px; margin-top:8px; }
        .metodo .num { font-size:18px; font-weight:800; letter-spacing:.04em; }
        .metodo img { width:92px; height:92px; border-radius:10px; background:#fff; padding:4px; object-fit:contain; }
        .copiar { margin-left:auto; padding:7px 11px; border-radius:9px; border:1px solid var(--border); background:transparent; color:var(--fg); font-weight:700; font-size:13px; cursor:pointer; font-family:inherit; }
        .mov { display:flex; justify-content:space-between; gap:10px; font-size:13px; padding:9px 0; border-top:1px solid var(--border); align-items:center; }
        .ttl + .mov { border-top:0; padding-top:0; }
        .mov small { display:block; color:var(--fg3); font-size:12px; }
    </style>
</head>
<body>
<div class="wrap">
    @include('publico._membresia_negocio', ['leyenda' => 'Mi suscripción'])

    @if (session('ok'))<div class="alerta ok">{{ session('ok') }}</div>@endif
    @if ($errors->any())<div class="alerta mal">{{ $errors->first() }}</div>@endif

    <div class="card">
        <div class="resumen">
            <div>
                <div style="font-size:13px;color:var(--fg3);">Hola {{ $primerNombre }}, tu plan</div>
                <div style="font-weight:800;font-size:19px;">{{ optional($s->plan)->nombre ?? 'Plan' }}</div>
                <div style="font-size:14px;color:var(--fg2);margin-top:2px;">{{ $simbolo }} {{ number_format($s->precio, 2) }} {{ $periodo }}</div>
            </div>
            <span class="chip {{ $s->estado }}">{{ $estados[$s->estado] ?? $s->estado }}</span>
        </div>
        @if ($s->estado === 'activa')
            <div class="fila" style="margin-top:12px;"><span>Activa hasta</span><b>{{ $s->proximo_cobro?->format('d/m/Y') }}</b></div>
        @elseif ($s->estado === 'cancelada')
            <div class="alerta info">Esta suscripción fue cancelada. Si quieres volver, escríbele al negocio.</div>
        @elseif ($s->estado === 'vencida')
            <div class="alerta mal">Tu suscripción está vencida. Paga el periodo pendiente para volver a activarla.</div>
        @endif
        @if (optional($s->plan)->beneficios)
            <ul class="ben">@foreach ((array) $s->plan->beneficios as $b)<li>{{ $b }}</li>@endforeach</ul>
        @endif
    </div>

    @if ($abierto && $abierto->estado === 'por_revisar')
        <div class="card">
            <div class="ttl">Pago en revisión</div>
            <p class="sub" style="margin:0;">Recibimos tu captura de {{ $simbolo }} {{ number_format($abierto->monto, 2) }}. El negocio la revisará y tu suscripción se activará. No tienes que hacer nada más.</p>
        </div>
    @elseif ($puedePagar)
        <div class="card" id="pagar">
            <div class="ttl">Por pagar</div>
            <div class="precio">{{ $simbolo }} {{ number_format($abierto->monto, 2) }}</div>
            <div style="font-size:13px;color:var(--fg2);">Periodo del {{ $abierto->desde->format('d/m') }} al {{ $abierto->hasta->format('d/m/Y') }}{{ $abierto->estado === 'pendiente' ? ' · paga antes del ' . $abierto->vence->format('d/m') : '' }}</div>

            @if ($mpFormulario)
                <div class="ttl" style="margin-top:16px;">💳 Paga con tarjeta</div>
                @include('publico._mp_formulario', ['mpKey' => $mpFormulario, 'mpMonto' => $abierto->monto, 'mpColor' => $color,
                    'mpUrl' => route('membresia.tarjeta.cobrar', $s->token), 'mpAlternativa' => route('membresia.tarjeta', $s->token)])
            @elseif ($tarjeta)
                <a class="btn" href="{{ route('membresia.tarjeta', $s->token) }}">💳 Pagar con tarjeta</a>
                <div class="pie" style="margin-top:6px;">Pagas en la página segura de Mercado Pago. Tu suscripción se activa al instante.</div>
            @endif

            @if (count($pagos))
                <div class="ttl" style="margin-top:16px;">{{ $tarjeta ? 'O paga con' : 'Paga con' }} Yape o Plin</div>
                @foreach ($pagos as $m)
                    <div class="metodo">
                        @if (! empty($m['qr']))<img src="{{ $m['qr'] }}" alt="QR de {{ $m['nombre'] }}">@endif
                        <div>
                            <div style="font-size:13px;color:var(--fg3);">{{ $m['nombre'] }}</div>
                            <div class="num">{{ $m['numero'] }}</div>
                        </div>
                        <button type="button" class="copiar" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $m['numero'] }}'); this.textContent='Copiado ✓'">Copiar</button>
                    </div>
                @endforeach
            @endif

            <form method="POST" action="{{ route('membresia.reportar', $s->token) }}" enctype="multipart/form-data" style="margin-top:6px;">
                @csrf
                <label for="metodo">¿Cómo pagaste?</label>
                <select class="inp" name="metodo" id="metodo" required>
                    <option value="">Elige</option>
                    <option value="yape" @selected(old('metodo') === 'yape')>Yape</option>
                    <option value="plin" @selected(old('metodo') === 'plin')>Plin</option>
                    <option value="transferencia" @selected(old('metodo') === 'transferencia')>Transferencia</option>
                </select>
                <label for="captura">Foto o captura del pago</label>
                <input class="inp" type="file" name="captura" id="captura" accept="image/*" required>
                <label for="referencia">N.º de operación (opcional)</label>
                <input class="inp" name="referencia" id="referencia" value="{{ old('referencia') }}" maxlength="60">
                <button class="btn {{ $tarjeta ? 'sec' : '' }}" type="submit">Enviar captura</button>
            </form>
        </div>
    @endif

    @if ($cobros->isNotEmpty())
        <div class="card">
            <div class="ttl">Tus pagos</div>
            @foreach ($cobros as $c)
                <div class="mov">
                    <div>{{ $c->desde->format('d/m/Y') }} al {{ $c->hasta->format('d/m/Y') }}<small>{{ $simbolo }} {{ number_format($c->monto, 2) }}{{ $c->pagado_at ? ' · pagado el ' . $c->pagado_at->format('d/m/Y') : '' }}</small></div>
                    <span class="chip {{ $c->estado }}">{{ $estCobro[$c->estado] ?? $c->estado }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="pie">Guarda este enlace: aquí verás y pagarás cada periodo.<br>{{ $config['negocio']['nombre'] }}@if ($config['whatsapp']) · <a href="https://wa.me/{{ $config['whatsapp'] }}" style="color:inherit;">Escribir al negocio</a>@endif</div>
</div>
</body>
</html>
