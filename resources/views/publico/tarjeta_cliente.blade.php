@php
    $nombreNegocio = $negocio->nombre_negocio ?? 'Tu negocio';
    $logo = $diseno['logo_url'] ?? null;
    $primerNombre = \Illuminate\Support\Str::of($cliente->nombre)->explode(' ')->first();
    $iniciales = collect(explode(' ', trim($nombreNegocio)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ $color }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Mi tarjeta · {{ $nombreNegocio }}</title>
    <style>
        :root { --bg:#F3F5F9; --card:#fff; --fg:#111827; --fg2:#4B5563; --fg3:#6B7280; --border:#E5E7EB; --brand:{{ $color }}; --ok:#16A34A; --red:#DC2626; }
        @media (prefers-color-scheme: dark) { :root { --bg:#0B1220; --card:#121B2C; --fg:#F3F4F6; --fg2:#CBD5E1; --fg3:#94A3B8; --border:#1F2A3D; } }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background:var(--bg); color:var(--fg); -webkit-font-smoothing:antialiased; }
        .wrap { max-width:440px; margin:0 auto; padding:18px 16px 40px; padding-top:max(18px, env(safe-area-inset-top)); }
        .neg { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
        .neg img, .neg .ini { width:38px; height:38px; border-radius:10px; object-fit:cover; background:var(--brand); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; }
        .neg b { font-size:15px; }
        .neg span { display:block; font-size:12px; color:var(--fg3); }

        .tarjeta { position:relative; overflow:hidden; border-radius:22px; padding:20px; color:#fff;
            background:linear-gradient(135deg, var(--brand), color-mix(in srgb, var(--brand) 55%, #000)); box-shadow:0 14px 34px -14px color-mix(in srgb, var(--brand) 70%, #000); }
        .tarjeta::after { content:''; position:absolute; right:-60px; top:-60px; width:200px; height:200px; border-radius:50%; background:rgba(255,255,255,.09); }
        .t-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; position:relative; z-index:1; }
        .t-hola { font-size:13px; opacity:.85; }
        .t-nom { font-size:19px; font-weight:800; margin-top:2px; }
        .nivel { font-size:12px; font-weight:800; padding:5px 11px; border-radius:99px; background:rgba(255,255,255,.2); white-space:nowrap; }
        .t-pts { margin-top:18px; position:relative; z-index:1; }
        .t-pts small { font-size:12px; opacity:.85; text-transform:uppercase; letter-spacing:.08em; font-weight:700; }
        .t-pts div { font-size:44px; font-weight:900; line-height:1.05; font-variant-numeric:tabular-nums; }
        .t-pts span { font-size:13px; opacity:.9; }
        .barra { height:7px; border-radius:99px; background:rgba(255,255,255,.25); margin-top:14px; overflow:hidden; position:relative; z-index:1; }
        .barra i { display:block; height:100%; background:#fff; border-radius:99px; }
        .t-sig { font-size:12px; margin-top:7px; opacity:.9; position:relative; z-index:1; }

        .card { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:16px; margin-top:14px; }
        .ttl { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:var(--fg3); margin-bottom:10px; }
        .qr { display:flex; gap:16px; align-items:center; }
        .qr .cod { background:#fff; padding:10px; border-radius:14px; border:1px solid var(--border); flex-shrink:0; }
        .qr .cod svg { width:120px; height:120px; display:block; }
        .qr p { margin:0; font-size:13px; color:var(--fg2); line-height:1.45; }
        .qr b { display:block; font-size:16px; letter-spacing:.12em; margin-top:6px; color:var(--fg); }
        .rec { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 0; border-top:1px solid var(--border); font-size:14px; }
        .ttl + .rec { border-top:0; padding-top:0; }
        .rec .pt { font-size:12px; font-weight:800; padding:4px 9px; border-radius:99px; white-space:nowrap; }
        .rec .si { background:#DCFCE7; color:#166534; }
        .rec .no { background:var(--bg); color:var(--fg3); }
        .mov { display:flex; justify-content:space-between; gap:10px; font-size:13px; padding:8px 0; border-top:1px solid var(--border); }
        .ttl + .mov { border-top:0; padding-top:0; }
        .mov span { color:var(--fg3); font-size:12px; display:block; }
        .mov b.mas { color:var(--ok); } .mov b.men { color:var(--red); }
        .vacio { font-size:13px; color:var(--fg3); }
        .pie { text-align:center; font-size:12px; color:var(--fg3); margin-top:18px; line-height:1.5; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="neg">
        @if ($logo)
            <img src="{{ $logo }}" alt="">
        @else
            <div class="ini">{{ $iniciales }}</div>
        @endif
        <div><b>{{ $nombreNegocio }}</b><span>Tarjeta de cliente frecuente</span></div>
    </div>

    <div class="tarjeta">
        <div class="t-top">
            <div><div class="t-hola">Hola,</div><div class="t-nom">{{ $primerNombre }}</div></div>
            <div class="nivel">★ {{ $nivel['nombre'] }}</div>
        </div>
        <div class="t-pts">
            <small>Tus puntos</small>
            <div>{{ number_format($saldo) }}</div>
            @if ($soles > 0)
                <span>Equivalen a S/ {{ number_format($soles, 2) }} de descuento</span>
            @endif
        </div>
        <div class="barra"><i style="width:{{ $progreso }}%"></i></div>
        <div class="t-sig">
            @if ($siguiente)
                Te faltan {{ number_format($siguiente['faltan']) }} puntos para ser {{ $siguiente['nombre'] }}
            @else
                Estás en el nivel más alto. ¡Gracias por tu preferencia!
            @endif
        </div>
    </div>

    <div class="card">
        <div class="ttl">Muéstralo al pagar</div>
        <div class="qr">
            <div class="cod">{!! $qr !!}</div>
            <p>Enseña este código en caja para sumar tus puntos o canjear un premio.<b>{{ $codigo }}</b></p>
        </div>
    </div>

    <div class="card">
        <div class="ttl">Premios que puedes canjear</div>
        @forelse ($recompensas as $r)
            <div class="rec">
                <div>{{ $r->nombre }}</div>
                <div class="pt {{ $saldo >= $r->puntos_requeridos ? 'si' : 'no' }}">
                    {{ $saldo >= $r->puntos_requeridos ? '✓ ' : '' }}{{ number_format($r->puntos_requeridos) }} pts
                </div>
            </div>
        @empty
            <div class="vacio">Pronto habrá premios para canjear con tus puntos.</div>
        @endforelse
    </div>

    <div class="card">
        <div class="ttl">Tus últimos movimientos</div>
        @forelse ($movimientos as $m)
            <div class="mov">
                <div>{{ $m->descripcion ?: ($m->puntos >= 0 ? 'Puntos ganados' : 'Puntos usados') }}<span>{{ $m->created_at?->format('d/m/Y') }}</span></div>
                <b class="{{ $m->puntos >= 0 ? 'mas' : 'men' }}">{{ $m->puntos >= 0 ? '+' : '' }}{{ number_format($m->puntos) }}</b>
            </div>
        @empty
            <div class="vacio">Aún no tienes movimientos. Con tu próxima compra empiezas a sumar.</div>
        @endforelse
    </div>

    <div class="pie">
        @if ($programa['puntos_por_sol'] > 0)
            Ganas {{ rtrim(rtrim(number_format($programa['puntos_por_sol'], 2), '0'), '.') }} {{ $programa['puntos_por_sol'] == 1 ? 'punto' : 'puntos' }} por cada S/ 1 de compra.<br>
        @endif
        Guarda esta página en tu inicio para tenerla siempre a mano.
    </div>
</div>
</body>
</html>
