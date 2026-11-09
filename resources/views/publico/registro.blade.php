<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prueba gratis Â· GestionaHub</title>
    <style>
        :root { --brand:#2B6EF6; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:480px; margin:0 auto; padding:28px 16px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:22px; animation: subir .4s ease both; }
        @keyframes subir { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
        h1 { font-size:22px; margin:0 0 4px; }
        .sub { font-size:14px; color:var(--fg3); margin-bottom:16px; }
        label { display:block; font-size:12px; font-weight:700; color:var(--fg2); margin:12px 0 4px; }
        input, select { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid var(--border); border-radius:10px; font:inherit; background:var(--card); color:var(--fg); }
        .check { display:flex; gap:8px; align-items:flex-start; font-size:13px; color:var(--fg2); margin-top:12px; }
        .check input { width:auto; margin-top:3px; }
        .err { color:#B91C1C; font-size:12px; margin-top:4px; }
        button { margin-top:16px; width:100%; padding:12px; border:0; border-radius:10px; background:var(--brand); color:#fff; font:inherit; font-weight:700; cursor:pointer; }
        .nota { font-size:12px; color:var(--fg3); margin-top:12px; text-align:center; }
        @media (prefers-reduced-motion: reduce) { .card { animation:none; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Prueba gratis {{ \App\Http\Controllers\RegistroController::DIAS_PRUEBA }} días</h1>
        <div class="sub">Sin tarjeta. Crea tu cuenta y empieza a vender hoy.</div>

        <form method="POST" action="{{ route('registro.guardar') }}" novalidate>
            @csrf
            <label for="nombre_negocio">Nombre del negocio</label>
            <input id="nombre_negocio" name="nombre_negocio" maxlength="120" value="{{ old('nombre_negocio') }}" required>
            @error('nombre_negocio') <div class="err">{{ $message }}</div> @enderror

            <label for="nombre">Tu nombre</label>
            <input id="nombre" name="nombre" maxlength="120" value="{{ old('nombre') }}" required>
            @error('nombre') <div class="err">{{ $message }}</div> @enderror

            <label for="email">Correo</label>
            <input id="email" name="email" type="email" maxlength="150" value="{{ old('email') }}" required>
            @error('email') <div class="err">{{ $message }}</div> @enderror

            <label for="password">Contraseña (mínimo 8 caracteres)</label>
            <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
            @error('password') <div class="err">{{ $message }}</div> @enderror

            <label for="password_confirmation">Repite la contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">

            <label for="plan">Plan que quieres después de la prueba</label>
            <select id="plan" name="plan" required>
                @foreach ($planes as $p)
                    <option value="{{ $p->slug }}" @selected(old('plan', $plan) === $p->slug)>{{ $p->nombre }} Â· S/ {{ number_format($p->precio_mensual, 2) }}/mes</option>
                @endforeach
            </select>
            @error('plan') <div class="err">{{ $message }}</div> @enderror

            <label for="rubro">Rubro de tu negocio</label>
            <select id="rubro" name="rubro" required>
                <option value="">Elige uno</option>
                @foreach (\App\Support\PlantillasRubro::CATALOGO as $k => $p)
                    <option value="{{ $k }}" @selected(old('rubro') === $k)>{{ $p['nombre'] }}</option>
                @endforeach
            </select>
            @error('rubro') <div class="err">{{ $message }}</div> @enderror

            <label class="check"><input type="checkbox" name="terminos" value="1" required> Acepto los términos y la política de privacidad.</label>
            @error('terminos') <div class="err">{{ $message }}</div> @enderror

            <button type="submit">Empezar prueba gratis</button>
        </form>
        <div class="nota">Ya tienes cuenta? <a href="{{ url('/login') }}" style="color:var(--brand);">Ingresar</a></div>
    </div>
</div>
</body>
</html>
