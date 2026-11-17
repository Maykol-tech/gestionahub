<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $c['titulo'] }} · {{ $negocio->nombre_negocio }}</title>
    <meta name="description" content="{{ $c['subtitulo'] }}">
    <meta property="og:title" content="{{ $negocio->nombre_negocio }}">
    <meta property="og:description" content="{{ $c['subtitulo'] }}">
    @if ($d['logo_url'])<meta property="og:image" content="{{ $d['logo_url'] }}">@endif
    <style>
        :root { --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--fg); line-height:1.5; }
        a { color:inherit; }
        .wrap { max-width:1080px; margin:0 auto; padding:0 20px; }
        .nav { display:flex; align-items:center; gap:12px; padding:16px 0; }
        .nav img { width:40px; height:40px; border-radius:10px; object-fit:contain; background:#fff; }
        .nav b { font-size:17px; }
        .nav a.cta { margin-left:auto; }
        .btn { display:inline-block; background:var(--brand2); color:#fff; padding:13px 24px; border-radius:12px; font-weight:700; border:0; cursor:pointer; font:inherit; font-weight:700; text-decoration:none; transition: transform .2s, box-shadow .2s, filter .2s; }
        .btn:hover { transform: translateY(-2px); box-shadow:0 10px 24px rgba(0,0,0,.15); filter: brightness(1.05); }
        .btn.sec { background:transparent; color:var(--brand); border:2px solid var(--brand); }
        .hero { padding:56px 0 40px; }
        .hero h1 { font-size:clamp(30px, 5vw, 50px); line-height:1.1; margin:0 0 14px; letter-spacing:-.02em; }
        .hero p { color:var(--fg2); font-size:18px; margin:0 0 24px; max-width:620px; }
        .acciones { display:flex; gap:10px; flex-wrap:wrap; }
        .seccion { padding:40px 0; }
        .seccion h2 { font-size:clamp(22px, 3.4vw, 30px); margin:0 0 6px; text-align:center; }
        .seccion .sub { color:var(--fg2); text-align:center; margin:0 0 22px; }
        .grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:14px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:22px; transition: transform .25s, box-shadow .25s; }
        .card:hover { transform: translateY(-3px); box-shadow:0 14px 30px rgba(15,23,42,.08); }
        .card .num { width:38px; height:38px; border-radius:10px; display:grid; place-items:center; font-weight:800; color:#fff; background:var(--brand); margin-bottom:12px; }
        .card h3 { margin:0 0 6px; font-size:17px; }
        .card p { margin:0; color:var(--fg2); font-size:14px; }
        .contacto { background:var(--card); border:1px solid var(--border); border-radius:20px; padding:28px; display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:center; }
        .contacto form { display:grid; gap:10px; }
        input, textarea { width:100%; padding:12px 13px; border:1px solid var(--border); border-radius:10px; font:inherit; background:var(--bg); color:var(--fg); }
        input:focus, textarea:focus { outline:2px solid var(--brand); outline-offset:1px; }
        .aviso { background:#DCFCE7; color:#15803D; border-radius:10px; padding:12px; text-align:center; margin-bottom:12px; }
        footer { padding:28px 0 36px; color:var(--fg3); font-size:14px; text-align:center; }
        .rev { opacity:0; transform: translateY(14px); transition: opacity .6s ease, transform .6s ease; }
        .rev.in { opacity:1; transform:none; }

        /* Plantilla moderna: portada a dos columnas con degradado */
        .pl-moderna .hero-box { background: linear-gradient(135deg, var(--brand), color-mix(in srgb, var(--brand) 55%, var(--brand2))); color:#fff; border-radius:28px; padding:48px; display:grid; grid-template-columns:1.2fr .8fr; gap:28px; align-items:center; margin-top:8px; }
        .pl-moderna .hero-box p { color:rgba(255,255,255,.9); }
        .pl-moderna .hero-box .btn.sec { color:#fff; border-color:rgba(255,255,255,.7); }
        .pl-moderna .hero-art { aspect-ratio:1; border-radius:24px; background: rgba(255,255,255,.14); display:grid; place-items:center; font-size:90px; box-shadow: inset 0 0 0 1px rgba(255,255,255,.25); }
        .pl-moderna .hero-art img { width:70%; height:70%; object-fit:contain; border-radius:20px; background:#fff; padding:12px; }
        .pl-moderna .card { border-radius:22px; }
        /* Plantilla clásica: portada centrada */
        .pl-clasica .hero { text-align:center; }
        .pl-clasica .hero p { margin-left:auto; margin-right:auto; }
        .pl-clasica .acciones { justify-content:center; }
        .pl-clasica .hero-band { background: color-mix(in srgb, var(--brand) 9%, var(--bg)); border-bottom:1px solid var(--border); }
        /* Plantilla minimal: blanco, títulos con serifa y líneas finas */
        .pl-minimal { --bg:#fff; }
        .pl-minimal .hero { border-bottom:1px solid var(--border); padding:80px 0 56px; }
        .pl-minimal .hero h1 { font-weight:600; font-size:clamp(34px, 6vw, 60px); }
        .pl-minimal .card { border:0; border-top:2px solid var(--brand); border-radius:0; padding:20px 4px; background:transparent; }
        .pl-minimal .card:hover { box-shadow:none; }
        .pl-minimal .card .num { background:transparent; color:var(--brand); width:auto; height:auto; display:block; font-size:13px; letter-spacing:.1em; }
        .pl-minimal .btn { border-radius:0; }
        @media (prefers-color-scheme: dark) { .pl-minimal:not([data-theme="light"]) { --bg:#0B1220; } }

        @media (max-width: 760px) {
            .grid, .contacto, .pl-moderna .hero-box { grid-template-columns:1fr; }
            .pl-moderna .hero-box { padding:28px; }
            .pl-moderna .hero-art { display:none; }
        }
        @media (prefers-reduced-motion: reduce) { .rev { opacity:1; transform:none; transition:none; } .btn, .card { transition:none; } }
    </style>
    @include('publico._diseno_head', ['d' => $d])
</head>
<body class="pl-{{ $d['plantilla'] }}">
@php $wa = preg_replace('/\D/', '', (string) $negocio->telefono); @endphp

<div class="{{ $d['plantilla'] === 'clasica' ? 'hero-band' : '' }}">
    <div class="wrap">
        <nav class="nav">
            @if ($d['logo_url'])<img src="{{ $d['logo_url'] }}" alt="Logo de {{ $negocio->nombre_negocio }}">@endif
            <b>{{ $negocio->nombre_negocio }}</b>
            <a class="btn sec cta" href="#contacto" style="padding:9px 16px;">Contacto</a>
        </nav>

        @if ($d['plantilla'] === 'moderna')
            <div class="hero-box rev">
                <div>
                    <h1>{{ $c['titulo'] }}</h1>
                    <p>{{ $c['subtitulo'] }}</p>
                    <div class="acciones">
                        <a class="btn" href="#contacto">{{ $c['boton'] }}</a>
                        @if ($wa)<a class="btn sec" href="https://wa.me/{{ strlen($wa) === 9 ? '51' . $wa : $wa }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                    </div>
                </div>
                <div class="hero-art">@if ($d['logo_url'])<img src="{{ $d['logo_url'] }}" alt="">@else ✨ @endif</div>
            </div>
        @else
            <header class="hero rev">
                <h1>{{ $c['titulo'] }}</h1>
                <p>{{ $c['subtitulo'] }}</p>
                <div class="acciones">
                    <a class="btn" href="#contacto">{{ $c['boton'] }}</a>
                    @if ($wa)<a class="btn sec" href="https://wa.me/{{ strlen($wa) === 9 ? '51' . $wa : $wa }}" target="_blank" rel="noopener">Escríbenos por WhatsApp</a>@endif
                </div>
            </header>
        @endif
    </div>
</div>

<div class="wrap">
    <section class="seccion">
        <div class="grid">
            @foreach ($c['beneficios'] as $i => $b)
                <div class="card rev" style="transition-delay: {{ $i * 90 }}ms">
                    <div class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                    <h3>{{ $b['titulo'] }}</h3>
                    <p>{{ $b['texto'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    @if ($galeria->isNotEmpty())
        <section class="seccion rev">
            <h2>Nuestros trabajos</h2>
            <p class="sub">Algunas fotos de lo que hacemos</p>
            @include('publico._galeria', ['items' => $galeria])
        </section>
    @endif

    <section class="seccion" id="contacto">
        <div class="contacto rev">
            <div>
                <h2 style="text-align:left;">Hablemos</h2>
                <p style="color:var(--fg2);margin:0 0 10px;">Déjanos tus datos y te escribimos por WhatsApp.</p>
                @if ($negocio->direccion)<p style="color:var(--fg3);margin:0;">📍 {{ $negocio->direccion }}</p>@endif
                @if ($negocio->telefono)<p style="color:var(--fg3);margin:4px 0 0;">📱 {{ $negocio->telefono }}</p>@endif
            </div>
            <div>
                @if ($enviada)<div class="aviso">{{ $enviada }}</div>@endif
                @if ($preview ?? false)
                    <form onsubmit="return false"><input placeholder="Tu nombre"><input placeholder="Tu WhatsApp"><button class="btn" type="button">Enviar</button></form>
                @else
                    <form method="POST" action="{{ route('publico.landing_negocio.contacto', $negocio->slug) }}">
                        @csrf
                        <input name="nombre" placeholder="Tu nombre" required maxlength="150" aria-label="Tu nombre">
                        <input name="telefono" placeholder="Tu WhatsApp" required maxlength="30" aria-label="Tu WhatsApp">
                        <textarea name="mensaje" rows="3" placeholder="¿En qué te podemos ayudar?" maxlength="500" aria-label="Mensaje"></textarea>
                        <button class="btn" type="submit">Enviar</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</div>

<footer>
    <div class="wrap">
        {{ $d['pie_texto'] ?: '© ' . now()->year . ' ' . $negocio->nombre_negocio . '. Todos los derechos reservados.' }}
    </div>
</footer>
<script>
    (() => {
        const els = document.querySelectorAll('.rev');
        if (!('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('in')); return; }
        const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: .12 });
        els.forEach(e => io.observe(e));
    })();
</script>
</body>
</html>
