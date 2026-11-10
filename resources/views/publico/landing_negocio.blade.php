<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $c['titulo'] }} · {{ $negocio->nombre_negocio }}</title>
    <meta name="description" content="{{ $c['subtitulo'] }}">
    <style>
        :root { --brand: {{ $negocio->color_primario ?? '#2B6EF6' }}; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:900px; margin:0 auto; padding:0 16px 40px; }
        .hero { text-align:center; padding:56px 0 32px; animation: subir .5s ease both; }
        .hero h1 { font-size:clamp(26px, 5vw, 40px); margin:0 0 10px; }
        .hero p { color:var(--fg2); font-size:17px; margin:0 0 20px; }
        .btn { display:inline-block; background:var(--brand); color:#fff; padding:12px 22px; border-radius:12px; font-weight:700; border:0; cursor:pointer; font:inherit; text-decoration:none; }
        .grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:18px; animation: subir .5s ease both; }
        .card h3 { margin:0 0 6px; font-size:16px; }
        .card p { margin:0; color:var(--fg2); font-size:14px; }
        form { display:grid; gap:10px; max-width:420px; margin:28px auto 0; }
        input, textarea { width:100%; box-sizing:border-box; padding:11px 12px; border:1px solid var(--border); border-radius:10px; font:inherit; background:var(--card); color:var(--fg); }
        .aviso { background:#DCFCE7; color:#15803D; border-radius:10px; padding:12px; text-align:center; margin-top:16px; }
        @keyframes subir { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
        @media (max-width: 720px) { .grid { grid-template-columns: 1fr; } }
        @media (prefers-reduced-motion: reduce) { .card, .hero { animation:none; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <h1>{{ $c['titulo'] }}</h1>
        <p>{{ $c['subtitulo'] }}</p>
        <a class="btn" href="#contacto">{{ $c['boton'] }}</a>
    </div>

    <div class="grid">
        @foreach ($c['beneficios'] as $i => $b)
            <div class="card" style="animation-delay: {{ $i * 80 }}ms">
                <h3>{{ $b['titulo'] }}</h3>
                <p>{{ $b['texto'] }}</p>
            </div>
        @endforeach
    </div>

    <div id="contacto" style="padding-top:28px;">
        @if ($enviada)<div class="aviso">{{ $enviada }}</div>@endif
        <form method="POST" action="{{ route('publico.landing_negocio.contacto', $negocio->slug) }}">
            @csrf
            <input name="nombre" placeholder="Tu nombre" required maxlength="150">
            <input name="telefono" placeholder="Tu WhatsApp" required maxlength="30">
            <textarea name="mensaje" rows="3" placeholder="¿En qué te podemos ayudar?" maxlength="500"></textarea>
            <button class="btn" type="submit">Enviar</button>
        </form>
    </div>
</div>
</body>
</html>
