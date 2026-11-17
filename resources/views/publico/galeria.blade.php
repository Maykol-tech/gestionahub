<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galería · {{ $negocio->nombre_negocio }}</title>
    <style>
        :root { --brand: {{ $negocio->color_primario ?? '#2B6EF6' }}; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:1100px; margin:0 auto; padding:24px 16px; }
        h1 { font-size:22px; margin:0 0 14px; }
        .grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:12px; }
        figure { margin:0; background:var(--card); border:1px solid var(--border); border-radius:14px; overflow:hidden; animation: subir .4s ease both; }
        figure img { width:100%; height:180px; object-fit:cover; display:block; background:var(--bg); }
        figcaption { padding:10px 12px; }
        figcaption strong { display:block; font-size:14px; }
        figcaption span { font-size:12px; color:var(--fg3); }
        @keyframes subir { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform:none; } }
        @media (prefers-reduced-motion: reduce) { figure { animation:none; } }
    </style>
    @include('publico._diseno_head', ['d' => \App\Support\DisenoNegocio::de($negocio)])
</head>
<body>
<div class="wrap">
    <h1>{{ $negocio->nombre_negocio }} · Trabajos</h1>
    @if ($items->isEmpty())
        <p style="color:var(--fg3);">Pronto publicaremos nuestros trabajos.</p>
    @else
        @include('publico._galeria', ['items' => $items])
    @endif
</div>
</body>
</html>
