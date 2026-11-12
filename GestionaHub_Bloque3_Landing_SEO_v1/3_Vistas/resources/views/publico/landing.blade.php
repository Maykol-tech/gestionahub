<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $contenido['seo']['titulo'] ?? 'GestionaHub · Gestiona tu negocio' }}</title>
    <meta name="description" content="{{ $contenido['seo']['descripcion'] ?? $contenido['hero']['subtitulo'] }}">
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
        .chat-btn { position:fixed; right:16px; bottom:16px; background:var(--brand); color:#fff; border:0; border-radius:99px; padding:12px 18px; font:inherit; font-weight:700; box-shadow:0 8px 24px rgba(43,110,246,.35); cursor:pointer; z-index:80; }
        .chat { position:fixed; right:16px; bottom:72px; width:min(340px, calc(100vw - 32px)); background:var(--card); border:1px solid var(--border); border-radius:16px; box-shadow:0 12px 32px rgba(15,23,42,.18); padding:16px; z-index:80; }
        .chat[hidden] { display:none; }
        .chat-hd { font-weight:800; margin-bottom:8px; }
        .chat-paso p { margin:0 0 8px; color:var(--fg2); font-size:14px; }
        .chat-paso input, .chat-paso select { width:100%; margin-bottom:8px; padding:9px 10px; border:1px solid var(--border); border-radius:8px; font:inherit; background:var(--card); color:var(--fg); }
        .chat-nav { display:flex; gap:8px; justify-content:flex-end; }
        .chat-nav .btn-sec { background:none; border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:var(--fg2); font:inherit; cursor:pointer; }
        .chat-error { color:#B91C1C; font-size:13px; margin-top:6px; }
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
            <span><a href="{{ route('superadmin.landing') }}">Editar landing</a> · <a href="{{ url('/superadmin') }}">Panel</a></span>
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
            <textarea class="full" name="mensaje" rows="3" placeholder="¿Qué negocio tienes?" maxlength="1000"></textarea>
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

@php
    $popupCfg = $contenido['popup'] ?? [];
    $popupActivo = !empty($popupCfg['activo']);
@endphp
@if ($popupActivo)
<div class="popup-bg" id="popup" role="dialog" aria-modal="true" aria-labelledby="popup-titulo">
    <div class="popup">
        <div style="font-size:11px;font-weight:700;color:var(--brand);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">Oferta por tiempo limitado</div>
        <h3 id="popup-titulo" style="margin:0 0 6px;">{{ $popupCfg['titulo'] }}</h3>
        <p style="color:var(--fg2);font-size:14px;margin:0 0 12px;">{{ $popupCfg['texto'] }}</p>
        <form method="POST" action="{{ route('landing.contacto') }}" style="display:grid;gap:8px;" data-popup-form>
            @csrf
            <input type="hidden" name="fuente" value="popup">
            <input type="hidden" name="oferta" value="{{ $popupCfg['oferta'] ?? '' }}">
            <input name="nombre" placeholder="Tu nombre" required maxlength="150">
            <input name="email" type="email" placeholder="Correo" required maxlength="150">
            <input type="hidden" name="utm_source" data-utm="utm_source">
            <input type="hidden" name="utm_medium" data-utm="utm_medium">
            <input type="hidden" name="utm_campaign" data-utm="utm_campaign">
            <button class="btn" type="submit">Quiero mi beneficio</button>
            <button type="button" data-cerrar style="background:none;border:0;color:var(--fg3);cursor:pointer;">No, gracias</button>
        </form>
    </div>
</div>
@endif

{{-- Chatbot de captura de leads (GEST-53): pasos guiados que envían el mismo formulario de contacto --}}
<button type="button" id="chat-btn" class="chat-btn" aria-expanded="false" aria-controls="chat">Hablar con un asesor</button>
<div class="chat" id="chat" hidden>
    <div class="chat-hd">Te ayudamos a empezar</div>
    <form method="POST" action="{{ route('landing.contacto') }}" class="chat-form" id="chat-form">
        @csrf
        <input type="hidden" name="fuente" value="chatbot">
        <input type="hidden" name="utm_source" data-utm="utm_source">
        <input type="hidden" name="utm_medium" data-utm="utm_medium">
        <input type="hidden" name="utm_campaign" data-utm="utm_campaign">
        <div class="chat-paso" data-paso="1">
            <p>¿Cómo te llamas?</p>
            <input name="nombre" placeholder="Tu nombre" maxlength="150" aria-label="Tu nombre">
        </div>
        <div class="chat-paso" data-paso="2" hidden>
            <p>¿Dónde te contactamos?</p>
            <input name="email" type="email" placeholder="Correo" maxlength="150" aria-label="Correo">
            <input name="telefono" placeholder="Celular (opcional)" maxlength="30" aria-label="Celular">
        </div>
        <div class="chat-paso" data-paso="3" hidden>
            <p>¿Qué necesitas?</p>
            <select name="mensaje" aria-label="Interés">
                <option value="Quiero probar GestionaHub">Quiero probar GestionaHub</option>
                <option value="Pedidos y cocina">Pedidos y cocina</option>
                <option value="WhatsApp para mi negocio">WhatsApp para mi negocio</option>
                <option value="Caja y comprobantes">Caja y comprobantes</option>
                <option value="Varias sucursales">Varias sucursales</option>
            </select>
        </div>
        <div class="chat-nav">
            <button type="button" class="btn-sec" data-atras hidden>Atrás</button>
            <button type="button" class="btn" data-siguiente>Siguiente</button>
            <button type="submit" class="btn" data-enviar hidden>Enviar</button>
        </div>
        <div class="chat-error" role="alert" hidden></div>
    </form>
</div>

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

    // Storage puede fallar en modo privado: nunca debe romper la landing
    const guardarLocal = (k, v) => { try { localStorage.setItem(k, v); } catch (e) { /* sin storage */ } };
    const leerLocal = (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } };
    const enviarEvento = (nombre) => { if (window.dataLayer) window.dataLayer.push({ event: nombre }); };

    // Popup de oferta (GEST-52): tiempo activo, salida por el borde superior en escritorio,
    // no se repite en 7 dias tras cerrarlo y no aparece si ya dejo sus datos.
    const popup = document.getElementById('popup');
    if (popup) {
        const segundos = {{ (int) ($popupCfg['segundos'] ?? 30) }};
        const diasEntreAvisos = 7;
        const forzar = params.has('popup');
        const yaEnvio = leerLocal('gh-popup-lead') === '1';
        const cerradoEn = Number(leerLocal('gh-popup-cerrado') || 0);
        const enEspera = cerradoEn && (Date.now() - cerradoEn) < diasEntreAvisos * 86400000;
        let mostrado = false;
        let armadoSalida = false;

        const mostrar = () => {
            if (mostrado || document.querySelector('.popup-bg.on') || yaEnvio) return;
            mostrado = true;
            popup.classList.add('on');
            enviarEvento('popup_view');
        };
        const cerrar = () => {
            popup.classList.remove('on');
            guardarLocal('gh-popup-cerrado', String(Date.now()));
        };

        if (!yaEnvio && (!enEspera || forzar)) {
            let activo = 0;
            const tiempo = setInterval(() => {
                if (!document.hidden) activo++;
                if (activo >= (forzar ? 2 : segundos)) { clearInterval(tiempo); mostrar(); }
            }, 1000);

            if (window.matchMedia('(pointer: fine)').matches) {
                setTimeout(() => { armadoSalida = true; }, 8000);
                document.addEventListener('mouseout', (e) => {
                    if (armadoSalida && !e.relatedTarget && e.clientY <= 0) mostrar();
                });
            }
        }

        popup.querySelector('[data-cerrar]').addEventListener('click', cerrar);
        popup.addEventListener('click', (e) => { if (e.target === popup) cerrar(); });
        popup.querySelector('[data-popup-form]').addEventListener('submit', () => guardarLocal('gh-popup-lead', '1'));
    }

    // Chatbot de captura de leads (GEST-53): pasos con validacion basica
    const chatBtn = document.getElementById('chat-btn');
    const chat = document.getElementById('chat');
    if (chatBtn && chat) {
        const form = document.getElementById('chat-form');
        const pasos = [...form.querySelectorAll('.chat-paso')];
        const atras = form.querySelector('[data-atras]');
        const siguiente = form.querySelector('[data-siguiente]');
        const enviar = form.querySelector('[data-enviar]');
        const error = form.querySelector('.chat-error');
        let actual = 0;

        const pintar = () => {
            pasos.forEach((p, i) => p.hidden = i !== actual);
            atras.hidden = actual === 0;
            siguiente.hidden = actual === pasos.length - 1;
            enviar.hidden = actual !== pasos.length - 1;
            error.hidden = true;
        };
        const validarPaso = () => {
            const paso = pasos[actual];
            if (actual === 0 && !paso.querySelector('input[name=nombre]').value.trim()) return 'Escribe tu nombre para continuar.';
            if (actual === 1) {
                const correo = paso.querySelector('input[name=email]').value.trim();
                const tel = paso.querySelector('input[name=telefono]').value.trim();
                if (!correo && !tel) return 'Déjanos un correo o un celular para contactarte.';
            }
            return '';
        };

        chatBtn.addEventListener('click', () => {
            const abierto = chat.hidden;
            chat.hidden = !abierto;
            chatBtn.setAttribute('aria-expanded', String(abierto));
            if (abierto) enviarEvento('chatbot_open');
        });
        siguiente.addEventListener('click', () => {
            const msg = validarPaso();
            if (msg) { error.textContent = msg; error.hidden = false; return; }
            actual = Math.min(actual + 1, pasos.length - 1); pintar();
        });
        atras.addEventListener('click', () => { actual = Math.max(actual - 1, 0); pintar(); });
        form.addEventListener('submit', () => guardarLocal('gh-popup-lead', '1'));
        pintar();
    }
</script>
</body>
</html>
