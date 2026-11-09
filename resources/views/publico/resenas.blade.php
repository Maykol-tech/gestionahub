<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reseñas · {{ $negocio->nombre_negocio }}</title>
    <style>
        :root { --brand:#2B6EF6; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; --star:#F59E0B; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:520px; margin:0 auto; padding:24px 16px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:18px; margin-bottom:12px; animation: subir .4s ease both; }
        @keyframes subir { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform:none; } }
        h1 { font-size:20px; margin:0 0 4px; }
        .promedio { font-size:14px; color:var(--fg2); }
        .estrellas { color:var(--star); letter-spacing:2px; }
        .resena-hd { display:flex; justify-content:space-between; font-size:13px; color:var(--fg2); margin-bottom:6px; }
        .comentario { font-size:14px; margin:0; }
        label { display:block; font-size:12px; font-weight:700; color:var(--fg2); margin:10px 0 4px; }
        input, textarea, select { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid var(--border); border-radius:10px; font:inherit; background:var(--card); color:var(--fg); }
        button { margin-top:14px; width:100%; padding:12px; border:0; border-radius:10px; background:var(--brand); color:#fff; font:inherit; font-weight:700; cursor:pointer; }
        .ok { background:#DCFCE7; color:#15803D; border-radius:10px; padding:12px; font-size:14px; margin-bottom:12px; }
        .err { color:#B91C1C; font-size:12px; margin-top:4px; }
        @media (prefers-reduced-motion: reduce) { .card { animation:none; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>{{ $negocio->nombre_negocio }}</h1>
        @if ($promedio)
            <div class="promedio"><span class="estrellas">{{ str_repeat('★', (int) round($promedio)) }}</span> {{ $promedio }} de 5 · {{ $resenas->count() }} reseñas</div>
        @else
            <div class="promedio">Todavía no hay reseñas publicadas.</div>
        @endif
    </div>

    @if ($enviada)<div class="ok">{{ $enviada }}</div>@endif

    @foreach ($resenas as $r)
        <div class="card">
            <div class="resena-hd">
                <strong>{{ $r->cliente_nombre }}</strong>
                <span class="estrellas">{{ str_repeat('★', $r->calificacion) }}{{ str_repeat('☆', 5 - $r->calificacion) }}</span>
            </div>
            @if ($r->comentario)<p class="comentario">{{ $r->comentario }}</p>@endif
        </div>
    @endforeach

    <div class="card">
        <strong>Déjanos tu reseña</strong>
        <form method="POST" action="{{ route('publico.resenas.enviar', $negocio->slug) }}">
            @csrf
            <label for="cliente_nombre">Tu nombre</label>
            <input id="cliente_nombre" name="cliente_nombre" maxlength="120" required value="{{ old('cliente_nombre') }}">
            @error('cliente_nombre') <div class="err">{{ $message }}</div> @enderror

            <label for="calificacion">Calificación</label>
            <select id="calificacion" name="calificacion" required>
                @for ($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" @selected(old('calificacion') == $i)>{{ $i }} {{ $i === 1 ? 'estrella' : 'estrellas' }}</option>
                @endfor
            </select>

            <label for="comentario">Comentario (opcional)</label>
            <textarea id="comentario" name="comentario" rows="3" maxlength="1000">{{ old('comentario') }}</textarea>
            <button type="submit">Enviar reseña</button>
        </form>
    </div>
</div>
</body>
</html>
