<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prueba gratis · GestionaHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Mismo diseño que el login (panel de marca a la izquierda y formulario a la derecha) */
        :root{--blue:#2B6EF6;--blue2:#1E56D6;--cta:#F47B20;--fg:#1A2332;--fg2:#4A5568;--fg3:#94A3B8;--border:#E2E7EF;--surface2:#F4F6F9;--red:#DC2626;--redl:#FEE2E2;--ease:cubic-bezier(.22,1,.36,1);}
        *{box-sizing:border-box;margin:0;padding:0;}
        html,body{height:100%;}
        body{font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;color:var(--fg);background:#fff;-webkit-font-smoothing:antialiased;font-size:14px;}
        .wrap{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.1fr);min-height:100%;}
        .brand{position:relative;overflow:hidden;background:linear-gradient(150deg,#2B6EF6 0%,#1E56D6 55%,#173F9E 100%);color:#fff;padding:44px 52px;display:flex;flex-direction:column;}
        .brand::before{content:'';position:absolute;width:520px;height:520px;border-radius:50%;right:-180px;top:-160px;background:radial-gradient(circle,rgba(255,255,255,.14),transparent 65%);}
        .brand::after{content:'';position:absolute;width:420px;height:420px;border-radius:50%;left:-160px;bottom:-200px;background:radial-gradient(circle,rgba(244,123,32,.28),transparent 65%);}
        .logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:19px;letter-spacing:-.4px;position:relative;z-index:1;color:#fff;text-decoration:none;}
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
        .side{display:flex;align-items:center;justify-content:center;padding:36px 24px;background:#fff;}
        .card{width:100%;max-width:460px;animation:up .5s var(--ease) backwards;}
        .card h2{font-size:24px;font-weight:800;letter-spacing:-.6px;margin-bottom:6px;}
        .sub{color:var(--fg2);margin-bottom:22px;}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 12px;}
        .fg{margin-bottom:14px;}
        .fl{display:block;font-size:12px;font-weight:600;color:var(--fg2);margin-bottom:6px;}
        .inp{width:100%;height:44px;border:1px solid var(--border);border-radius:12px;padding:0 14px;font:inherit;font-size:14px;color:var(--fg);background:#fff;transition:border-color .15s,box-shadow .15s;outline:none;}
        select.inp{padding-right:8px;}
        .inp:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(43,110,246,.15);}
        .inp.err{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,38,.10);}
        .ferr{color:var(--red);font-size:12px;margin-top:6px;}
        .chk{display:flex;align-items:flex-start;gap:8px;font-size:13px;color:var(--fg2);margin:4px 0 18px;cursor:pointer;}
        .chk a{color:var(--blue);font-weight:600;}
        .chk input{width:16px;height:16px;margin-top:1px;accent-color:var(--blue);flex:none;}
        .btn{width:100%;height:46px;border:none;border-radius:9px;background:var(--cta);color:#fff;font:inherit;font-weight:700;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:filter .15s,transform .15s;box-shadow:0 6px 18px rgba(244,123,32,.28);}
        .btn:hover{filter:brightness(.95);transform:translateY(-1px);}
        .btn:disabled{opacity:.55;cursor:not-allowed;transform:none;}
        .preview{background:#FFF7ED;border:1px solid #FED7AA;color:#9A3412;border-radius:10px;padding:10px 12px;font-size:13px;line-height:1.5;margin-bottom:18px;}
        .preview a{color:inherit;font-weight:700;}
        .nota{margin-top:16px;font-size:14px;color:var(--fg2);text-align:center;}
        .nota a{color:var(--blue);font-weight:700;text-decoration:none;}
        .legal{margin-top:20px;font-size:12px;color:var(--fg3);text-align:center;}
        @keyframes up{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
        @media (max-width:900px){ .wrap{grid-template-columns:1fr;} .brand{padding:24px;} .pitch,.brand-foot{display:none;} }
        @media (max-width:520px){ .grid2{grid-template-columns:1fr;} }
        @media (prefers-reduced-motion:reduce){ *{animation:none!important;transition:none!important;} }
    </style>
    @include('publico._gtm')
</head>
<body>
@php $dias = \App\Http\Controllers\RegistroController::DIAS_PRUEBA; @endphp
<div class="wrap">
    <section class="brand">
        <a class="logo" href="{{ route('landing') }}"><span class="logo-ico">G</span> GestionaHub</a>
        <div class="pitch">
            <h1>Empieza hoy, <span>{{ $dias }} días gratis.</span></h1>
            <p>Crea la cuenta de tu negocio en un minuto. Sin tarjeta y sin compromiso: si te gusta, eliges tu plan al terminar la prueba.</p>
            <div class="feats">
                <div class="feat"><i><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></i> Todos los módulos activos durante la prueba</div>
                <div class="feat"><i><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></i> Sin tarjeta de crédito</div>
                <div class="feat"><i><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg></i> Menú y ejemplos listos según tu rubro</div>
                <div class="feat"><i><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></i> Tienda online, caja y WhatsApp en un solo panel</div>
            </div>
        </div>
        <div class="brand-foot">© {{ date('Y') }} GestionaHub · Intelecta Lab</div>
    </section>

    <section class="side">
        <div class="card">
            <h2>Prueba gratis {{ $dias }} días</h2>
            <p class="sub">Sin tarjeta. Crea tu cuenta y empieza a vender hoy.</p>

            @auth
                <div class="preview">Estás viendo esta página como vista previa porque tienes la sesión iniciada. Para crear otra cuenta, <a href="{{ route('admin.dashboard') }}">vuelve al panel</a> y cierra sesión.</div>
            @else
                @include('auth._google', ['textoGoogle' => 'Crear cuenta con Google'])
            @endauth
            <form method="POST" action="{{ route('registro.guardar') }}" novalidate>
                @csrf
                <div class="grid2">
                    <div class="fg">
                        <label class="fl" for="nombre_negocio">Nombre del negocio</label>
                        <input class="inp @error('nombre_negocio') err @enderror" id="nombre_negocio" name="nombre_negocio" maxlength="120" value="{{ old('nombre_negocio') }}" placeholder="Ej.: Pollería El Sol" required>
                        @error('nombre_negocio') <div class="ferr">{{ $message }}</div> @enderror
                    </div>
                    <div class="fg">
                        <label class="fl" for="nombre">Tu nombre</label>
                        <input class="inp @error('nombre') err @enderror" id="nombre" name="nombre" maxlength="120" value="{{ old('nombre') }}" required>
                        @error('nombre') <div class="ferr">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="fg">
                    <label class="fl" for="email">Correo</label>
                    <input class="inp @error('email') err @enderror" id="email" name="email" type="email" maxlength="150" value="{{ old('email') }}" placeholder="tucorreo@ejemplo.com" required>
                    @error('email') <div class="ferr">{{ $message }}</div> @enderror
                </div>

                <div class="grid2">
                    <div class="fg">
                        <label class="fl" for="password">Contraseña (mín. 8)</label>
                        <input class="inp @error('password') err @enderror" id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
                        @error('password') <div class="ferr">{{ $message }}</div> @enderror
                    </div>
                    <div class="fg">
                        <label class="fl" for="password_confirmation">Repite la contraseña</label>
                        <input class="inp" id="password_confirmation" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
                    </div>
                </div>

                <div class="grid2">
                    <div class="fg">
                        <label class="fl" for="rubro">Rubro de tu negocio</label>
                        <select class="inp @error('rubro') err @enderror" id="rubro" name="rubro" required>
                            <option value="">Elige uno</option>
                            @foreach (\App\Support\PlantillasRubro::CATALOGO as $k => $p)
                                <option value="{{ $k }}" @selected(old('rubro') === $k)>{{ $p['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('rubro') <div class="ferr">{{ $message }}</div> @enderror
                    </div>
                    <div class="fg">
                        <label class="fl" for="plan">Plan después de la prueba</label>
                        <select class="inp @error('plan') err @enderror" id="plan" name="plan" required>
                            @foreach ($planes as $p)
                                <option value="{{ $p->slug }}" @selected(old('plan', $plan) === $p->slug)>{{ $p->nombre }} · S/ {{ number_format($p->precio_mensual, 2) }}/mes</option>
                            @endforeach
                        </select>
                        @error('plan') <div class="ferr">{{ $message }}</div> @enderror
                    </div>
                </div>

                @php $emp = \App\Models\LandingContenido::todo()['empresa'] ?? []; @endphp
                <label class="chk"><input type="checkbox" name="terminos" value="1" required> <span>Acepto los @if (! empty($emp['terminos_url']))<a href="{{ $emp['terminos_url'] }}" target="_blank" rel="noopener">términos</a>@else términos @endif y la @if (! empty($emp['privacidad_url']))<a href="{{ $emp['privacidad_url'] }}" target="_blank" rel="noopener">política de privacidad</a>@else política de privacidad @endif.</span></label>
                @error('terminos') <div class="ferr" style="margin:-12px 0 14px;">{{ $message }}</div> @enderror

                <button class="btn" type="submit" @auth disabled title="Cierra sesión para crear otra cuenta" @endauth>Empezar prueba gratis</button>
            </form>
            <p class="nota">¿Ya tienes cuenta? <a href="{{ url('/login') }}">Ingresar</a></p>
            <p class="legal">Conexión segura · Tus datos están protegidos</p>
        </div>
    </section>
</div>
</body>
</html>
