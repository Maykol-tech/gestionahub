{{-- Pagina de error con el estilo de GestionaHub (se usa en produccion con APP_DEBUG=false). --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo') · GestionaHub</title>
    <style>
        :root{--blue:#2B6EF6;--cta:#F47B20;--fg:#1A2332;--fg2:#4A5568;--fg3:#94A3B8;--bg:#F4F6F9;--card:#fff;--border:#E2E7EF;}
        @media (prefers-color-scheme:dark){:root{--fg:#E5E9F0;--fg2:#B4BFCE;--fg3:#7C8798;--bg:#0F1522;--card:#182033;--border:#273149;}}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;background:var(--bg);color:var(--fg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;}
        .card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:36px 30px;max-width:440px;width:100%;text-align:center;box-shadow:0 20px 50px rgba(15,23,42,.08);animation:up .5s cubic-bezier(.22,1,.36,1) backwards;}
        .cod{font-size:64px;font-weight:800;letter-spacing:-2px;line-height:1;background:linear-gradient(135deg,var(--blue),var(--cta));-webkit-background-clip:text;background-clip:text;color:transparent;}
        h1{font-size:20px;margin:14px 0 8px;}
        p{color:var(--fg2);font-size:14px;line-height:1.6;}
        .acc{display:flex;gap:8px;justify-content:center;margin-top:22px;flex-wrap:wrap;}
        a,button{font:inherit;font-weight:700;font-size:14px;border-radius:11px;padding:11px 18px;text-decoration:none;cursor:pointer;border:1px solid var(--border);background:var(--card);color:var(--fg);}
        .p{background:var(--cta);border-color:var(--cta);color:#fff;}
        .logo{font-weight:800;color:var(--blue);margin-bottom:18px;font-size:15px;}
        @keyframes up{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">GestionaHub</div>
        <div class="cod">@yield('codigo')</div>
        <h1>@yield('titulo')</h1>
        <p>@yield('mensaje')</p>
        <div class="acc">@yield('acciones')</div>
    </div>
</body>
</html>
