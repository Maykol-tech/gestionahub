<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reserva de cita · {{ $negocio }}</title>
    <style>
        :root { --brand:#2B6EF6; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:480px; margin:0 auto; padding:24px 16px; }
        .card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:20px; box-shadow:0 10px 30px rgba(26,35,50,.06); animation: subir .4s ease both; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) .card { background:#111A2B; } }
        @keyframes subir { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
        h1 { font-size:20px; margin:0 0 4px; }
        .sub { font-size:13px; color:var(--fg3); margin-bottom:16px; }
        .estado { display:inline-block; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; margin-bottom:14px; }
        .abierto { background:#DCFCE7; color:#15803D; } .cerrado { background:#E2E8F0; color:#475569; }
        label { display:block; font-size:12px; font-weight:700; color:var(--fg2); margin:12px 0 4px; }
        input, select, textarea { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid var(--border); border-radius:10px; font:inherit; background:#fff; color:#1A2332; }
        .campos { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .err { color:#B91C1C; font-size:12px; margin-top:4px; }
        button { margin-top:16px; width:100%; padding:12px; border:0; border-radius:10px; background:var(--brand); color:#fff; font:inherit; font-weight:700; cursor:pointer; transition: transform .15s ease, filter .15s ease; }
        button:hover { transform: translateY(-1px); filter: brightness(1.05); }
        .ok { background:#DCFCE7; color:#15803D; border-radius:10px; padding:12px; font-size:14px; margin-bottom:12px; }
        @media (max-width: 420px) { .campos { grid-template-columns:1fr; } }
        @media (prefers-reduced-motion: reduce) { .card { animation:none; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Reserva tu cita</h1>
        <div class="sub">{{ $negocio }}</div>

        <span class="estado {{ $abierto ? 'abierto' : 'cerrado' }}">{{ $abierto ? 'Abierto ahora' : 'Cerrado ahora · puedes reservar igual' }}</span>

        @if ($enviada)
            <div class="ok">Recibimos tu reserva. Te confirmaremos por WhatsApp.</div>
        @endif

        @if ($servicios->isEmpty())
            <p style="font-size:14px;color:var(--fg2);">Por ahora no hay servicios disponibles para reservar.</p>
        @else
            <form method="POST" action="{{ url('reservar/' . $slug) }}" novalidate>
                @csrf
                <label for="servicio">Servicio</label>
                <select id="servicio" name="servicio_id" required>
                    @foreach ($servicios as $s)
                        <option value="{{ $s->id }}" @selected(old('servicio_id') == $s->id)>{{ $s->nombre }} · S/ {{ number_format($s->precio, 2) }} · {{ $s->duracion_min }} min</option>
                    @endforeach
                </select>
                @error('servicio_id') <div class="err">{{ $message }}</div> @enderror

                <label for="nombre">Tu nombre</label>
                <input id="nombre" name="cliente_nombre" maxlength="120" value="{{ old('cliente_nombre') }}" required>
                @error('cliente_nombre') <div class="err">{{ $message }}</div> @enderror

                <label for="telefono">Tu WhatsApp</label>
                <input id="telefono" name="cliente_telefono" maxlength="30" inputmode="tel" value="{{ old('cliente_telefono') }}" required>
                @error('cliente_telefono') <div class="err">{{ $message }}</div> @enderror

                <div class="campos">
                    <div>
                        <label for="fecha">Fecha</label>
                        <input id="fecha" type="date" name="fecha" min="{{ now()->toDateString() }}" value="{{ old('fecha') }}" required>
                        @error('fecha') <div class="err">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="hora">Hora</label>
                        <input id="hora" type="time" name="hora" value="{{ old('hora') }}" required>
                        @error('hora') <div class="err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <label for="notas">Notas (opcional)</label>
                <textarea id="notas" name="notas" rows="2" maxlength="300">{{ old('notas') }}</textarea>

                <button type="submit">Reservar cita</button>
            </form>
        @endif
    </div>
</div>
</body>
</html>
