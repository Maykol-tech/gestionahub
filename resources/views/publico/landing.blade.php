<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GestionaHub Â· Gestiona tu negocio</title>
    <meta name="description" content="{{ $contenido['hero']['subtitulo'] }}">
    <style>
        :root { --brand:#2B6EF6; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height:1.5; }
        a { color:var(--brand); }
        .wrap { max-width:1040px; margin:0 auto; padding:0 16px; }
        header { display:flex; justify-content:space-between; align-items:center; padding:16px 0; }
        .logo { font-weight:800; font-size:18px; }
        .hero { text-align:center; padding:56px 0 40px; animation: subir .5s ease both; }
        .hero h1 { font-size:clamp(28px, 5vw, 44px); line-height:1.15; margin:0 0 12px; }
        .hero p { color:var(--fg2); font-size:18px; max-width:620px; margin:0 auto 22px; }
        .btn { display:inline-block; background:var(--brand); color:#fff; padding:12px 22px; border-radius:12px; font-weight:700; text-decoration:none; border:0; cursor:pointer; font:inherit; transition: transform .15s ease, filter .15s ease; }
        .btn:hover { transform: translateY(-1px); filter: brightness(1.05); }
        .grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; padding:20px 0; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:20px; animation: subir .5s ease both; }
        .card h3 { margin:0 0 6px; font-size:17px; }
        .card p { margin:0; color:var(--fg2); font-size:14px; }
        section { padding:28px 0; }
        h2 { font-size:24px; margin:0 0 14px; text-align:center; }
        .toggle { display:flex; justify-content:center; gap:6px; margin-bottom:16px; }
        .toggle button { background:var(--card); color:var(--fg2); border:1px solid var(--border); border-radius:99px; padding:6px 14px; font:inherit; font-size:13px; font-weight:700; cursor:pointer; }
        .toggle button.on { background:var(--brand); color:#fff; border-color:var(--brand); }
        .precios { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
        .precio { text-align:center; }
        .precio .monto { font-size:30px; font-weight:800; }
        .precio .per { font-size:13px; color:var(--fg3); }
        form.contacto { display:grid; grid-template-columns: 1fr 1fr; gap:10px; max-width:640px; margin:0 auto; }
        form.contacto .full { grid-column:1 / -1; }
        input, textarea, select { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:10px; font:inherit; background:var(--card); color:var(--fg); }
        .aviso { background:#DCFCE7; color:#15803D; border-radius:10px; padding:12px; text-align:center; margin:12px auto; max-width:640px; }
        .error { color:#B91C1C; font-size:13px; text-align:center; }
        .legal { font-size:12px; color:var(--fg3); text-align:center; }
        .superbar { background:#0F172A; color:#E2E8F0; font-size:13px; padding:8px 16px; display:flex; justify-content:space-between; gap:10px; }
        .superbar a { color:#93C5FD; }
        .popup-bg { position:fixed; inset:0; background:rgba(15,23,42,.45); display:none; align-items:center; justify-content:center; padding:16px; z-index:90; }
        .popup-bg.on { display:flex; }
        .popup { background:var(--card); border-radius:16px; padding:22px; max-width:380px; width:100%; animation: subir .3s ease both; }
        @keyframes subir { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
        @media (max-width: 760px) { .grid { grid-template-columns: 1fr; } form.contacto { grid-template-columns: 1fr; } }
        @media (prefers-reduced-motion: reduce) { .card, .hero, .popup { animation:none; } }
    </style>
    @if (!empty($contenido['analitica']['gtm_id']))
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $contenido['analitica']['gtm_id'] }}');</script>
    @endif
</head>
<body>
@auth
    @if (auth()->user()->hasRole('superadmin'))
        <div class="superbar">
            <span>Modo superadmin</span>
            <span><a href="{{ route('superadmin.landing') }}">Editar landing</a> Â· <a href="{{ url('/superadmin') }}">Panel</a></span>
        </div>
    @endif
@endauth

<div class="wrap">
    <header>
        <div class="logo">GestionaHub</div>
        <nav style="display:flex;gap:14px;font-size:14px;">
            <a href="#precios" style="color:var(--fg2);text-decoration:none;">Precios</a>
            <a href="#contacto" style="color:var(--fg2);text-decoration:none;">Contacto</a>
            <a href="{{ url('/login') }}" style="color:var(--fg2);text-decoration:none;">Ingresar</a>
        </nav>
    </header>

    @if (session('ok'))
        <div class="aviso">{{ session('ok') }}</div>
    @endif

    <div class="hero">
        <h1>{{ $contenido['hero']['titulo'] }}</h1>
        <p>{{ $contenido['hero']['subtitulo'] }}</p>
        <a class="btn" href="{{ route('registro') }}">{{ $contenido['hero']['cta'] }}</a>
    </div>

    <div class="grid">
        @foreach ($contenido['beneficios']['items'] as $i => $b)
            <div class="card" style="animation-delay: {{ $i * 80 }}ms">
                <h3>{{ $b['titulo'] }}</h3>
                <p>{{ $b['texto'] }}</p>
            </div>
        @endforeach
    </div>

    <section id="precios">
        <h2>Planes</h2>
        <div class="toggle" role="group" aria-label="Periodo de cobro">
            <button type="button" class="on" data-periodo="mensual">Mensual</button>
            <button type="button" data-periodo="anual">Anual (2 meses gratis)</button>
        </div>
        <div class="precios">
            @forelse ($planes as $p)
                <div class="card precio">
                    <h3>{{ $p['nombre'] }}</h3>
                    <div class="monto" data-mensual="S/ {{ number_format($p['mensual'], 2) }}" data-anual="S/ {{ number_format($p['anual'], 2) }}">S/ {{ number_format($p['mensual'], 2) }}</div>
                    <div class="per" data-mensual-txt="por mes" data-anual-txt="por año">por mes</div>
                    <p style="margin-top:10px;">Hasta {{ $p['sucursales'] }} {{ $p['sucursales'] === 1 ? 'sucursal' : 'sucursales' }}</p>
                    <a class="btn" style="margin-top:12px;" href="{{ route('registro', ['plan' => $p['slug']]) }}">Elegir {{ $p['nombre'] }}</a>
                </div>
            @empty
                <p class="legal">Pronto publicaremos nuestros planes.</p>
            @endforelse
        </div>
    </section>

    <section id="contacto">
        <h2>Hablemos</h2>
        @error('email') <div class="error">{{ $message }}</div> @enderror
        <form class="contacto" method="POST" action="{{ route('landing.contacto') }}">
            @csrf
            <input class="full" name="nombre" placeholder="Tu nombre" required maxlength="150">
            <input name="email" type="email" placeholder="Correo" maxlength="150">
            <input name="telefono" placeholder="WhatsApp" maxlength="30">
            <textarea class="full" name="mensaje" rows="3" placeholder="Â¿Qué negocio tienes?" maxlength="1000"></textarea>
            <input type="hidden" name="utm_source" data-utm="utm_source">
            <input type="hidden" name="utm_medium" data-utm="utm_medium">
            <input type="hidden" name="utm_campaign" data-utm="utm_campaign">
            <button class="btn full" type="submit">Quiero una demo</button>
        </form>
    </section>

    <section id="reclamos">
        <h2>Libro de reclamaciones</h2>
        <p class="legal">Conforme al Código de Protección y Defensa del Consumidor, puedes registrar un reclamo o una queja. Te responderemos en un plazo máximo de 30 días calendario.</p>
        <form class="contacto" method="POST" action="{{ route('landing.reclamacion') }}">
            @csrf
            <select name="tipo" required>
                <option value="reclamo">Reclamo</option>
                <option value="queja">Queja</option>
            </select>
            <select name="documento_tipo" required>
                <option value="DNI">DNI</option>
                <option value="CE">Carné de extranjería</option>
                <option value="RUC">RUC</option>
            </select>
            <input name="nombre" placeholder="Nombre completo" required maxlength="150">
            <input name="documento" placeholder="Número de documento" required maxlength="20">
            <input name="email" type="email" placeholder="Correo" required maxlength="150">
            <input name="telefono" placeholder="Teléfono" maxlength="30">
            <input class="full" name="pedido" placeholder="Producto o servicio contratado" maxlength="255">
            <textarea class="full" name="detalle" rows="4" placeholder="Describe tu reclamo o queja" required maxlength="2000"></textarea>
            <button class="btn full" type="submit">Enviar hoja de reclamación</button>
        </form>
    </section>

    <footer class="legal" style="padding:24px 0 40px;">
        {{ $contenido['pie']['texto'] }}
        @if (!empty($contenido['pie']['email']))<div><a href="mailto:{{ $contenido['pie']['email'] }}">{{ $contenido['pie']['email'] }}</a></div>@endif
    </footer>
</div>

@if (!empty($contenido['popup']['activo']))
<div class="popup-bg" id="popup" role="dialog" aria-modal="true" aria-labelledby="popup-titulo">
    <div class="popup">
        <h3 id="popup-titulo" style="margin:0 0 6px;">{{ $contenido['popup']['titulo'] }}</h3>
        <p style="color:var(--fg2);font-size:14px;margin:0 0 12px;">{{ $contenido['popup']['texto'] }}</p>
        <form method="POST" action="{{ route('landing.contacto') }}" style="display:grid;gap:8px;">
            @csrf
            <input name="nombre" placeholder="Tu nombre" required maxlength="150">
            <input name="email" type="email" placeholder="Correo" required maxlength="150">
            <input type="hidden" name="utm_source" data-utm="utm_source">
            <input type="hidden" name="utm_medium" data-utm="utm_medium">
            <input type="hidden" name="utm_campaign" data-utm="utm_campaign">
            <button class="btn" type="submit">Quiero que me contacten</button>
            <button type="button" data-cerrar style="background:none;border:0;color:var(--fg3);cursor:pointer;">No, gracias</button>
        </form>
    </div>
</div>
@endif

<script>
    // Precios mensual/anual
    document.querySelectorAll('.toggle button').forEach((b) => b.addEventListener('click', () => {
        const periodo = b.dataset.periodo;
        document.querySelectorAll('.toggle button').forEach((x) => x.classList.toggle('on', x === b));
        document.querySelectorAll('.precio .monto').forEach((el) => el.textContent = el.dataset[periodo]);
        document.querySelectorAll('.precio .per').forEach((el) => el.textContent = el.dataset[periodo + 'Txt']);
    }));

    // UTM: se guardan en la URL de entrada y se envian con cada formulario
    const params = new URLSearchParams(location.search);
    document.querySelectorAll('[data-utm]').forEach((el) => {
        const v = params.get(el.dataset.utm);
        if (v) el.value = v.slice(0, 100);
    });

    // Popup por tiempo o al salir de la pagina (una vez por visita)
    const popup = document.getElementById('popup');
    if (popup && !sessionStorage.getItem('gh-popup')) {
        const mostrar = () => { popup.classList.add('on'); sessionStorage.setItem('gh-popup', '1'); };
        setTimeout(mostrar, {{ (int) ($contenido['popup']['segundos'] ?? 20) * 1000 }});
        document.addEventListener('mouseout', (e) => { if (!e.relatedTarget && e.clientY <= 0) mostrar(); });
        popup.querySelector('[data-cerrar]').addEventListener('click', () => popup.classList.remove('on'));
        popup.addEventListener('click', (e) => { if (e.target === popup) popup.classList.remove('on'); });
    }
</script>
</body>
</html>
