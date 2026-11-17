@php
    $s = $config['moneda']['simbolo'];
    $d = $config['diseno'];
    $neg = $config['negocio'];
    $estados = ['pendiente' => 'Recibido', 'confirmado' => 'Confirmado', 'preparando' => 'En preparación', 'listo' => 'Listo', 'en_camino' => 'En camino', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
    $orden = ['pendiente', 'confirmado', 'preparando', 'en_camino', 'entregado'];
    $actual = array_search($pedido->estado === 'listo' ? 'preparando' : $pedido->estado, $orden, true);
    $f = fn ($n) => $s . ' ' . number_format((float) $n, 2);
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pedido {{ $pedido->numero }} · {{ $neg['nombre'] }}</title>
    @include('publico._diseno_head', ['d' => $d])
    <style>
        :root { --bg:#F6F7FB; --fg:#111827; --fg2:#4B5563; --fg3:#6B7280; --card:#fff; --border:#E5E7EB; --ok:#16A34A; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --bg:#0B1220; --fg:#F3F4F6; --fg2:#D1D5DB; --fg3:#9CA3AF; --card:#111A2B; --border:#1F2A3D; } }
        :root[data-theme="dark"] { --bg:#0B1220; --fg:#F3F4F6; --fg2:#D1D5DB; --fg3:#9CA3AF; --card:#111A2B; --border:#1F2A3D; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--fg); -webkit-font-smoothing:antialiased; }
        .wrap { max-width:560px; margin:0 auto; padding:24px 16px 60px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:22px; margin-bottom:14px; box-shadow:0 6px 16px rgba(16,24,40,.06); animation:up .5s cubic-bezier(.2,.8,.2,1) both; }
        .card:nth-child(2) { animation-delay:.08s; } .card:nth-child(3) { animation-delay:.16s; } .card:nth-child(4) { animation-delay:.24s; }
        @keyframes up { from { opacity:0; transform:translateY(14px); } }
        .check { width:72px; height:72px; margin:4px auto 14px; border-radius:50%; background:var(--ok); display:grid; place-items:center; animation:pop .55s cubic-bezier(.3,1.6,.5,1) both; box-shadow:0 0 0 0 rgba(22,163,74,.4); }
        .check svg { width:38px; height:38px; stroke:#fff; stroke-width:3; fill:none; stroke-dasharray:40; stroke-dashoffset:40; animation:trazo .5s .35s ease forwards; }
        @keyframes pop { from { transform:scale(.3); opacity:0; } }
        @keyframes trazo { to { stroke-dashoffset:0; } }
        h1 { text-align:center; margin:0 0 6px; font-size:22px; }
        .centro { text-align:center; color:var(--fg2); font-size:14px; margin:0; }
        .codigo { display:inline-block; margin-top:10px; background:var(--bg); border:1px dashed var(--border); border-radius:10px; padding:6px 12px; font-weight:800; letter-spacing:.5px; }
        h2 { font-size:15px; margin:0 0 12px; }
        .linea { display:flex; justify-content:space-between; gap:12px; padding:8px 0; border-bottom:1px solid var(--border); font-size:14px; }
        .linea small { display:block; color:var(--fg3); }
        .linea b, .num { font-variant-numeric:tabular-nums; white-space:nowrap; }
        .linea.total { border:0; font-size:17px; font-weight:800; padding-top:12px; }
        .verde { color:var(--ok); }
        .estado { display:flex; justify-content:space-between; position:relative; margin:6px 0 4px; }
        .estado::before { content:''; position:absolute; top:13px; left:10%; right:10%; height:3px; background:var(--border); border-radius:3px; }
        .estado .barra { position:absolute; top:13px; left:10%; height:3px; background:var(--brand); border-radius:3px; transition:width .8s; }
        .paso { position:relative; z-index:1; display:flex; flex-direction:column; align-items:center; gap:6px; width:20%; font-size:11px; color:var(--fg3); text-align:center; }
        .paso i { width:28px; height:28px; border-radius:50%; background:var(--card); border:3px solid var(--border); display:grid; place-items:center; font-style:normal; font-size:12px; }
        .paso.on { color:var(--fg); font-weight:700; } .paso.on i { background:var(--brand); border-color:var(--brand); color:#fff; }
        .paso.ahora i { animation:latido 1.6s infinite; }
        @keyframes latido { 50% { box-shadow:0 0 0 7px color-mix(in srgb, var(--brand) 25%, transparent); } }
        .dato { display:flex; gap:10px; padding:6px 0; font-size:14px; } .dato span:first-child { width:22px; text-align:center; }
        .pago { background:color-mix(in srgb, var(--brand) 8%, var(--card)); border:1px solid color-mix(in srgb, var(--brand) 30%, var(--border)); }
        .pago .n { font-size:22px; font-weight:800; letter-spacing:1px; }
        .qr { width:200px; max-width:100%; margin:12px auto 0; display:block; border-radius:12px; background:#fff; padding:8px; }
        .btn { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:14px; border-radius:14px; font-weight:800; text-decoration:none; border:0; cursor:pointer; font-size:15px; margin-top:10px; transition:transform .15s; }
        .btn:active { transform:scale(.98); }
        .btn.wa { background:#25D366; color:#fff; }
        .enviado { margin-top:12px; padding:10px 12px; border-radius:12px; background:#DCFCE7; color:#166534; font-size:14px; font-weight:600; animation:sube .35s ease both; }
        .rechazado { margin-top:12px; padding:10px 12px; border-radius:12px; background:#FEE2E2; color:#991B1B; font-size:14px; font-weight:600; }
        .subir { margin-top:14px; border-top:1px dashed var(--border); padding-top:14px; }
        .subir-ttl { font-weight:700; font-size:14px; margin-bottom:8px; }
        .archivo { display:flex; align-items:center; justify-content:center; padding:14px; border:2px dashed var(--border); border-radius:14px; cursor:pointer; font-weight:700; font-size:14px; color:var(--fg2); transition:border-color .2s, background .2s; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .archivo:hover { border-color:var(--brand); }
        .archivo input { display:none; }
        .op { width:100%; margin-top:8px; padding:12px 14px; border-radius:12px; border:1.5px solid var(--border); background:var(--card); color:var(--fg); font:inherit; font-size:15px; }
        .err { color:#B91C1C; font-size:13px; font-weight:600; margin-top:6px; }
        @keyframes sube { from { opacity:0; transform:translateY(6px); } }
        .btn.sec { background:transparent; color:var(--fg); border:1.5px solid var(--border); }
        .copiar { background:none; border:1px solid var(--border); border-radius:8px; padding:4px 10px; font-size:12px; cursor:pointer; color:var(--fg2); margin-left:8px; }
        .cancelado { background:#FEF2F2; color:#991B1B; border-radius:12px; padding:10px 12px; font-size:14px; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation:none !important; transition:none !important; } .check svg { stroke-dashoffset:0; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="check"><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
        <h1>¡Gracias, {{ \Illuminate\Support\Str::before((string) $pedido->cliente_nombre, ' ') ?: 'recibimos tu pedido' }}!</h1>
        <p class="centro">{{ $neg['nombre'] }} recibió tu pedido{{ $pedido->whatsapp_ok ? ' y te envió la confirmación por WhatsApp' : '' }}.</p>
        <p class="centro"><span class="codigo">{{ $pedido->numero }}</span></p>

        @if ($pedido->estado === 'cancelado')
            <p class="cancelado" style="margin-top:16px;">Este pedido fue cancelado. Escríbele al negocio si tienes dudas.</p>
        @else
            <div class="estado" style="margin-top:20px;" aria-label="Estado: {{ $estados[$pedido->estado] ?? $pedido->estado }}">
                <span class="barra" style="width:{{ max(0, (int) $actual) * 20 }}%"></span>
                @foreach (['Recibido', 'Confirmado', 'Preparando', 'En camino', 'Entregado'] as $k => $e)
                    @php $on = $actual !== false && $k <= $actual; @endphp
                    <div class="paso {{ $on ? 'on' : '' }} {{ $k === $actual ? 'ahora' : '' }}"><i>{{ $on ? '✓' : $k + 1 }}</i>{{ $pedido->tipo === 'recojo' && $e === 'En camino' ? 'Listo' : $e }}</div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($pago && in_array($pago['clave'], ['yape', 'plin', 'transferencia'], true) && ! $pedido->pagado && $pedido->estado !== 'cancelado')
        <div class="card pago">
            <h2>{{ $pago['icono'] }} Paga con {{ $pago['nombre'] }}</h2>
            @if (! empty($pago['numero']))
                <div>Número: <span class="n" id="num">{{ $pago['numero'] }}</span><button type="button" class="copiar" onclick="navigator.clipboard?.writeText('{{ preg_replace('/\D/', '', $pago['numero']) }}'); this.textContent='¡Copiado!'">Copiar</button></div>
            @else
                <p class="centro" style="text-align:left;">{{ $pago['detalle'] }}</p>
            @endif
            <div style="margin-top:6px;">Monto: <b class="num">{{ $f($pedido->total) }}</b></div>
            @if (! empty($pago['qr']))<img class="qr" src="{{ $pago['qr'] }}" alt="QR de {{ $pago['nombre'] }}">@endif
            @if (session('pago_enviado') || ($pagoReportado && $pagoReportado->estado === 'pendiente'))
                <div class="enviado">✓ Recibimos tu captura{{ $pagoReportado?->referencia ? ' (operación ' . $pagoReportado->referencia . ')' : '' }}. {{ $neg['nombre'] }} la revisará y confirmará tu pago.</div>
            @elseif ($pagoReportado && $pagoReportado->estado === 'rechazado')
                <div class="rechazado">No pudimos confirmar el pago que enviaste. Revisa el monto y vuelve a subir la captura, o escribe al negocio.</div>
            @endif
            <form class="subir" method="POST" action="{{ route('tienda.pago.reportar', [$neg['slug'], $pedido->numero]) }}" enctype="multipart/form-data">
                @csrf
                <div class="subir-ttl">{{ ($pagoReportado && $pagoReportado->estado === 'pendiente') ? '¿Te equivocaste de foto? Puedes cambiarla' : 'Cuando pagues, sube aquí tu captura' }}</div>
                <label class="archivo">
                    <input type="file" name="captura" accept="image/*" required onchange="this.closest('label').querySelector('span').textContent = this.files[0] ? '📎 ' + this.files[0].name : 'Elegir foto de la captura'">
                    <span>📷 Elegir foto de la captura</span>
                </label>
                <input class="op" name="referencia" maxlength="60" placeholder="N.º de operación (opcional)" value="{{ old('referencia') }}">
                @error('captura')<div class="err">{{ $message }}</div>@enderror
                @error('referencia')<div class="err">{{ $message }}</div>@enderror
                <button class="btn" type="submit" style="background:var(--brand);color:#fff;">Enviar captura</button>
            </form>
        </div>
    @elseif ($pedido->metodo_pago === 'mercadopago' && ! $pedido->pagado && $pedido->estado !== 'cancelado')
        <div class="card pago">
            <h2>💳 Paga con tarjeta</h2>
            @if (session('mp_error'))<div class="rechazado">{{ session('mp_error') }}</div>@endif
            @if ($mpEstado === 'pending' || $mpEstado === 'in_process')
                <div class="enviado" style="background:#FEF3C7;color:#92400E;">Tu pago está en proceso. Te avisaremos cuando Mercado Pago lo apruebe.</div>
            @elseif ($mpEstado === 'rejected' || $mpEstado === 'failure')
                <div class="rechazado">El pago no se completó. Puedes intentarlo otra vez con otra tarjeta.</div>
            @elseif (! $mpFormulario)
                <p style="font-size:14px;color:var(--fg2);margin:6px 0 0;">Monto: <b class="num">{{ $f($pedido->total) }}</b>. Pagas de forma segura en Mercado Pago; nosotros no vemos los datos de tu tarjeta.</p>
            @endif
            @if ($mpFormulario)
                <p style="font-size:14px;color:var(--fg2);margin:6px 0 0;">Monto: <b class="num">{{ $f($pedido->total) }}</b></p>
                @include('publico._mp_formulario', ['mpKey' => $mpFormulario, 'mpMonto' => $pedido->total, 'mpColor' => $color,
                    'mpUrl' => route('tienda.mp.cobrar', [$neg['slug'], $pedido->numero]), 'mpAlternativa' => route('tienda.mp.pagar', [$neg['slug'], $pedido->numero])])
            @else
                <a class="btn" href="{{ route('tienda.mp.pagar', [$neg['slug'], $pedido->numero]) }}" style="background:#009EE3;color:#fff;">Pagar {{ $f($pedido->total) }} con tarjeta</a>
            @endif
        </div>
    @elseif ($pedido->pagado && $pedido->estado !== 'cancelado')
        <div class="card pago"><div class="enviado" style="margin:0;">✓ Tu pago está confirmado. ¡Gracias!</div></div>
    @endif

    <div class="card">
        <h2>Tu pedido</h2>
        @foreach ($pedido->items as $item)
            <div class="linea">
                <span>{{ $item->peso_kg ? '' : $item->cantidad . ' × ' }}{{ $item->nombre }}
                    @if (($item->variante_nombre && ! str_contains((string) $item->nombre, $item->variante_nombre)) || $item->peso_kg)<small>{{ collect([$item->variante_nombre && ! str_contains((string) $item->nombre, $item->variante_nombre) ? $item->variante_nombre : null, $item->peso_kg ? \App\Support\TiendaPedido::textoPeso((float) $item->peso_kg) : null])->filter()->join(' · ') }}</small>@endif
                </span>
                <b>{{ $f($item->subtotal) }}</b>
            </div>
        @endforeach
        @if ((float) $pedido->costo_envio > 0)<div class="linea"><span>Envío</span><b>{{ $f($pedido->costo_envio) }}</b></div>@endif
        @if ((float) $pedido->descuento > 0)<div class="linea verde"><span>Descuentos{{ $pedido->cupon_codigo ? ' (cupón ' . $pedido->cupon_codigo . ')' : '' }}{{ $pedido->puntos_usados ? ' · ' . $pedido->puntos_usados . ' puntos' : '' }}</span><b>-{{ $f($pedido->descuento) }}</b></div>@endif
        <div class="linea total"><span>Total</span><b>{{ $f($pedido->total) }}</b></div>
        @if ($pedido->items->contains(fn ($i) => $i->peso_kg))
            <p style="font-size:12px;color:var(--fg3);margin:6px 0 0;">Los productos por kilo se pesan al despachar; el total puede variar un poco.</p>
        @endif
    </div>

    <div class="card">
        <h2>Entrega y pago</h2>
        <div class="dato"><span>{{ $pedido->tipo === 'delivery' ? '🛵' : '🏪' }}</span><span>{{ $pedido->tipo === 'delivery' ? ($pedido->direccion_entrega ?: 'Delivery') : 'Recojo en ' . ($neg['direccion'] ?: 'el local') }}</span></div>
        @if ($pedido->programado_para)
            <div class="dato"><span>🗓️</span><span>Programado para <b>{{ $pedido->programado_para->locale('es')->translatedFormat('l d \d\e F, H:i') }}</b></span></div>
        @endif
        <div class="dato"><span>{{ $pago['icono'] ?? '💵' }}</span><span>{{ $pago['nombre'] ?? ucfirst((string) $pedido->metodo_pago) }}{{ $pedido->pagado ? ' · Pagado ✓' : '' }}</span></div>

        @if ($whatsapp)
            <a class="btn wa" href="{{ $whatsapp }}" target="_blank" rel="noopener">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-1.6-.8-2.7-1.5-3.8-3.3-.3-.5.3-.5.8-1.6.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 1.9.8 2.6.9 3.6.7.6-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>
                Escribir al negocio por WhatsApp
            </a>
        @endif
        <a class="btn sec" href="{{ route('tienda.publica', $neg['slug']) }}">Seguir comprando</a>
    </div>
</div>
</body>
</html>
