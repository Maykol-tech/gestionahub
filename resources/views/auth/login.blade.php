<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar · GestionaHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--blue:#2B6EF6;--blue2:#1E56D6;--cta:#F47B20;--fg:#1A2332;--fg2:#4A5568;--fg3:#94A3B8;--border:#E2E7EF;--surface2:#F4F6F9;--red:#DC2626;--redl:#FEE2E2;--green:#16A34A;--greenl:#DCFCE7;--ease:cubic-bezier(.22,1,.36,1);}
        *{box-sizing:border-box;margin:0;padding:0;}
        html,body{height:100%;}
        body{font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;color:var(--fg);background:#fff;-webkit-font-smoothing:antialiased;font-size:14px;}
        .wrap{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);min-height:100%;}
        /* Lado de marca */
        .brand{position:relative;overflow:hidden;background:linear-gradient(150deg,#2B6EF6 0%,#1E56D6 55%,#173F9E 100%);color:#fff;padding:44px 52px;display:flex;flex-direction:column;}
        .brand::before{content:'';position:absolute;width:520px;height:520px;border-radius:50%;right:-180px;top:-160px;background:radial-gradient(circle,rgba(255,255,255,.14),transparent 65%);}
        .brand::after{content:'';position:absolute;width:420px;height:420px;border-radius:50%;left:-160px;bottom:-200px;background:radial-gradient(circle,rgba(244,123,32,.28),transparent 65%);}
        .logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:19px;letter-spacing:-.4px;position:relative;z-index:1;}
        .logo-ico{width:34px;height:34px;border-radius:9px;background:#fff;color:var(--blue);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;}
        .pitch{margin:auto 0;max-width:440px;position:relative;z-index:1;animation:up .6s var(--ease) backwards;}
        .pitch h1{font-size:34px;line-height:1.15;font-weight:800;letter-spacing:-1px;margin-bottom:14px;}
        .pitch h1 span{color:#FFC08F;}
        .pitch p{font-size:15px;line-height:1.6;color:rgba(255,255,255,.78);margin-bottom:28px;}
        .feats{display:grid;gap:12px;}
        .feat{display:flex;align-items:center;gap:12px;font-size:14px;color:rgba(255,255,255,.92);animation:up .6s var(--ease) backwards;}
        .feat:nth-child(1){animation-delay:.10s}.feat:nth-child(2){animation-delay:.16s}.feat:nth-child(3){animation-delay:.22s}.feat:nth-child(4){animation-delay:.28s}
        .feat i{width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center;font-style:normal;flex:none;}
        .brand-foot{font-size:12px;color:rgba(255,255,255,.55);position:relative;z-index:1;}
        /* Formulario */
        .side{display:flex;align-items:center;justify-content:center;padding:40px 24px;background:#fff;}
        .card{width:100%;max-width:380px;animation:up .5s var(--ease) backwards;}
        .card h2{font-size:24px;font-weight:800;letter-spacing:-.6px;margin-bottom:6px;}
        .sub{color:var(--fg2);margin-bottom:26px;}
        .fl{display:block;font-size:12px;font-weight:600;color:var(--fg2);margin-bottom:6px;}
        .fg{margin-bottom:16px;}
        .inp-w{position:relative;}
        .inp{width:100%;height:44px;border:1px solid var(--border);border-radius:9px;padding:0 14px;font:inherit;font-size:14px;color:var(--fg);background:#fff;transition:border-color .15s,box-shadow .15s;outline:none;}
        .inp:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(43,110,246,.15);}
        .inp.err{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,38,.10);}
        .eye{position:absolute;right:6px;top:6px;width:32px;height:32px;border:none;background:none;color:var(--fg3);cursor:pointer;border-radius:7px;display:flex;align-items:center;justify-content:center;}
        .eye:hover{background:var(--surface2);color:var(--fg2);}
        .ferr{color:var(--red);font-size:12px;margin-top:6px;}
        .row{display:flex;align-items:center;justify-content:space-between;margin:4px 0 22px;font-size:13px;color:var(--fg2);}
        .chk{display:flex;align-items:center;gap:8px;cursor:pointer;user-select:none;}
        .chk input{width:16px;height:16px;accent-color:var(--blue);}
        .btn{width:100%;height:46px;border:none;border-radius:9px;background:var(--cta);color:#fff;font:inherit;font-weight:700;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:filter .15s,transform .15s,box-shadow .15s;box-shadow:0 6px 18px rgba(244,123,32,.28);}
        .btn:hover{filter:brightness(.95);transform:translateY(-1px);}
        .btn:disabled{opacity:.75;cursor:wait;transform:none;}
        .spin{width:16px;height:16px;border:2px solid rgba(255,255,255,.45);border-top-color:#fff;border-radius:50%;animation:rot .7s linear infinite;display:none;}
        .btn:disabled .spin{display:block;}
        .alert{display:flex;gap:10px;align-items:flex-start;padding:11px 13px;border-radius:9px;font-size:13px;margin-bottom:18px;}
        .alert-err{background:var(--redl);color:#991B1B;}
        .alert-ok{background:var(--greenl);color:#166534;}
        .demo{margin-top:22px;border:1px dashed var(--border);border-radius:10px;padding:12px 14px;font-size:12px;color:var(--fg2);background:#FAFBFC;}
        .demo b{color:var(--fg);}
        .legal{margin-top:26px;font-size:12px;color:var(--fg3);text-align:center;}
        @keyframes up{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
        @keyframes rot{to{transform:rotate(360deg)}}
        @media (max-width:900px){ .wrap{grid-template-columns:1fr;} .brand{padding:24px;min-height:auto;} .pitch,.brand-foot{display:none;} }
        @media (prefers-reduced-motion:reduce){ *{animation:none!important;transition:none!important;} }
    </style>
</head>
<body>
<div class="wrap">
    <section class="brand">
        <div class="logo"><span class="logo-ico">G</span> GestionaHub</div>
        <div class="pitch">
            <h1>Todo tu negocio, <span>en un solo panel.</span></h1>
            <p>Pedidos, productos, clientes, caja y delivery conectados en tiempo real. Diseñado para negocios peruanos que quieren crecer con orden.</p>
            <div class="feats">
                <div class="feat"><i><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/></svg></i> Tablero de pedidos por estado</div>
                <div class="feat"><i><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.3 7L12 12l8.7-5M12 22V12"/></svg></i> Inventario con alertas de stock mínimo</div>
                <div class="feat"><i><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></i> CRM de clientes y fidelización</div>
                <div class="feat"><i><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg></i> Reportes y caja del día</div>
            </div>
        </div>
        <div class="brand-foot">© {{ date('Y') }} GestionaHub · Intelecta Lab</div>
    </section>

    <section class="side">
        <div class="card">
            <h2>Ingresa a tu panel</h2>
            <p class="sub">Administra tu negocio desde cualquier lugar.</p>

            @if (session('estado'))
                <div class="alert alert-ok">{{ session('estado') }}</div>
            @endif
            @if ($errors->has('email'))
                <div class="alert alert-err">{{ $errors->first('email') }}</div>
            @endif

            <form method="POST" action="{{ route('login.ingresar') }}" onsubmit="this.querySelector('button[type=submit]').disabled = true" novalidate>
                @csrf
                <div class="fg">
                    <label class="fl" for="email">Correo electrónico</label>
                    <input class="inp {{ $errors->has('email') ? 'err' : '' }}" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="tucorreo@negocio.pe" autocomplete="username" autofocus required>
                </div>
                <div class="fg">
                    <label class="fl" for="password">Contraseña</label>
                    <div class="inp-w">
                        <input class="inp {{ $errors->has('password') ? 'err' : '' }}" id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required style="padding-right:44px;">
                        <button type="button" class="eye" aria-label="Mostrar contraseña" onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password';"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                    @error('password')<div class="ferr">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <label class="chk"><input type="checkbox" name="recordar" value="1" {{ old('recordar') ? 'checked' : '' }}> Mantener sesión iniciada</label>
                </div>
                <button class="btn" type="submit"><span class="spin"></span> Ingresar</button>
            </form>

            @if (app()->environment('local'))
                <div class="demo">
                    <b>Acceso de demostración</b><br>
                    Correo: <b>admin@gestionahub.pe</b> · Contraseña: <b>Admin2026!</b>
                </div>
            @endif

            <p class="legal">Conexión segura · Tus datos están protegidos</p>
        </div>
    </section>
</div>
</body>
</html>
