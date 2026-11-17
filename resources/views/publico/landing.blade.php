<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $contenido['seo']['titulo'] ?? 'GestionaHub · Gestiona tu negocio' }}</title>
    <meta name="description" content="{{ $contenido['seo']['descripcion'] ?? $contenido['hero']['subtitulo'] }}">
    <meta property="og:title" content="{{ $contenido['seo']['titulo'] ?? 'GestionaHub' }}">
    <meta property="og:description" content="{{ $contenido['seo']['descripcion'] ?? $contenido['hero']['subtitulo'] }}">
    <meta name="theme-color" content="#2B6EF6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand:#2B6EF6; --brand2:#7C3AED; --acc:#F47B20; --ok:#16A34A;
            --fg:#0F172A; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#FFFFFF; --bg2:#F6F8FB; --card:#FFFFFF;
            --shadow:0 1px 2px rgba(15,23,42,.04), 0 8px 24px rgba(15,23,42,.06);
            --shadow-lg:0 24px 60px rgba(15,23,42,.14);
        }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#A3B1C6; --fg3:#7C8BA1; --border:#1E293B; --bg:#0B1220; --bg2:#0F1829; --card:#111A2B; --shadow:0 1px 2px rgba(0,0,0,.3), 0 8px 24px rgba(0,0,0,.25); }
        }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { margin:0; background:var(--bg); color:var(--fg); font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height:1.55; -webkit-font-smoothing:antialiased; }
        a { color:var(--brand); }
        img, svg { max-width:100%; }
        .wrap { max-width:1160px; margin:0 auto; padding:0 20px; }
        .ico { width:20px; height:20px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; flex:none; }

        /* Barra superior */
        .nav { position:sticky; top:0; z-index:50; background:color-mix(in srgb, var(--bg) 82%, transparent); backdrop-filter:saturate(1.6) blur(14px); border-bottom:1px solid transparent; transition:border-color .2s, box-shadow .2s; }
        .nav.scrolled { border-color:var(--border); box-shadow:0 4px 20px rgba(15,23,42,.05); }
        .nav .wrap { display:flex; align-items:center; gap:18px; height:66px; }
        .logo { display:flex; align-items:center; gap:9px; font-weight:800; font-size:19px; color:var(--fg); text-decoration:none; letter-spacing:-.02em; }
        .logo-m { width:32px; height:32px; border-radius:9px; background:linear-gradient(135deg, var(--brand), var(--brand2)); display:grid; place-items:center; color:#fff; font-size:15px; box-shadow:0 6px 16px rgba(43,110,246,.35); }
        .nav-links { display:flex; gap:4px; margin-left:auto; }
        .nav-links a { color:var(--fg2); text-decoration:none; font-size:14px; font-weight:500; padding:8px 12px; border-radius:8px; transition:background .15s, color .15s; }
        .nav-links a:hover { background:var(--bg2); color:var(--fg); }
        .nav-cta { display:flex; gap:8px; align-items:center; }
        .menu-btn { display:none; margin-left:auto; background:none; border:1px solid var(--border); border-radius:10px; padding:8px; color:var(--fg); cursor:pointer; }

        /* Botones */
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; background:var(--brand); color:#fff; padding:12px 22px; border-radius:12px; font-weight:700; font-size:15px; text-decoration:none; border:0; cursor:pointer; font-family:inherit; transition:transform .18s ease, box-shadow .18s ease, filter .18s; box-shadow:0 8px 20px rgba(43,110,246,.28); }
        .btn:hover { transform:translateY(-2px); box-shadow:0 12px 28px rgba(43,110,246,.36); }
        .btn:active { transform:translateY(0); }
        .btn-sm { padding:9px 16px; font-size:14px; border-radius:10px; }
        .btn-ghost { background:transparent; color:var(--fg); box-shadow:none; border:1px solid var(--border); }
        .btn-ghost:hover { background:var(--bg2); box-shadow:none; }
        .btn-white { background:#fff; color:var(--brand); box-shadow:0 8px 20px rgba(0,0,0,.15); }

        /* Hero */
        .hero { position:relative; overflow:hidden; padding:72px 0 40px; }
        .hero::before { content:""; position:absolute; inset:-30% -10% auto; height:720px; background:radial-gradient(600px 320px at 20% 20%, rgba(43,110,246,.16), transparent 70%), radial-gradient(520px 300px at 85% 10%, rgba(124,58,237,.14), transparent 70%), radial-gradient(400px 240px at 60% 70%, rgba(244,123,32,.10), transparent 70%); pointer-events:none; animation:flotar 14s ease-in-out infinite alternate; }
        .hero .wrap > * { min-width:0; }
        .btn { white-space:nowrap; }
        .hero .wrap { position:relative; display:grid; grid-template-columns:1.05fr 1fr; gap:48px; align-items:center; }
        .pill { display:inline-flex; align-items:center; gap:8px; background:var(--card); border:1px solid var(--border); border-radius:99px; padding:6px 12px 6px 6px; font-size:13px; font-weight:600; color:var(--fg2); box-shadow:var(--shadow); }
        .pill b { background:linear-gradient(135deg, var(--brand), var(--brand2)); color:#fff; border-radius:99px; padding:2px 9px; font-size:11px; letter-spacing:.03em; }
        .hero h1 { font-size:clamp(34px, 5.2vw, 58px); line-height:1.06; letter-spacing:-.035em; margin:18px 0 16px; font-weight:800; }
        .grad { background:linear-gradient(120deg, var(--brand) 10%, var(--brand2) 60%, var(--acc)); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .hero p.sub { color:var(--fg2); font-size:clamp(16px, 1.6vw, 19px); max-width:540px; margin:0 0 26px; }
        .hero-ctas { display:flex; flex-wrap:wrap; gap:10px; }
        .hero-ok { display:flex; flex-wrap:wrap; gap:16px; margin-top:22px; font-size:13px; color:var(--fg3); }
        .hero-ok span { display:inline-flex; align-items:center; gap:6px; }
        .hero-ok .ico { width:16px; height:16px; color:var(--ok); }

        /* Mockup del panel */
        .mock { position:relative; background:var(--card); border:1px solid var(--border); border-radius:18px; box-shadow:var(--shadow-lg); overflow:hidden; transform:perspective(1400px) rotateY(-8deg) rotateX(4deg); transition:transform .6s ease; }
        .mock:hover { transform:perspective(1400px) rotateY(0) rotateX(0); }
        .mock-top { display:flex; align-items:center; gap:6px; padding:10px 14px; border-bottom:1px solid var(--border); background:var(--bg2); }
        .mock-top i { width:10px; height:10px; border-radius:50%; background:#F87171; } .mock-top i:nth-child(2){background:#FBBF24;} .mock-top i:nth-child(3){background:#34D399;}
        .mock-top span { margin-left:10px; font-size:11px; color:var(--fg3); background:var(--card); border:1px solid var(--border); border-radius:6px; padding:2px 10px; }
        .mock-body { display:grid; grid-template-columns:120px 1fr; min-height:330px; }
        .mock-side { background:linear-gradient(180deg, var(--brand), #1E4FD1); padding:14px 10px; display:flex; flex-direction:column; gap:7px; }
        .mock-side b { color:#fff; font-size:12px; margin-bottom:6px; }
        .mock-side span { height:9px; border-radius:5px; background:rgba(255,255,255,.28); }
        .mock-side span:nth-child(3) { background:rgba(255,255,255,.75); width:80%; }
        .mock-main { padding:14px; display:flex; flex-direction:column; gap:12px; }
        .kpis { display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; }
        .kpi { border:1px solid var(--border); border-radius:10px; padding:9px; }
        .kpi small { display:block; font-size:9px; color:var(--fg3); text-transform:uppercase; letter-spacing:.05em; font-weight:700; }
        .kpi strong { font-size:14px; white-space:nowrap; }
        .kpi em { font-style:normal; font-size:10px; color:var(--ok); font-weight:700; margin-left:4px; }
        .chart { border:1px solid var(--border); border-radius:10px; padding:10px; height:130px; display:flex; align-items:flex-end; gap:6px; }
        .chart i { flex:1; border-radius:5px 5px 2px 2px; background:linear-gradient(180deg, var(--brand), rgba(43,110,246,.35)); transform-origin:bottom; animation:crecer 1.1s cubic-bezier(.2,.8,.2,1) both; }
        .orders { display:grid; gap:6px; }
        .order { display:flex; align-items:center; gap:8px; font-size:11px; border:1px solid var(--border); border-radius:8px; padding:7px 9px; }
        .order .dot { width:8px; height:8px; border-radius:50%; background:var(--acc); }
        .order b { margin-left:auto; }
        .float-card { position:absolute; background:var(--card); border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow-lg); padding:10px 14px; font-size:12px; display:flex; align-items:center; gap:10px; animation:flotarY 5s ease-in-out infinite; }
        .float-card .fi { width:32px; height:32px; border-radius:9px; display:grid; place-items:center; color:#fff; }
        .float-card strong { display:block; font-size:13px; }
        .fc1 { left:-28px; bottom:40px; } .fc1 .fi { background:#22C55E; }
        .fc2 { right:-18px; top:58px; animation-delay:-2.5s; } .fc2 .fi { background:var(--acc); }

        /* Franja de rubros */
        .rubros { padding:28px 0 8px; text-align:center; }
        .rubros p { font-size:13px; color:var(--fg3); font-weight:600; text-transform:uppercase; letter-spacing:.08em; margin:0 0 14px; }
        .rubros-lista { display:flex; flex-wrap:wrap; justify-content:center; gap:10px; }
        .rubros-lista span { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:99px; background:var(--bg2); border:1px solid var(--border); font-size:14px; font-weight:600; color:var(--fg2); transition:transform .2s, border-color .2s; }
        .rubros-lista span:hover { transform:translateY(-2px); border-color:var(--brand); color:var(--brand); }

        /* Secciones */
        section { padding:84px 0; }
        .alt { background:var(--bg2); }
        .sec-hd { text-align:center; max-width:640px; margin:0 auto 44px; }
        .eyebrow { display:inline-block; font-size:12px; font-weight:800; color:var(--brand); text-transform:uppercase; letter-spacing:.1em; margin-bottom:10px; }
        h2 { font-size:clamp(28px, 3.6vw, 40px); line-height:1.12; letter-spacing:-.025em; margin:0 0 12px; }
        .sec-hd p { color:var(--fg2); font-size:17px; margin:0; }

        .features { display:grid; grid-template-columns:repeat(3, 1fr); gap:18px; }
        .feat { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:26px; box-shadow:var(--shadow); transition:transform .25s ease, box-shadow .25s ease, border-color .25s; }
        .feat:hover { transform:translateY(-6px); box-shadow:var(--shadow-lg); border-color:color-mix(in srgb, var(--brand) 35%, var(--border)); }
        .feat-i { width:46px; height:46px; border-radius:13px; display:grid; place-items:center; color:#fff; margin-bottom:16px; background:linear-gradient(135deg, var(--c1, var(--brand)), var(--c2, var(--brand2))); box-shadow:0 8px 18px rgba(43,110,246,.25); }
        .feat h3 { margin:0 0 8px; font-size:18px; letter-spacing:-.01em; }
        .feat p { margin:0; color:var(--fg2); font-size:15px; }

        .pasos { display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; counter-reset:paso; }
        .paso { position:relative; padding:28px 24px; border-radius:18px; background:var(--card); border:1px solid var(--border); }
        .paso::before { counter-increment:paso; content:counter(paso); position:absolute; top:-18px; left:24px; width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg, var(--brand), var(--brand2)); color:#fff; font-weight:800; display:grid; place-items:center; box-shadow:0 8px 18px rgba(43,110,246,.35); }
        .paso h3 { margin:6px 0 6px; font-size:17px; } .paso p { margin:0; color:var(--fg2); font-size:15px; }

        .stats { display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; text-align:center; }
        .stat strong { display:block; font-size:clamp(30px, 4vw, 44px); font-weight:800; letter-spacing:-.03em; }
        .stat span { color:var(--fg2); font-size:14px; }

        /* Precios */
        .toggle { display:inline-flex; gap:4px; padding:4px; border-radius:99px; background:var(--card); border:1px solid var(--border); margin:0 auto 34px; }
        .toggle button { background:none; color:var(--fg2); border:0; border-radius:99px; padding:8px 18px; font:inherit; font-size:14px; font-weight:700; cursor:pointer; transition:background .2s, color .2s; }
        .toggle button.on { background:var(--brand); color:#fff; box-shadow:0 6px 14px rgba(43,110,246,.3); }
        .toggle-wrap { text-align:center; }
        .precios { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px; align-items:stretch; }
        .precio { position:relative; background:var(--card); border:1px solid var(--border); border-radius:20px; padding:30px 26px; display:flex; flex-direction:column; box-shadow:var(--shadow); transition:transform .25s, box-shadow .25s; }
        .precio:hover { transform:translateY(-6px); box-shadow:var(--shadow-lg); }
        .precio.dest { border:2px solid var(--brand); box-shadow:0 20px 50px rgba(43,110,246,.22); }
        .precio .tag { position:absolute; top:-13px; left:50%; transform:translateX(-50%); background:linear-gradient(135deg, var(--brand), var(--brand2)); color:#fff; font-size:12px; font-weight:800; padding:5px 14px; border-radius:99px; white-space:nowrap; }
        .precio h3 { margin:0; font-size:18px; }
        .precio .desc { color:var(--fg3); font-size:14px; margin:4px 0 18px; }
        .precio .monto { font-size:40px; font-weight:800; letter-spacing:-.03em; transition:opacity .2s; }
        .precio .per { font-size:14px; color:var(--fg3); }
        .precio ul { list-style:none; padding:0; margin:22px 0 26px; display:grid; gap:10px; font-size:14px; color:var(--fg2); }
        .precio li { display:flex; gap:9px; align-items:flex-start; }
        .precio li .ico { width:18px; height:18px; color:var(--ok); margin-top:1px; }
        .precio .btn { margin-top:auto; width:100%; }
        .precio:not(.dest) .btn { background:var(--bg2); color:var(--fg); box-shadow:none; border:1px solid var(--border); }
        .precio:not(.dest) .btn:hover { border-color:var(--brand); color:var(--brand); }

        /* Testimonios */
        .testis { display:grid; grid-template-columns:repeat(3, 1fr); gap:18px; }
        .testi { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:24px; box-shadow:var(--shadow); display:flex; flex-direction:column; gap:16px; }
        .stars { color:#F59E0B; letter-spacing:2px; font-size:15px; }
        .testi p { margin:0; font-size:15px; color:var(--fg); }
        .testi-who { display:flex; align-items:center; gap:12px; margin-top:auto; }
        .avatar { width:40px; height:40px; border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:800; font-size:14px; background:linear-gradient(135deg, var(--brand), var(--brand2)); }
        .testi-who strong { display:block; font-size:14px; } .testi-who span { font-size:13px; color:var(--fg3); }

        /* FAQ */
        .faq { max-width:760px; margin:0 auto; display:grid; gap:10px; }
        .faq details { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:0 20px; transition:border-color .2s, box-shadow .2s; }
        .faq details[open] { border-color:color-mix(in srgb, var(--brand) 40%, var(--border)); box-shadow:var(--shadow); }
        .faq summary { cursor:pointer; list-style:none; padding:18px 0; font-weight:700; display:flex; align-items:center; gap:12px; }
        .faq summary::-webkit-details-marker { display:none; }
        .faq summary::after { content:"+"; margin-left:auto; font-size:22px; font-weight:500; color:var(--brand); transition:transform .25s; }
        .faq details[open] summary::after { transform:rotate(45deg); }
        .faq details p { margin:0 0 18px; color:var(--fg2); animation:subir .3s ease both; }

        /* CTA */
        .cta-band { border-radius:26px; padding:56px 40px; text-align:center; color:#fff; position:relative; overflow:hidden; background:linear-gradient(120deg, var(--brand), var(--brand2)); box-shadow:0 30px 60px rgba(43,110,246,.3); }
        .cta-band::after { content:""; position:absolute; width:420px; height:420px; border-radius:50%; background:rgba(255,255,255,.08); right:-120px; top:-160px; }
        .cta-band h2 { color:#fff; } .cta-band p { opacity:.9; font-size:17px; margin:0 0 24px; }

        /* Contacto */
        .contacto-grid { display:grid; grid-template-columns:.9fr 1.1fr; gap:40px; align-items:start; }
        .contacto-info h2 { text-align:left; }
        .contacto-info p { color:var(--fg2); font-size:16px; }
        .info-list { display:grid; gap:14px; margin-top:24px; }
        .info-list div { display:flex; gap:12px; align-items:center; color:var(--fg2); font-size:15px; }
        .info-list .ii { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; background:color-mix(in srgb, var(--brand) 12%, transparent); color:var(--brand); }
        .form-card { background:var(--card); border:1px solid var(--border); border-radius:20px; padding:28px; box-shadow:var(--shadow-lg); }
        form.contacto { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        form.contacto .full { grid-column:1 / -1; }
        .fld label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--fg2); }
        input, textarea, select { width:100%; padding:12px 14px; border:1px solid var(--border); border-radius:11px; font:inherit; font-size:15px; background:var(--bg); color:var(--fg); transition:border-color .15s, box-shadow .15s; }
        input:focus, textarea:focus, select:focus { outline:none; border-color:var(--brand); box-shadow:0 0 0 4px color-mix(in srgb, var(--brand) 18%, transparent); }
        .aviso { display:flex; gap:10px; align-items:center; background:#DCFCE7; color:#166534; border:1px solid #BBF7D0; border-radius:12px; padding:14px 16px; margin:16px auto 0; max-width:1160px; font-weight:600; animation:subir .4s ease both; }
        .error { color:#B91C1C; font-size:13px; margin:0 0 10px; }
        .legal { font-size:12px; color:var(--fg3); }
        .reclamos-box { max-width:760px; margin:0 auto; }
        .reclamos-box details { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:0 22px; }
        .reclamos-box summary { cursor:pointer; list-style:none; padding:18px 0; font-weight:700; display:flex; gap:12px; align-items:center; }
        .reclamos-box summary::-webkit-details-marker { display:none; }
        .reclamos-box form { padding-bottom:22px; }

        footer.pie { border-top:1px solid var(--border); padding:44px 0 36px; }
        .pie-grid { display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr; gap:24px; }
        .pie-grid h4 { margin:0 0 12px; font-size:14px; }
        .pie-grid a { display:block; color:var(--fg2); text-decoration:none; font-size:14px; padding:3px 0; }
        .pie-grid a:hover { color:var(--brand); }
        .pie-bottom { margin-top:30px; padding-top:20px; border-top:1px solid var(--border); display:flex; flex-wrap:wrap; justify-content:space-between; gap:10px; }

        .superbar { background:#0F172A; color:#E2E8F0; font-size:13px; padding:8px 20px; display:flex; justify-content:space-between; gap:10px; position:relative; z-index:60; }
        .superbar a { color:#93C5FD; text-decoration:none; }
        .superbar a:hover { text-decoration:underline; }
        .superbar { flex-wrap:wrap; align-items:center; animation:subir .35s ease both; }
        .sb-l, .sb-r { display:flex; flex-wrap:wrap; gap:14px; align-items:center; }
        .sb-k { background:rgba(255,255,255,.08); border-radius:99px; padding:2px 10px; color:#CBD5E1; }
        .sb-k b { color:#fff; }
        .sb-dot { width:8px; height:8px; border-radius:50%; background:#22C55E; box-shadow:0 0 0 4px rgba(34,197,94,.25); display:inline-block; }
        .superbar button { background:none; border:0; color:#94A3B8; cursor:pointer; font-size:14px; }

        /* Popup y chatbot */
        .popup-bg { position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; padding:16px; z-index:90; }
        .popup-bg.on { display:flex; }
        .popup { background:var(--card); border-radius:20px; padding:26px; max-width:400px; width:100%; animation:zoom .3s cubic-bezier(.2,.8,.2,1) both; box-shadow:var(--shadow-lg); }
        .chat-btn { position:fixed; right:20px; bottom:20px; display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg, var(--brand), var(--brand2)); color:#fff; border:0; border-radius:99px; padding:13px 20px; font:inherit; font-weight:700; box-shadow:0 12px 30px rgba(43,110,246,.4); cursor:pointer; z-index:80; transition:transform .2s; animation:latido 2.6s ease-in-out 3s 3; }
        .chat-btn:hover { transform:translateY(-3px); }
        .chat { position:fixed; right:20px; bottom:82px; width:min(350px, calc(100vw - 40px)); background:var(--card); border:1px solid var(--border); border-radius:18px; box-shadow:var(--shadow-lg); padding:18px; z-index:80; animation:zoom .25s ease both; }
        .chat[hidden] { display:none; }
        .chat-hd { font-weight:800; margin-bottom:10px; }
        .chat-paso p { margin:0 0 8px; color:var(--fg2); font-size:14px; }
        .chat-paso input, .chat-paso select { margin-bottom:8px; padding:10px 12px; font-size:14px; }
        .chat-nav { display:flex; gap:8px; justify-content:flex-end; }
        .chat-nav .btn { padding:9px 16px; font-size:14px; }
        .chat-nav .btn-sec { background:none; border:1px solid var(--border); border-radius:10px; padding:8px 12px; color:var(--fg2); font:inherit; cursor:pointer; }
        .chat-error { color:#B91C1C; font-size:13px; margin-top:6px; }

        /* Animaciones */
        .rv { opacity:0; transform:translateY(26px); transition:opacity .7s cubic-bezier(.2,.8,.2,1), transform .7s cubic-bezier(.2,.8,.2,1); transition-delay:var(--d, 0ms); }
        .rv.in { opacity:1; transform:none; }
        .hero-anim > * { animation:subir .8s cubic-bezier(.2,.8,.2,1) both; }
        .hero-anim > *:nth-child(2) { animation-delay:.08s; } .hero-anim > *:nth-child(3) { animation-delay:.16s; } .hero-anim > *:nth-child(4) { animation-delay:.24s; } .hero-anim > *:nth-child(5) { animation-delay:.32s; }
        @keyframes subir { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:none; } }
        @keyframes zoom { from { opacity:0; transform:scale(.94) translateY(8px); } to { opacity:1; transform:none; } }
        @keyframes crecer { from { transform:scaleY(0); } to { transform:scaleY(1); } }
        @keyframes flotar { from { transform:translate3d(0,0,0); } to { transform:translate3d(-3%, 4%, 0); } }
        @keyframes flotarY { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-10px); } }
        @keyframes latido { 0%,100% { transform:scale(1); } 50% { transform:scale(1.07); } }

        @media (max-width: 960px) {
            .hero .wrap, .contacto-grid { grid-template-columns:1fr; }
            .mock { transform:none; } .fc1 { left:8px; } .fc2 { right:8px; }
            .features, .testis, .pasos { grid-template-columns:1fr 1fr; }
            .stats { grid-template-columns:1fr 1fr; gap:28px; }
            .pie-grid { grid-template-columns:1fr 1fr; }
        }
        @media (max-width: 720px) {
            .nav-links, .nav-cta .btn-ghost { display:none; }
            .nav-links.open { display:flex; flex-direction:column; position:absolute; top:66px; left:0; right:0; background:var(--bg); border-bottom:1px solid var(--border); padding:10px 20px 16px; }
            .menu-btn { display:inline-flex; }
            .nav-cta { margin-left:0; }
            .features, .testis, .pasos { grid-template-columns:1fr; }
            section { padding:64px 0; }
            form.contacto { grid-template-columns:1fr; }
            .mock-body { grid-template-columns:64px 1fr; min-height:0; }
            .mock-side b { display:none; }
            .kpis { grid-template-columns:1fr 1fr; } .kpi:nth-child(3) { display:none; }
            .float-card { display:none; }
            .hero { padding:40px 0 24px; }
            .hero-ctas .btn { flex:1; }
            .chat-btn { padding:12px 16px; font-size:14px; }
            .cta-band { padding:40px 22px; }
            .pie-grid { grid-template-columns:1fr; }
        }
        .secs { display:flex; flex-direction:column; }
        .logos-ttl { text-align:center; color:var(--fg3); font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; margin:0 0 14px; }
        .logos-cinta { overflow:hidden; mask-image:linear-gradient(90deg, transparent, #000 10%, #000 90%, transparent); }
        .logos-pista { display:flex; gap:44px; width:max-content; animation:cinta 32s linear infinite; }
        .logos-cinta:hover .logos-pista { animation-play-state:paused; }
        .logo-item { display:flex; align-items:center; height:44px; color:var(--fg3); font-weight:800; font-size:18px; text-decoration:none; filter:grayscale(1); opacity:.7; transition:filter .2s, opacity .2s; }
        .logo-item:hover { filter:none; opacity:1; }
        .logo-item img { max-height:40px; width:auto; }
        @keyframes cinta { to { transform:translateX(-50%); } }
        .blog-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px; }
        .blog-card { display:flex; flex-direction:column; background:var(--card); border:1px solid var(--border); border-radius:18px; overflow:hidden; text-decoration:none; color:inherit; box-shadow:var(--shadow); transition:transform .25s, box-shadow .25s; }
        .blog-card:hover { transform:translateY(-6px); box-shadow:var(--shadow-lg); }
        .blog-img { aspect-ratio:16/9; background:var(--bg2); display:grid; place-items:center; font-size:34px; overflow:hidden; }
        .blog-img img { width:100%; height:100%; object-fit:cover; transition:transform .5s; }
        .blog-card:hover .blog-img img { transform:scale(1.05); }
        .blog-body { padding:18px; display:flex; flex-direction:column; gap:6px; }
        .blog-body small { color:var(--fg3); font-size:12px; }
        .blog-body p { color:var(--fg2); font-size:14px; margin:0; }
        .blog-mas { color:var(--brand); font-weight:700; font-size:14px; margin-top:4px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation:none !important; transition:none !important; }
            .rv { opacity:1; transform:none; }
        }
    </style>
    @include('publico._gtm')
</head>
<body>
@php $emp = $contenido['empresa'] ?? []; @endphp
@php
    $svg = [
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
        'chat' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'cash' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
        'box' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
        'truck' => '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15zM20 17v5H6.5a2.5 2.5 0 0 1 0-5"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'arrow' => '<path d="M5 12h14M13 5l7 7-7 7"/>',
        'spark' => '<path d="m12 3 1.9 5.8L20 10l-6.1 1.2L12 17l-1.9-5.8L4 10l6.1-1.2z"/>',
    ];
    $ico = fn ($n, $cls = 'ico') => '<svg class="' . $cls . '" viewBox="0 0 24 24" aria-hidden="true">' . $svg[$n] . '</svg>';
    $iconosBeneficio = ['cart', 'chat', 'cash'];
    $extras = [
        ['box', 'Inventario y proveedores', 'Stock por producto e insumo, alertas de mínimo y órdenes de compra que suben el stock al recibirlas.', '#0EA5E9', '#2563EB'],
        ['users', 'Clientes y fidelización', 'Ficha de cada cliente, puntos por compra, niveles y recompensas que hacen que vuelvan.', '#EC4899', '#8B5CF6'],
        ['chart', 'Reportes en tiempo real', 'Ventas, rentabilidad y comparativos con el mes anterior, exportables a Excel.', '#10B981', '#0EA5E9'],
    ];
    $colores = [['#2B6EF6', '#7C3AED'], ['#22C55E', '#16A34A'], ['#F47B20', '#EF4444']];
    $incluye = [
        ['Pedidos, POS y caja', 'Tienda online y catálogo', 'Clientes y fidelización', 'Soporte por WhatsApp'],
        ['Todo lo del plan anterior', 'Boletas y facturas SUNAT', 'Bot de WhatsApp', 'Reportes y rentabilidad'],
        ['Todo lo del plan anterior', 'Producción y recetas', 'Proveedores y compras', 'Asesor dedicado'],
    ];
    $descPlan = ['Para empezar a ordenar tu negocio', 'El favorito de los negocios en crecimiento', 'Para cadenas y negocios con varias sedes'];
    $secs = $contenido['secciones'] ?? [];
    $vis = fn ($c) => $secs[$c]['visible'] ?? true;
    $ord = fn ($c) => (($secs[$c]['orden'] ?? 0) + 1) * 10;
    $tit = fn ($c, $d) => ($secs[$c]['titulo'] ?? null) ?: $d;
    $sub = fn ($c) => $secs[$c]['subtitulo'] ?? null;
    $blog = $blog ?? [];
    $destacado = count($planes) >= 3 ? 1 : (count($planes) - 1);
@endphp

@if (! empty($superadmin))
    {{-- GEST-100: barra del superadmin con accesos y números del día; los visitantes no la ven --}}
    <div class="superbar" x-data id="superbar">
        <span class="sb-l"><span class="sb-dot"></span> <b>Modo superadmin</b>
            <span class="sb-k">Leads hoy <b>{{ $superadmin['leads_hoy'] }}</b></span>
            <a class="sb-k" href="{{ route('superadmin.leads') }}">Sin atender <b>{{ $superadmin['leads_nuevos'] }}</b></a>
            <a class="sb-k" href="{{ route('superadmin.suscripciones') }}">Pagos por revisar <b>{{ $superadmin['pagos_pendientes'] }}</b></a>
            <span class="sb-k">En prueba <b>{{ $superadmin['pruebas'] }}</b></span>
        </span>
        <span class="sb-r">
            <a href="{{ route('superadmin.landing') }}">✏️ Editar landing</a>
            <a href="{{ route('superadmin.leads') }}">Leads</a>
            <a href="{{ url('/superadmin') }}">Panel</a>
            <a href="{{ url('/landing') }}?visitante=1">Ver como visitante</a>
            <button type="button" onclick="document.getElementById('superbar').remove()" aria-label="Ocultar barra">✕</button>
        </span>
    </div>
@endif

<header class="nav" id="nav">
    <div class="wrap">
        <a href="{{ url('/landing') }}" class="logo"><span class="logo-m">G</span>GestionaHub</a>
        <nav class="nav-links" id="nav-links">
            @foreach (['funciones' => 'Funciones', 'como' => 'Cómo funciona', 'precios' => 'Precios', 'blog' => 'Blog', 'preguntas' => 'Preguntas', 'contacto' => 'Contacto'] as $ancla => $txt)
                @if ($vis($ancla) && ($ancla !== 'blog' || count($blog)))<a href="#{{ $ancla }}">{{ $txt }}</a>@endif
            @endforeach
        </nav>
        <div class="nav-cta">
            <a class="btn btn-ghost btn-sm" href="{{ url('/login') }}">Ingresar</a>
            <a class="btn btn-sm" href="{{ route('registro') }}" data-evento="registro_inicio">Prueba gratis</a>
        </div>
        <button type="button" class="menu-btn" id="menu-btn" aria-label="Abrir menú" aria-expanded="false">{!! $ico('menu') !!}</button>
    </div>
</header>

@if (session('ok'))
    <div class="wrap"><div class="aviso">{!! $ico('check') !!} {{ session('ok') }}</div></div>
@endif

<div class="hero">
    <div class="wrap">
        <div class="hero-anim">
            <span class="pill"><b>NUEVO</b> Boletas SUNAT y WhatsApp en el mismo panel</span>
            <h1>{{ $contenido['hero']['titulo'] }}</h1>
            <p class="sub">{{ $contenido['hero']['subtitulo'] }}</p>
            <div class="hero-ctas">
                <a class="btn" href="{{ route('registro') }}" data-evento="registro_inicio">{{ $contenido['hero']['cta'] }} {!! $ico('arrow') !!}</a>
                @if (! empty($emp['video_url']))
                    <a class="btn btn-ghost" href="{{ $emp['video_url'] }}" target="_blank" rel="noopener">Ver demo en video</a>
                @else
                    <a class="btn btn-ghost" href="#contacto">Agendar una demo</a>
                @endif
            </div>
            <div class="hero-ok">
                <span>{!! $ico('check') !!} Sin tarjeta de crédito</span>
                <span>{!! $ico('check') !!} Listo en 5 minutos</span>
                <span>{!! $ico('check') !!} Soporte en español</span>
            </div>
        </div>
        <div class="rv" style="position:relative;">
            <div class="mock" aria-hidden="true">
                <div class="mock-top"><i></i><i></i><i></i><span>app.gestionahub.pe/admin</span></div>
                <div class="mock-body">
                    <div class="mock-side"><b>GestionaHub</b><span></span><span></span><span></span><span></span><span></span><span></span></div>
                    <div class="mock-main">
                        <div class="kpis">
                            <div class="kpi"><small>Ventas hoy</small><strong>S/ 2,480</strong><em>+18%</em></div>
                            <div class="kpi"><small>Pedidos</small><strong>64</strong><em>+9%</em></div>
                            <div class="kpi"><small>Ticket</small><strong>S/ 38.7</strong><em>+4%</em></div>
                        </div>
                        <div class="chart">
                            @foreach ([38, 52, 44, 68, 58, 80, 72, 92, 76, 98] as $k => $h)
                                <i style="height:{{ $h }}%;animation-delay:{{ 300 + $k * 70 }}ms"></i>
                            @endforeach
                        </div>
                        <div class="orders">
                            <div class="order"><span class="dot"></span>#1042 · Delivery · Miraflores<b>S/ 54.90</b></div>
                            <div class="order"><span class="dot" style="background:#22C55E"></span>#1041 · Mesa 4 · Pagado<b>S/ 86.00</b></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="float-card fc1">{!! '<span class="fi">' . $ico('check') . '</span>' !!}<div><strong>Boleta B001-245</strong>Aceptada por SUNAT</div></div>
            <div class="float-card fc2">{!! '<span class="fi">' . $ico('chat') . '</span>' !!}<div><strong>Nuevo pedido</strong>por WhatsApp</div></div>
        </div>
    </div>
</div>

<div class="rubros wrap rv">
    <p>Pensado para negocios peruanos</p>
    <div class="rubros-lista">
        @foreach (['🍗 Restaurantes', '🛒 Bodegas y minimarkets', '💇 Salones y spas', '☕ Cafeterías', '🥖 Panaderías', '🏪 Tiendas', '🏋️ Gimnasios'] as $r)
            <span>{{ $r }}</span>
        @endforeach
    </div>
</div>

<main class="secs">
@if ($vis('logos') && count($contenido['logos'] ?? []))
<section id="logos" style="order:{{ $ord('logos') }};padding:28px 0 8px;">
    <div class="wrap">
        <p class="logos-ttl rv">{{ $tit('logos', 'Negocios que ya confían en GestionaHub') }}</p>
        <x-landing.logos :logos="$contenido['logos']" />
    </div>
</section>
@endif

@if ($vis('funciones'))
<section id="funciones" style="order:{{ $ord('funciones') }}">
    <div class="wrap">
        <div class="sec-hd rv">
            <span class="eyebrow">Funciones</span>
            <h2>Todo tu negocio, <span class="grad">en un solo lugar</span></h2>
            <p>Deja los cuadernos y las hojas de cálculo. Cada área de tu negocio conectada y al día.</p>
        </div>
        <div class="features">
            @foreach ($contenido['beneficios']['items'] as $i => $b)
                <div class="feat rv" style="--d:{{ $i * 90 }}ms;--c1:{{ $colores[$i % 3][0] }};--c2:{{ $colores[$i % 3][1] }}">
                    <div class="feat-i">{!! $ico($iconosBeneficio[$i % 3]) !!}</div>
                    <h3>{{ $b['titulo'] }}</h3>
                    <p>{{ $b['texto'] }}</p>
                </div>
            @endforeach
            @foreach ($extras as $i => $e)
                <div class="feat rv" style="--d:{{ ($i + 3) * 90 }}ms;--c1:{{ $e[3] }};--c2:{{ $e[4] }}">
                    <div class="feat-i">{!! $ico($e[0]) !!}</div>
                    <h3>{{ $e[1] }}</h3>
                    <p>{{ $e[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if ($vis('como'))
<section id="como" class="alt" style="order:{{ $ord('como') }}">
    <div class="wrap">
        <div class="sec-hd rv">
            <span class="eyebrow">Cómo funciona</span>
            <h2>Empieza a vender hoy mismo</h2>
            <p>Sin instalaciones ni técnicos. Configuras tu negocio en minutos.</p>
        </div>
        <div class="pasos">
            <div class="paso rv" style="--d:0ms"><h3>Crea tu cuenta</h3><p>Elige tu rubro y te preparamos el panel con los módulos que necesitas.</p></div>
            <div class="paso rv" style="--d:120ms"><h3>Carga tus productos</h3><p>Sube tu catálogo o impórtalo desde Excel, con precios, variantes y stock.</p></div>
            <div class="paso rv" style="--d:240ms"><h3>Vende y cobra</h3><p>Recibe pedidos por la web, el POS y WhatsApp, y emite tus comprobantes.</p></div>
        </div>
        {{-- GEST-101: cifras reales, editables en el superadmin (ocultas hasta que se activen) --}}
        @if (! empty($contenido['cifras']['mostrar']))
        <div class="stats rv" style="margin-top:64px;">
            @foreach ($contenido['cifras']['items'] as $cf)
                @continue(empty($cf['texto']))
                <div class="stat"><strong data-contar="{{ (int) $cf['valor'] }}" data-sufijo="{{ $cf['sufijo'] }}">0</strong><span>{{ $cf['texto'] }}</span></div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

@if ($vis('precios'))
<section id="precios" style="order:{{ $ord('precios') }}">
    <div class="wrap">
        <div class="sec-hd rv">
            <span class="eyebrow">Precios</span>
            <h2>Planes simples, sin sorpresas</h2>
            <p>Prueba cualquier plan gratis por 14 días. Cambia o cancela cuando quieras.</p>
        </div>
        <div class="toggle-wrap rv">
            <div class="toggle" role="group" aria-label="Periodo de cobro">
                <button type="button" class="on" data-periodo="mensual">Mensual</button>
                <button type="button" data-periodo="anual">Anual (2 meses gratis)</button>
            </div>
        </div>
        <div class="precios">
            @forelse ($planes as $i => $p)
                <div class="precio rv {{ $i === $destacado ? 'dest' : '' }}" style="--d:{{ $i * 100 }}ms">
                    @if ($i === $destacado)<span class="tag">Más elegido</span>@endif
                    <h3>{{ $p['nombre'] }}</h3>
                    <div class="desc">{{ $descPlan[min($i, 2)] }}</div>
                    <div><span class="monto" data-mensual="S/ {{ number_format($p['mensual'], 2) }}" data-anual="S/ {{ number_format($p['anual'], 2) }}">S/ {{ number_format($p['mensual'], 2) }}</span></div>
                    <div class="per" data-mensual-txt="por mes" data-anual-txt="por año">por mes</div>
                    <ul>
                        <li>{!! $ico('check') !!} Hasta {{ $p['sucursales'] }} {{ $p['sucursales'] === 1 ? 'sucursal' : 'sucursales' }}</li>
                        @foreach ($incluye[min($i, 2)] as $f)
                            <li>{!! $ico('check') !!} {{ $f }}</li>
                        @endforeach
                    </ul>
                    <a class="btn" href="{{ route('registro', ['plan' => $p['slug']]) }}" data-evento="plan_elegido" data-plan="{{ $p['slug'] }}">Elegir {{ $p['nombre'] }}</a>
                </div>
            @empty
                <p class="legal" style="text-align:center;grid-column:1/-1;">Pronto publicaremos nuestros planes.</p>
            @endforelse
        </div>
    </div>
</section>
@endif

@if ($vis('testimonios') && count($contenido['testimonios']['items'] ?? []))
<x-landing.seccion id="testimonios" :alt="true" :orden="$ord('testimonios')">
    <x-landing.encabezado eyebrow="Testimonios" :titulo="$tit('testimonios', 'Negocios que ya trabajan tranquilos')" :subtitulo="$sub('testimonios')" />
    <div class="testis">
        @foreach ($contenido['testimonios']['items'] as $i => $t)
            <x-landing.testimonio :t="$t" :i="$i" />
        @endforeach
    </div>
</x-landing.seccion>
@endif

@if ($vis('blog') && count($blog))
<x-landing.seccion id="blog" :orden="$ord('blog')">
    <x-landing.encabezado eyebrow="Blog" :titulo="$tit('blog', $contenido['blog']['titulo'] ?? 'Consejos para tu negocio')" :subtitulo="$sub('blog')" />
    <div class="blog-grid">
        @foreach ($blog as $i => $p)
            <x-landing.entrada-blog :p="$p" :i="$i" />
        @endforeach
    </div>
</x-landing.seccion>
@endif

@if ($vis('preguntas'))
<x-landing.seccion id="preguntas" :orden="$ord('preguntas')">
    <x-landing.encabezado eyebrow="Preguntas frecuentes" :titulo="$tit('preguntas', '¿Tienes dudas?')" :subtitulo="$sub('preguntas')" />
    <div class="faq">
        @foreach (($contenido['faq']['items'] ?? []) as $i => $f)
            <x-landing.pregunta :f="$f" :i="$i" />
        @endforeach
    </div>
</x-landing.seccion>
@endif

<section style="padding-top:0;order:{{ $ord('contacto') - 1 }}">
    <div class="wrap">
        <div class="cta-band rv">
            <h2>¿Listo para ordenar tu negocio?</h2>
            <p>Crea tu cuenta en minutos y prueba todo gratis durante 14 días.</p>
            <a class="btn btn-white" href="{{ route('registro') }}" data-evento="registro_inicio">{{ $contenido['hero']['cta'] }} {!! $ico('arrow') !!}</a>
        </div>
    </div>
</section>

@if ($vis('contacto'))
<section id="contacto" class="alt" style="order:{{ $ord('contacto') }}">
    <div class="wrap contacto-grid">
        <div class="contacto-info rv">
            <span class="eyebrow">Contacto</span>
            <h2>Hablemos de tu negocio</h2>
            <p>Déjanos tus datos y un asesor te escribe por WhatsApp para mostrarte GestionaHub con tus propios productos.</p>
            <div class="info-list">
                <div><span class="ii">{!! $ico('clock') !!}</span> Respondemos en menos de 1 hora hábil</div>
                <div><span class="ii">{!! $ico('shield') !!}</span> Tus datos están protegidos y no los compartimos</div>
                @if (!empty($contenido['pie']['email']))
                    <div><span class="ii">{!! $ico('mail') !!}</span> <a href="mailto:{{ $contenido['pie']['email'] }}">{{ $contenido['pie']['email'] }}</a></div>
                @endif
</main>
            </div>
        </div>
        <div class="form-card rv" style="--d:120ms">
            @error('email') <div class="error">{{ $message }}</div> @enderror
            <form class="contacto" method="POST" action="{{ route('landing.contacto') }}" data-lead-form>
                @csrf
                <div class="fld full"><label for="ct-nombre">Nombre *</label><input id="ct-nombre" name="nombre" placeholder="Tu nombre" required maxlength="150" value="{{ old('nombre') }}"></div>
                <div class="fld"><label for="ct-email">Correo</label><input id="ct-email" name="email" type="email" placeholder="tu@correo.com" maxlength="150" value="{{ old('email') }}"></div>
                <div class="fld"><label for="ct-tel">WhatsApp</label><input id="ct-tel" name="telefono" placeholder="987 654 321" maxlength="30" value="{{ old('telefono') }}"></div>
                <div class="fld full"><label for="ct-msg">¿Qué negocio tienes?</label><textarea id="ct-msg" name="mensaje" rows="3" placeholder="Ej.: pollería con delivery y 2 locales" maxlength="1000">{{ old('mensaje') }}</textarea></div>
                <input type="hidden" name="utm_source" data-utm="utm_source">
                <input type="hidden" name="utm_medium" data-utm="utm_medium">
                <input type="hidden" name="utm_campaign" data-utm="utm_campaign">
                <button class="btn full" type="submit">Quiero una demo {!! $ico('arrow') !!}</button>
                <p class="legal full" style="margin:0;text-align:center;">Déjanos al menos un correo o un WhatsApp.</p>
            </form>
        </div>
    </div>
</section>
@endif

<section id="reclamos" style="padding:56px 0;">
    <div class="wrap reclamos-box rv">
        <details @if ($errors->hasAny(['tipo', 'documento', 'detalle'])) open @endif>
            <summary>{!! $ico('book') !!} Libro de reclamaciones</summary>
            <p class="legal">Conforme al Código de Protección y Defensa del Consumidor, puedes registrar un reclamo o una queja. Te responderemos en un plazo máximo de 30 días calendario.@if (! empty($emp['razon_social'])) Proveedor: {{ $emp['razon_social'] }}@if (! empty($emp['ruc'])), RUC {{ $emp['ruc'] }}@endif.@endif</p>
            <form class="contacto" method="POST" action="{{ route('landing.reclamacion') }}">
                @csrf
                <select name="tipo" required aria-label="Tipo">
                    <option value="reclamo">Reclamo</option>
                    <option value="queja">Queja</option>
                </select>
                <select name="documento_tipo" required aria-label="Tipo de documento">
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
        </details>
    </div>
</section>

<footer class="pie">
    <div class="wrap">
        <div class="pie-grid">
            <div>
                <a href="{{ url('/landing') }}" class="logo" style="margin-bottom:10px;"><span class="logo-m">G</span>GestionaHub</a>
                <p class="legal" style="font-size:14px;max-width:300px;">El sistema para vender, cobrar y crecer, hecho en Perú para negocios peruanos.</p>
            </div>
            <div><h4>Producto</h4><a href="#funciones">Funciones</a><a href="#precios">Precios</a><a href="#preguntas">Preguntas</a></div>
            <div><h4>Empresa</h4><a href="#contacto">Contacto</a><a href="{{ url('/login') }}">Ingresar</a><a href="{{ route('registro') }}">Crear cuenta</a></div>
            <div><h4>Legal</h4><a href="#reclamos">Libro de reclamaciones</a>@if (! empty($emp['terminos_url']))<a href="{{ $emp['terminos_url'] }}" target="_blank" rel="noopener">Términos y condiciones</a>@endif @if (! empty($emp['privacidad_url']))<a href="{{ $emp['privacidad_url'] }}" target="_blank" rel="noopener">Política de privacidad</a>@endif @if (! empty($emp['whatsapp']))<a href="https://wa.me/{{ $emp['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp de ventas</a>@endif @if (!empty($contenido['pie']['email']))<a href="mailto:{{ $contenido['pie']['email'] }}">{{ $contenido['pie']['email'] }}</a>@endif</div>
        </div>
        <div class="pie-bottom legal">
            <span>{{ $contenido['pie']['texto'] }}@if (! empty($emp['razon_social'])) · {{ $emp['razon_social'] }}@endif @if (! empty($emp['ruc'])) · RUC {{ $emp['ruc'] }}@endif</span>
            <span>Hecho con ❤ en Perú</span>
        </div>
    </div>
</footer>

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
<button type="button" id="chat-btn" class="chat-btn" aria-expanded="false" aria-controls="chat"><svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Hablar con un asesor</button>
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
    const enviarEvento = (nombre, extra = {}) => { window.dataLayer = window.dataLayer || []; window.dataLayer.push(Object.assign({ event: nombre }, extra)); };

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
            enviarEvento('popup_visto');
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
    // Barra superior con sombra al hacer scroll y menú en celular
    const nav = document.getElementById('nav');
    const alScroll = () => nav.classList.toggle('scrolled', window.scrollY > 8);
    window.addEventListener('scroll', alScroll, { passive: true }); alScroll();
    const menuBtn = document.getElementById('menu-btn');
    const navLinks = document.getElementById('nav-links');
    menuBtn.addEventListener('click', () => {
        const abierto = navLinks.classList.toggle('open');
        menuBtn.setAttribute('aria-expanded', String(abierto));
    });
    navLinks.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => navLinks.classList.remove('open')));

    // Aparición suave al hacer scroll y contadores animados
    const contar = (el) => {
        const fin = Number(el.dataset.contar), suf = el.dataset.sufijo || '', t0 = performance.now(), dur = 1400;
        const paso = (t) => {
            const k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
            el.textContent = Math.round(fin * e).toLocaleString('es-PE') + suf;
            if (k < 1) requestAnimationFrame(paso);
        };
        requestAnimationFrame(paso);
    };
    if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const io = new IntersectionObserver((entradas) => entradas.forEach((en) => {
            if (!en.isIntersecting) return;
            en.target.classList.add('in');
            en.target.querySelectorAll('[data-contar]').forEach(contar);
            io.unobserve(en.target);
        }), { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.rv').forEach((el) => io.observe(el));
    } else {
        document.querySelectorAll('.rv').forEach((el) => el.classList.add('in'));
        document.querySelectorAll('[data-contar]').forEach((el) => el.textContent = el.dataset.contar + (el.dataset.sufijo || ''));
    }

    // Eventos de analítica (GTM): clic en precios e inicio de registro
    document.querySelectorAll('[data-evento]').forEach((el) => el.addEventListener('click', () => enviarEvento(el.dataset.evento, el.dataset.plan ? { plan: el.dataset.plan } : {})));
    document.querySelectorAll('[data-lead-form]').forEach((f) => f.addEventListener('submit', () => enviarEvento('lead_enviado')));
</script>
</body>
</html>
