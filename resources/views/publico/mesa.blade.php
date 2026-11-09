<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mesa->nombre }} Â· {{ $negocio }}</title>
    <style>
        :root { --brand:#2B6EF6; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:520px; margin:0 auto; padding:20px 16px 90px; }
        h1 { font-size:20px; margin:0 0 2px; }
        .sub { font-size:13px; color:var(--fg3); margin-bottom:14px; }
        .prod { display:flex; align-items:center; justify-content:space-between; gap:10px; background:#fff; border:1px solid var(--border); border-radius:14px; padding:12px; margin-bottom:8px; animation: subir .35s ease both; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) .prod { background:#111A2B; } }
        @keyframes subir { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform:none; } }
        .nombre { font-weight:600; font-size:15px; }
        .precio { font-size:13px; color:var(--fg3); }
        .cant { width:64px; padding:8px; border:1px solid var(--border); border-radius:10px; font:inherit; text-align:center; }
        .ok { background:#DCFCE7; color:#15803D; border-radius:10px; padding:12px; font-size:14px; margin-bottom:12px; }
        .barra { position:fixed; left:0; right:0; bottom:0; background:var(--bg); border-top:1px solid var(--border); padding:12px 16px; }
        .barra .inner { max-width:520px; margin:0 auto; }
        textarea { width:100%; box-sizing:border-box; padding:10px; border:1px solid var(--border); border-radius:10px; font:inherit; background:#fff; color:#1A2332; margin-bottom:8px; }
        button { width:100%; padding:13px; border:0; border-radius:12px; background:var(--brand); color:#fff; font:inherit; font-weight:700; cursor:pointer; }
        @media (prefers-reduced-motion: reduce) { .prod { animation:none; } }
    </style>
</head>
<body>
<div class="wrap">
    <h1>{{ $mesa->nombre }}</h1>
    <div class="sub">{{ $negocio }} Â· Elige lo que quieres y envíalo a cocina</div>

    @if ($enviada)
        <div class="ok">{{ $enviada }}</div>
    @endif

    @if ($productos->isEmpty())
        <p style="font-size:14px;color:var(--fg2);">Por ahora no hay productos disponibles. Pide ayuda al personal.</p>
    @else
        <form method="POST" action="{{ route('publico.mesa.comanda', $mesa->token) }}" id="pedido">
            @csrf
            @foreach ($productos as $i => $p)
                <div class="prod" style="animation-delay: {{ $i * 25 }}ms">
                    <div>
                        <div class="nombre">{{ $p->nombre }}</div>
                        <div class="precio">S/ {{ number_format($p->precio_oferta ?? $p->precio, 2) }}</div>
                    </div>
                    <input type="hidden" name="items[{{ $i }}][producto_id]" value="{{ $p->id }}">
                    <input class="cant" type="number" name="items[{{ $i }}][cantidad]" min="0" max="{{ min(20, (int) $p->stock) }}" value="0" aria-label="Cantidad de {{ $p->nombre }}">
                </div>
            @endforeach

            <div class="barra">
                <div class="inner">
                    <textarea name="notas" rows="1" maxlength="255" placeholder="Notas (sin cebolla, bien cocidoâ€¦)"></textarea>
                    <button type="submit">Enviar a cocina</button>
                </div>
            </div>
        </form>
    @endif
</div>
</body>
</html>
