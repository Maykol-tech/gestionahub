@php
    $c = $config['contenido'];
    $d = $config['diseno'];
    $neg = $config['negocio'];
    $banners = array_values(array_filter((array) ($c['banners']['items'] ?? []), fn ($b) => ! empty($b['imagen']) || ! empty($b['titulo'])));
    $avisos = array_values(array_filter((array) ($c['avisos']['items'] ?? [])));
    $titulo = $c['portada']['titulo'] ?: $neg['nombre'];
    $subtitulo = $c['portada']['subtitulo'] ?: ($neg['descripcion'] ?: null);
    $paginas = $c['paginas'] ?? [];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seo['titulo'] }}</title>
    <meta name="description" content="{{ $seo['descripcion'] }}">
    <link rel="canonical" href="{{ $seo['url'] }}">
    <meta property="og:type" content="{{ $producto ? 'product' : 'website' }}">
    <meta property="og:title" content="{{ $seo['titulo'] }}">
    <meta property="og:description" content="{{ $seo['descripcion'] }}">
    <meta property="og:url" content="{{ $seo['url'] }}">
    @if ($seo['imagen'])<meta property="og:image" content="{{ $seo['imagen'] }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    @if ($jsonld)<script type="application/ld+json">{!! json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>@endif
    <script type="application/ld+json">{!! json_encode(array_filter(['@context' => 'https://schema.org', '@type' => 'Store', 'name' => $neg['nombre'], 'url' => route('tienda.publica', $neg['slug']), 'telephone' => $neg['telefono'], 'address' => $neg['direccion'], 'image' => $neg['logo']]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @include('publico._diseno_head', ['d' => $d])
    <style>
        :root { --bg:#F6F7FB; --fg:#111827; --fg2:#4B5563; --fg3:#6B7280; --card:#fff; --border:#E5E7EB; --ok:#16A34A; --mal:#DC2626; --sombra:0 1px 2px rgba(16,24,40,.05), 0 6px 16px rgba(16,24,40,.06); --radio:16px; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --bg:#0B1220; --fg:#F3F4F6; --fg2:#D1D5DB; --fg3:#9CA3AF; --card:#111A2B; --border:#1F2A3D; } }
        :root[data-theme="dark"] { --bg:#0B1220; --fg:#F3F4F6; --fg2:#D1D5DB; --fg3:#9CA3AF; --card:#111A2B; --border:#1F2A3D; }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { margin:0; background:var(--bg); color:var(--fg); -webkit-font-smoothing:antialiased; }
        img { max-width:100%; display:block; }
        button { font:inherit; color:inherit; }
        [x-cloak] { display:none !important; }
        .wrap { max-width:1180px; margin:0 auto; padding:0 16px; }
        .muted { color:var(--fg3); } .chico { font-size:12px; }

        /* Cabecera */
        .avisos { background:var(--brand); color:#fff; font-size:13px; font-weight:600; overflow:hidden; white-space:nowrap; }
        .avisos-pista { display:inline-flex; gap:48px; padding:8px 0; animation:cinta 28s linear infinite; }
        @keyframes cinta { from { transform:translateX(0); } to { transform:translateX(-50%); } }
        .cab { position:sticky; top:0; z-index:40; background:color-mix(in srgb, var(--card) 88%, transparent); backdrop-filter:saturate(1.6) blur(12px); border-bottom:1px solid var(--border); }
        .cab .wrap { display:flex; align-items:center; gap:12px; height:64px; }
        .marca { display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit; min-width:0; }
        .marca img, .marca .ini { width:38px; height:38px; border-radius:11px; object-fit:cover; flex:none; }
        .marca .ini { display:grid; place-items:center; background:linear-gradient(135deg, var(--brand), var(--brand2)); color:#fff; font-weight:800; }
        .marca b { font-size:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .buscar { flex:1; position:relative; max-width:420px; margin-left:auto; }
        .buscar input { width:100%; height:42px; border:1px solid var(--border); border-radius:12px; padding:0 14px 0 38px; background:var(--bg); color:var(--fg); font:inherit; font-size:14px; transition:border-color .2s, box-shadow .2s; }
        .buscar input:focus { outline:none; border-color:var(--brand); box-shadow:0 0 0 4px color-mix(in srgb, var(--brand) 16%, transparent); }
        .buscar svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--fg3); }
        .btn-carro { position:relative; display:grid; place-items:center; width:44px; height:44px; border-radius:12px; border:0; background:var(--brand); color:#fff; cursor:pointer; flex:none; transition:transform .2s; }
        .btn-carro:hover { transform:translateY(-1px); }
        .btn-carro .n { position:absolute; top:-6px; right:-6px; min-width:20px; height:20px; padding:0 5px; border-radius:99px; background:var(--brand2); color:#fff; font-size:11px; font-weight:800; display:grid; place-items:center; border:2px solid var(--card); }
        .btn-carro.salto { animation:salto .45s cubic-bezier(.3,1.6,.5,1); }
        @keyframes salto { 40% { transform:scale(1.18); } }

        /* Portada */
        .portada { padding-top:22px; padding-bottom:8px; }
        .estado { display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:700; padding:6px 12px; border-radius:99px; background:#DCFCE7; color:#166534; }
        .estado.cerrado { background:#FEE2E2; color:#991B1B; }
        .estado i { width:8px; height:8px; border-radius:50%; background:currentColor; animation:pulso 1.8s ease-in-out infinite; }
        @keyframes pulso { 50% { opacity:.35; } }
        .portada h1 { font-size:clamp(24px, 4vw, 36px); margin:12px 0 6px; letter-spacing:-.02em; }
        .portada p { color:var(--fg2); margin:0; max-width:640px; }
        .carrusel { position:relative; border-radius:20px; overflow:hidden; margin-top:16px; aspect-ratio:21/8; background:linear-gradient(135deg, var(--brand), var(--brand2)); box-shadow:var(--sombra); }
        .slide { position:absolute; inset:0; opacity:0; transition:opacity .7s ease; display:flex; align-items:flex-end; color:#fff; text-decoration:none; }
        .slide.on { opacity:1; }
        .slide img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
        .slide .txt { position:relative; padding:22px; width:100%; background:linear-gradient(transparent, rgba(0,0,0,.55)); }
        .slide h2 { margin:0; font-size:clamp(18px, 3vw, 28px); }
        .slide p { margin:4px 0 0; opacity:.9; }
        .puntos-c { position:absolute; bottom:10px; right:14px; display:flex; gap:6px; z-index:2; }
        .puntos-c button { width:8px; height:8px; border-radius:99px; border:0; background:rgba(255,255,255,.5); cursor:pointer; padding:0; transition:width .3s, background .3s; }
        .puntos-c button.on { width:22px; background:#fff; }

        /* Categorías */
        .chips { position:sticky; top:64px; z-index:30; background:var(--bg); padding:12px 0; }
        .chips .wrap { display:flex; gap:8px; overflow-x:auto; scrollbar-width:none; }
        .chips .wrap::-webkit-scrollbar { display:none; }
        .chip { flex:none; border:1px solid var(--border); background:var(--card); border-radius:99px; padding:8px 16px; font-size:14px; font-weight:600; cursor:pointer; color:var(--fg2); transition:all .2s; }
        .chip:hover { border-color:var(--brand); color:var(--fg); }
        .chip.on { background:var(--fg); color:var(--bg); border-color:var(--fg); }

        /* Productos */
        .seccion-t { display:flex; align-items:baseline; justify-content:space-between; margin:18px 0 12px; }
        .seccion-t h2 { font-size:20px; margin:0; }
        .grilla { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
        @media (min-width:640px) { .grilla { grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; } }
        @media (min-width:1000px) { .grilla { grid-template-columns:repeat(4, minmax(0, 1fr)); gap:18px; } }
        .card-p { background:var(--card); border:1px solid var(--border); border-radius:var(--radio); overflow:hidden; display:flex; flex-direction:column; box-shadow:var(--sombra); animation:subir .45s cubic-bezier(.2,.8,.2,1) both; animation-delay:var(--d); transition:transform .25s, box-shadow .25s; }
        .card-p:hover { transform:translateY(-4px); box-shadow:0 14px 30px rgba(16,24,40,.12); }
        .card-p.agotado { opacity:.65; }
        @keyframes subir { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
        .card-img { position:relative; aspect-ratio:1/1; background:var(--bg); display:block; overflow:hidden; }
        .card-img img { width:100%; height:100%; object-fit:cover; transition:transform .5s; }
        .card-p:hover .card-img img { transform:scale(1.05); }
        .ph { width:100%; height:100%; display:grid; place-items:center; font-size:42px; font-weight:800; color:#fff; background:linear-gradient(135deg, color-mix(in srgb, var(--brand) 85%, #fff), var(--brand2)); }
        .tag { position:absolute; top:10px; left:10px; font-size:11px; font-weight:800; padding:4px 8px; border-radius:8px; }
        .tag.oferta { background:var(--mal); color:#fff; }
        .tag.agot { background:rgba(17,24,39,.8); color:#fff; left:auto; right:10px; }
        .tag.kilo { background:var(--card); color:var(--fg); top:auto; bottom:10px; }
        .card-body { padding:12px; display:flex; flex-direction:column; gap:8px; flex:1; }
        .card-body h3 { font-size:14px; font-weight:600; margin:0; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .precio { display:flex; align-items:baseline; gap:6px; flex-wrap:wrap; margin-top:auto; }
        .precio b { font-size:16px; font-variant-numeric:tabular-nums; }
        .precio s { color:var(--fg3); font-size:13px; }
        .precio .desde, .precio small { color:var(--fg3); font-size:12px; }
        .precio.grande b { font-size:26px; }
        .btn-add { border:1.5px solid var(--brand); background:transparent; color:var(--brand); border-radius:10px; padding:9px; font-weight:700; cursor:pointer; transition:background .2s, color .2s, transform .15s; }
        .btn-add:hover:not(:disabled) { background:var(--brand); color:#fff; }
        .btn-add:active { transform:scale(.97); }
        .btn-add:disabled { border-color:var(--border); color:var(--fg3); cursor:not-allowed; }
        .sin-res { text-align:center; padding:50px 16px; color:var(--fg3); }

        /* Hojas: ficha y carrito */
        .velo { position:fixed; inset:0; z-index:80; background:rgba(15,23,42,.5); backdrop-filter:blur(3px); display:flex; align-items:flex-end; justify-content:center; animation:aparece .2s ease both; }
        @keyframes aparece { from { opacity:0; } }
        .hoja { background:var(--card); width:100%; max-height:92vh; overflow:auto; border-radius:22px 22px 0 0; padding:20px 18px calc(20px + env(safe-area-inset-bottom)); position:relative; animation:sube .35s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes sube { from { transform:translateY(40px); opacity:.4; } }
        @media (min-width:760px) { .velo { align-items:center; padding:20px; } .hoja { max-width:900px; border-radius:22px; } }
        .x { position:absolute; top:12px; right:12px; width:36px; height:36px; border-radius:50%; border:0; background:var(--bg); cursor:pointer; z-index:2; }
        .ficha-grid { display:grid; gap:18px; }
        @media (min-width:760px) { .ficha-grid { grid-template-columns:1fr 1fr; } }
        .g-main { width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:16px; }
        .g-mini { display:flex; gap:8px; margin-top:8px; overflow-x:auto; }
        .g-mini button { border:2px solid transparent; border-radius:10px; padding:0; width:58px; height:58px; overflow:hidden; cursor:pointer; flex:none; }
        .g-mini button.on { border-color:var(--brand); }
        .g-mini img { width:100%; height:100%; object-fit:cover; }
        .ficha-info h2 { margin:4px 30px 6px 0; font-size:22px; }
        .desc { color:var(--fg2); white-space:pre-line; font-size:14px; }
        .sub { font-weight:700; font-size:14px; margin:16px 0 8px; }
        .opciones { display:flex; flex-wrap:wrap; gap:8px; }
        .op { border:1.5px solid var(--border); background:var(--card); border-radius:12px; padding:8px 12px; cursor:pointer; display:flex; flex-direction:column; align-items:flex-start; gap:2px; transition:border-color .2s, box-shadow .2s, transform .15s; }
        .op small { color:var(--fg3); font-size:12px; }
        .op.on { border-color:var(--brand); box-shadow:0 0 0 3px color-mix(in srgb, var(--brand) 18%, transparent); }
        .op:disabled { opacity:.45; text-decoration:line-through; cursor:not-allowed; }
        .op:active:not(:disabled) { transform:scale(.97); }
        .peso-libre { display:flex; align-items:center; gap:8px; font-size:13px; margin-top:10px; color:var(--fg2); }
        .peso-libre input { width:100px; }
        .comprar { display:flex; gap:10px; margin-top:20px; position:sticky; bottom:0; background:var(--card); padding-top:8px; }
        .stepper { display:inline-flex; align-items:center; border:1px solid var(--border); border-radius:12px; overflow:hidden; flex:none; }
        .stepper button { width:42px; height:46px; border:0; background:transparent; font-size:18px; cursor:pointer; }
        .stepper span { min-width:28px; text-align:center; font-weight:700; }
        .stepper.chico button { width:32px; height:32px; font-size:15px; }
        .btn-main { flex:1; border:0; border-radius:12px; padding:14px; background:var(--brand); color:#fff; font-weight:800; font-size:15px; cursor:pointer; box-shadow:0 8px 20px color-mix(in srgb, var(--brand) 35%, transparent); transition:transform .15s, filter .2s, opacity .2s; }
        .btn-main:hover:not(:disabled) { filter:brightness(1.06); transform:translateY(-1px); }
        .btn-main:disabled { opacity:.55; cursor:not-allowed; box-shadow:none; }
        .btn-sec { border:1px solid var(--border); background:var(--card); border-radius:10px; padding:10px 14px; font-weight:700; cursor:pointer; }
        .btn-link { border:0; background:none; color:var(--brand); font-weight:700; font-size:13px; padding:6px 0; cursor:pointer; text-decoration:none; margin-right:12px; }

        .cajon { background:var(--card); width:100%; max-width:460px; height:100%; margin-left:auto; display:flex; flex-direction:column; animation:entra .35s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes entra { from { transform:translateX(40px); opacity:.4; } }
        @media (max-width:759px) { .velo:has(.cajon) { align-items:stretch; } .cajon { max-width:none; } }
        .cajon-hd { display:flex; align-items:center; gap:8px; padding:14px 16px; border-bottom:1px solid var(--border); position:relative; }
        .cajon-hd strong { flex:1; font-size:17px; }
        .cajon-hd .x { position:static; }
        .volver { border:0; background:var(--bg); width:36px; height:36px; border-radius:50%; cursor:pointer; }
        .pasos { display:flex; gap:6px; padding:10px 16px 0; }
        .pasos span { flex:1; height:4px; border-radius:99px; background:var(--border); transition:background .4s; }
        .pasos span.on { background:var(--brand); }
        .cajon-body { flex:1; overflow:auto; padding:14px 16px; }
        .cajon-ft { border-top:1px solid var(--border); padding:12px 16px calc(14px + env(safe-area-inset-bottom)); background:var(--card); }
        .tot-l { display:flex; justify-content:space-between; font-size:14px; color:var(--fg2); padding:2px 0; font-variant-numeric:tabular-nums; }
        .tot-l.total { font-size:18px; font-weight:800; color:var(--fg); margin:6px 0 10px; }
        .tot-l.verde { color:var(--ok); }
        .cajon-ft .btn-main { width:100%; }
        .linea { display:flex; gap:10px; padding:12px 0; border-bottom:1px solid var(--border); animation:subir .3s ease both; }
        .linea-img { width:58px; height:58px; border-radius:12px; overflow:hidden; background:var(--bg); flex:none; display:grid; place-items:center; font-weight:800; color:var(--fg3); }
        .linea-img img { width:100%; height:100%; object-fit:cover; }
        .linea-txt { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; font-size:14px; }
        .linea-pie { display:flex; align-items:center; justify-content:space-between; margin-top:4px; }
        .quitar { border:0; background:none; color:var(--fg3); cursor:pointer; align-self:flex-start; }
        .vacio { text-align:center; padding:40px 10px; display:flex; flex-direction:column; align-items:center; gap:6px; }
        .vacio-i { font-size:44px; animation:flotar 3s ease-in-out infinite; }
        @keyframes flotar { 50% { transform:translateY(-6px); } }
        .lbl { display:block; font-size:13px; font-weight:600; color:var(--fg2); margin:10px 0 0; }
        .inp { width:100%; margin-top:5px; padding:11px 12px; border:1px solid var(--border); border-radius:11px; background:var(--bg); color:var(--fg); font:inherit; font-size:15px; transition:border-color .2s, box-shadow .2s; }
        .inp:focus { outline:none; border-color:var(--brand); box-shadow:0 0 0 4px color-mix(in srgb, var(--brand) 15%, transparent); }
        .radios { display:grid; gap:8px; }
        .radio { display:flex; align-items:center; gap:10px; border:1.5px solid var(--border); border-radius:14px; padding:12px; cursor:pointer; transition:border-color .2s, background .2s; }
        .radio input { display:none; }
        .radio.on { border-color:var(--brand); background:color-mix(in srgb, var(--brand) 6%, var(--card)); }
        .radio.off { opacity:.5; cursor:not-allowed; }
        .r-ico { font-size:22px; }
        .r-txt { flex:1; display:flex; flex-direction:column; font-size:14px; }
        .r-txt small { color:var(--fg3); font-size:12px; }
        .r-precio { font-weight:700; font-size:13px; }
        .programar { margin-top:14px; }
        .sw { display:flex; align-items:center; gap:8px; font-size:14px; font-weight:600; }
        .cupon { display:flex; gap:8px; } .cupon .inp { margin:0; text-transform:uppercase; }
        .ok-chico { color:var(--ok); font-size:13px; font-weight:600; margin-top:6px; }
        .puntos { display:flex; gap:10px; align-items:flex-start; margin-top:14px; padding:12px; border-radius:12px; background:color-mix(in srgb, var(--brand2) 10%, var(--card)); font-size:14px; cursor:pointer; }
        .puntos > span { display:flex; flex-direction:column; gap:2px; }
        .qr { width:160px; margin:10px auto 0; border-radius:12px; }
        .conf { display:grid; gap:10px; background:var(--bg); border-radius:14px; padding:14px; margin-bottom:12px; }
        .conf div { display:flex; flex-direction:column; font-size:14px; }
        .conf-l { display:flex; justify-content:space-between; font-size:14px; padding:6px 0; border-bottom:1px dashed var(--border); gap:10px; }
        .error { background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; border-radius:12px; padding:10px 12px; font-size:13px; margin-bottom:10px; animation:sacude .4s; }
        @keyframes sacude { 25% { transform:translateX(-4px); } 75% { transform:translateX(4px); } }
        .cajon.sacude { animation:sacude .4s; }
        .toast-t { position:fixed; left:50%; top:76px; transform:translateX(-50%); z-index:90; background:var(--fg); color:var(--bg); padding:10px 16px; border-radius:999px; font-size:14px; font-weight:700; box-shadow:0 10px 24px rgba(0,0,0,.2); animation:toastT 2.2s ease both; pointer-events:none; white-space:nowrap; }
        @keyframes toastT { 0% { opacity:0; transform:translate(-50%, -10px); } 12%, 85% { opacity:1; transform:translate(-50%, 0); } 100% { opacity:0; transform:translate(-50%, -6px); } }
        .btn-add.agregado { background:#16A34A !important; color:#fff !important; transform:scale(1.04); }
        .aviso-ok { background:#DCFCE7; color:#166534; border-radius:12px; padding:10px 12px; font-size:13px; margin-bottom:10px; }

        /* Barra inferior móvil y WhatsApp */
        .barra-movil { position:fixed; left:12px; right:12px; bottom:calc(12px + env(safe-area-inset-bottom)); z-index:50; display:flex; align-items:center; justify-content:space-between; gap:10px; background:var(--fg); color:var(--bg); border:0; border-radius:16px; padding:14px 16px; font-weight:800; box-shadow:0 14px 30px rgba(0,0,0,.25); cursor:pointer; animation:sube .35s cubic-bezier(.2,.8,.2,1) both; }
        .wa { position:fixed; right:16px; bottom:calc(84px + env(safe-area-inset-bottom)); z-index:45; width:54px; height:54px; border-radius:50%; background:#25D366; color:#fff; display:grid; place-items:center; box-shadow:0 10px 24px rgba(37,211,102,.45); transition:transform .2s; }
        .wa:hover { transform:scale(1.07); }
        @media (min-width:900px) { .barra-movil { display:none; } .wa { bottom:24px; } }

        /* Pie */
        .pie { margin-top:50px; padding:36px 0 120px; border-top:1px solid var(--border); background:var(--card); font-size:14px; color:var(--fg2); }
        .pie-g { display:grid; gap:24px; }
        @media (min-width:760px) { .pie-g { grid-template-columns:1.3fr 1fr 1fr; } .pie { padding-bottom:40px; } }
        .pie h4 { color:var(--fg); margin:0 0 10px; font-size:14px; }
        .pie a { color:var(--fg2); text-decoration:none; display:block; padding:3px 0; }
        .pie a:hover { color:var(--brand); }
        .hor { display:flex; justify-content:space-between; max-width:260px; padding:2px 0; }
        .hor.hoy { color:var(--fg); font-weight:700; }
        .redes { display:flex; gap:10px; margin-top:10px; }
        .redes a { width:38px; height:38px; border-radius:10px; border:1px solid var(--border); display:grid !important; place-items:center; padding:0; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation:none !important; transition:none !important; } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
</head>
<body x-data="tienda(@js($catalogo), @js($config), @js([
        'cotizar' => route('tienda.cotizar', $neg['slug']),
        'pedido' => route('tienda.pedido', $neg['slug']),
        'capturar' => route('publico.carrito', $neg['slug']),
        'recuperar' => url('/tienda/' . $neg['slug'] . '/carrito'),
        'base' => route('tienda.publica', $neg['slug']),
    ]), @js($producto['id'] ?? null))" x-init="iniciar()">

@if ($avisos)
    <div class="avisos" role="note"><div class="avisos-pista">@foreach (array_merge($avisos, $avisos, $avisos, $avisos) as $a)<span>✨ {{ $a }}</span>@endforeach</div></div>
@endif

<header class="cab">
    <div class="wrap">
        <a class="marca" href="{{ route('tienda.publica', $neg['slug']) }}">
            @if ($neg['logo'])<img src="{{ $neg['logo'] }}" alt="">@else<span class="ini">{{ mb_substr($neg['nombre'], 0, 1) }}</span>@endif
            <b>{{ $neg['nombre'] }}</b>
        </a>
        <label class="buscar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Buscar productos" x-model.debounce.200ms="q" aria-label="Buscar productos">
        </label>
        <button type="button" class="btn-carro" :class="{ salto }" @click="abrirCarrito()" aria-label="Ver carrito">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
            <span class="n" x-show="unidades()" x-text="unidades()" x-cloak></span>
        </button>
    </div>
</header>

<main>
    <section class="portada wrap">
        @if ($config['abierta'])
            <span class="estado"><i></i> Abierto ahora</span>
        @else
            <span class="estado cerrado"><i></i> Cerrado{{ $config['proxima_apertura'] ? ' · abre ' . $config['proxima_apertura'] : '' }}</span>
        @endif
        <h1>{{ $titulo }}</h1>
        @if ($subtitulo)<p>{{ $subtitulo }}</p>@endif
        @if (! empty($c['bienvenida']['texto']))<p style="margin-top:8px;">{{ $c['bienvenida']['texto'] }}</p>@endif
        @if (! $config['abierta'] && $config['programar'])
            <p class="muted chico" style="margin-top:8px;">Puedes hacer tu pedido ahora y programarlo para cuando abramos.</p>
        @endif

        @if ($banners)
            <div class="carrusel" x-data="{ s: 0, n: {{ count($banners) }} }" x-init="if (n > 1) setInterval(() => s = (s + 1) % n, 5000)">
                @foreach ($banners as $k => $b)
                    <a class="slide" :class="{ on: s === {{ $k }} }" href="{{ $b['enlace'] ?? '#productos' }}" @if (! empty($b['enlace']) && str_starts_with($b['enlace'], 'http')) target="_blank" rel="noopener" @endif>
                        @if (! empty($b['imagen']))<img src="{{ $b['imagen'] }}" alt="{{ $b['titulo'] ?? '' }}" @if ($k > 0) loading="lazy" @endif>@endif
                        @if (! empty($b['titulo']) || ! empty($b['texto']))
                            <div class="txt">@if (! empty($b['titulo']))<h2>{{ $b['titulo'] }}</h2>@endif @if (! empty($b['texto']))<p>{{ $b['texto'] }}</p>@endif</div>
                        @endif
                    </a>
                @endforeach
                @if (count($banners) > 1)
                    <div class="puntos-c">@foreach ($banners as $k => $b)<button type="button" :class="{ on: s === {{ $k }} }" @click.prevent="s = {{ $k }}" aria-label="Banner {{ $k + 1 }}"></button>@endforeach</div>
                @endif
            </div>
        @endif
    </section>

    <nav class="chips" aria-label="Categorías" x-show="cat.categorias.length">
        <div class="wrap">
            <button type="button" class="chip" :class="{ on: categoria === null }" @click="categoria = null">Todo</button>
            <template x-for="c in cat.categorias.filter(c => cat.productos.some(p => p.categoria_id === c.id))" :key="c.id">
                <button type="button" class="chip" :class="{ on: categoria === c.id }" @click="categoria = c.id; window.scrollTo({ top: document.getElementById('productos').offsetTop - 120, behavior: 'smooth' })" x-text="c.nombre"></button>
            </template>
        </div>
    </nav>

    <section class="wrap" id="productos">
        <template x-if="destacados().length && ! q && categoria === null">
            <div>
                <div class="seccion-t"><h2>⭐ Destacados</h2></div>
                <div class="grilla"><template x-for="(p, i) in destacados()" :key="'d' + p.id">@include('components.tienda.tarjeta')</template></div>
            </div>
        </template>
        <div class="seccion-t"><h2 x-text="tituloLista()"></h2><span class="muted chico" x-text="visibles().length + ' productos'"></span></div>
        <div class="grilla"><template x-for="(p, i) in visibles()" :key="p.id">@include('components.tienda.tarjeta')</template></div>
        <div class="sin-res" x-show="! visibles().length" x-cloak>
            <div style="font-size:40px;">🔎</div>
            <p x-text="q ? 'No encontramos “' + q + '”. Prueba con otra palabra.' : 'Pronto tendremos productos aquí.'"></p>
        </div>
    </section>
</main>

<footer class="pie">
    <div class="wrap pie-g">
        <div>
            <h4>{{ $neg['nombre'] }}</h4>
            @if ($neg['direccion'])<div>📍 {{ $neg['direccion'] }}</div>@endif
            @if ($config['whatsapp'])<a href="https://wa.me/{{ $config['whatsapp'] }}" target="_blank" rel="noopener">💬 Escríbenos por WhatsApp</a>@endif
            @if (! empty($c['pie']['email']))<a href="mailto:{{ $c['pie']['email'] }}">✉️ {{ $c['pie']['email'] }}</a>@endif
            @if ($config['redes'])
                <div class="redes">
                    @foreach ($config['redes'] as $red => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($red) }}">{{ ['facebook' => 'f', 'instagram' => '◎', 'tiktok' => '♪'][$red] ?? '•' }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <h4>Horario de atención</h4>
            @foreach ($config['horarios'] as $h)
                <div class="hor {{ (int) $h['dia'] === now()->dayOfWeek ? 'hoy' : '' }}"><span>{{ $h['nombre'] }}</span><span>{{ $h['activo'] ? $h['abre'] . ' – ' . $h['cierra'] : 'Cerrado' }}</span></div>
            @endforeach
        </div>
        <div>
            <h4>Información</h4>
            @if (! empty($paginas['terminos']))<a href="{{ route('tienda.pagina', [$neg['slug'], 'terminos']) }}">Términos y condiciones</a>@endif
            @if (! empty($paginas['politicas']))<a href="{{ route('tienda.pagina', [$neg['slug'], 'politicas']) }}">Cambios y devoluciones</a>@endif
            @if (! empty($paginas['faq']))<a href="{{ route('tienda.pagina', [$neg['slug'], 'preguntas']) }}">Preguntas frecuentes</a>@endif
            @if (\Illuminate\Support\Facades\Route::has('landing.reclamacion'))<a href="{{ url('/landing#reclamos') }}">Libro de reclamaciones</a>@endif
            <div class="muted chico" style="margin-top:12px;">{{ $c['pie']['texto'] ?: '© ' . now()->year . ' ' . $neg['nombre'] }}</div>
        </div>
    </div>
</footer>

@if ($config['whatsapp'])
    <a class="wa" href="https://wa.me/{{ $config['whatsapp'] }}" target="_blank" rel="noopener" aria-label="WhatsApp">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-1.6-.8-2.7-1.5-3.8-3.3-.3-.5.3-.5.8-1.6.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 1.9.8 2.6.9 3.6.7.6-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>
    </a>
@endif

<button type="button" class="barra-movil" x-show="unidades() && ! panel && ! ficha" x-cloak @click="abrirCarrito()">
    <span x-text="unidades() + (unidades() === 1 ? ' producto' : ' productos')"></span><span x-text="'Ver pedido · ' + dinero(subtotalLocal())"></span>
</button>

@include('components.tienda.ficha')
@include('components.tienda.carrito')

@include('publico._tienda_js')
</body>
</html>
