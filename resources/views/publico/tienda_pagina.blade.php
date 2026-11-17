@php
    $d = $config['diseno'];
    $neg = $config['negocio'];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $titulo }} · {{ $neg['nombre'] }}</title>
    <meta name="description" content="{{ $titulo }} de la tienda online de {{ $neg['nombre'] }}.">
    <link rel="canonical" href="{{ route('tienda.pagina', [$neg['slug'], $pagina]) }}">
    @if ($pagina === 'preguntas' && is_array($contenido))
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_values(array_map(fn ($f) => ['@type' => 'Question', 'name' => (string) ($f['pregunta'] ?? ''), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) ($f['respuesta'] ?? '')]], array_filter($contenido, fn ($f) => ! empty($f['pregunta']))))], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
    @include('publico._diseno_head', ['d' => $d])
    <style>
        :root { --bg:#F6F7FB; --fg:#111827; --fg2:#4B5563; --fg3:#6B7280; --card:#fff; --border:#E5E7EB; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --bg:#0B1220; --fg:#F3F4F6; --fg2:#D1D5DB; --fg3:#9CA3AF; --card:#111A2B; --border:#1F2A3D; } }
        :root[data-theme="dark"] { --bg:#0B1220; --fg:#F3F4F6; --fg2:#D1D5DB; --fg3:#9CA3AF; --card:#111A2B; --border:#1F2A3D; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--fg); -webkit-font-smoothing:antialiased; }
        .wrap { max-width:760px; margin:0 auto; padding:0 16px; }
        .cab { background:var(--card); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:5; }
        .cab .wrap { display:flex; align-items:center; gap:10px; height:60px; }
        .cab a { color:var(--fg); text-decoration:none; font-weight:700; display:flex; align-items:center; gap:10px; }
        .cab img { width:34px; height:34px; border-radius:10px; object-fit:cover; }
        .volver { margin-left:auto; font-weight:600 !important; color:var(--brand) !important; font-size:14px; }
        h1 { font-size:clamp(24px, 4vw, 32px); margin:28px 0 16px; animation:up .45s ease both; }
        .texto { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:22px; line-height:1.7; color:var(--fg2); white-space:pre-line; animation:up .5s .06s ease both; }
        details { background:var(--card); border:1px solid var(--border); border-radius:14px; margin-bottom:10px; animation:up .45s ease both; }
        summary { list-style:none; cursor:pointer; padding:16px 18px; font-weight:700; display:flex; justify-content:space-between; gap:12px; }
        summary::-webkit-details-marker { display:none; }
        summary::after { content:'+'; font-size:20px; line-height:1; color:var(--brand); transition:transform .25s; }
        details[open] summary::after { transform:rotate(45deg); }
        details p { margin:0; padding:0 18px 16px; color:var(--fg2); line-height:1.6; white-space:pre-line; animation:abre .25s ease; }
        @keyframes up { from { opacity:0; transform:translateY(10px); } }
        @keyframes abre { from { opacity:0; transform:translateY(-4px); } }
        .pie { text-align:center; color:var(--fg3); font-size:13px; padding:30px 0 40px; }
        .pie a { color:var(--fg2); margin:0 8px; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation:none !important; transition:none !important; } }
    </style>
</head>
<body>
<header class="cab">
    <div class="wrap">
        <a href="{{ route('tienda.publica', $neg['slug']) }}">@if ($neg['logo'])<img src="{{ $neg['logo'] }}" alt="">@endif{{ $neg['nombre'] }}</a>
        <a class="volver" href="{{ route('tienda.publica', $neg['slug']) }}">← Volver a la tienda</a>
    </div>
</header>
<main class="wrap">
    <h1>{{ $titulo }}</h1>
    @if ($pagina === 'preguntas')
        @foreach ($contenido as $k => $f)
            @continue(empty($f['pregunta']))
            <details style="animation-delay:{{ min($k, 10) * 50 }}ms" @if ($k === 0) open @endif>
                <summary>{{ $f['pregunta'] }}</summary>
                <p>{{ $f['respuesta'] ?? '' }}</p>
            </details>
        @endforeach
    @else
        <div class="texto">{{ $contenido }}</div>
    @endif
</main>
<footer class="pie wrap">
    @foreach (\App\Models\TiendaContenido::PAGINAS as $clave => $nombre)
        @php $hay = $clave === 'preguntas' ? ! empty($config['contenido']['paginas']['faq']) : ! empty($config['contenido']['paginas'][$clave]); @endphp
        @if ($clave !== $pagina && $hay)<a href="{{ route('tienda.pagina', [$neg['slug'], $clave]) }}">{{ $nombre }}</a>@endif
    @endforeach
    <div style="margin-top:10px;">© {{ now()->year }} {{ $neg['nombre'] }}</div>
</footer>
</body>
</html>
