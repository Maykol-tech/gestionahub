<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editor de la landing · GestionaHub</title>
    <style>
        :root { --brand:#2B6EF6; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:960px; margin:0 auto; padding:20px 16px 60px; }
        .panel { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:18px; margin-bottom:14px; animation: subir .4s ease both; }
        @keyframes subir { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform:none; } }
        h1 { font-size:22px; margin:0 0 4px; }
        h2 { font-size:16px; margin:0 0 10px; }
        label { display:block; font-size:12px; font-weight:700; color:var(--fg2); margin:10px 0 4px; }
        input, textarea, select { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid var(--border); border-radius:10px; font:inherit; background:var(--card); color:var(--fg); }
        .grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        button { background:var(--brand); color:#fff; border:0; border-radius:10px; padding:11px 18px; font:inherit; font-weight:700; cursor:pointer; margin-top:14px; }
        .ok { color:#15803D; font-weight:700; margin-bottom:10px; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { text-align:left; padding:8px; border-bottom:1px solid var(--border); }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }
        @media (prefers-reduced-motion: reduce) { .panel { animation:none; } }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Editor de la landing</h1>
    <p style="color:var(--fg3);font-size:13px;margin-top:0;">Cambia textos, analítica y popup sin tocar código. <a href="{{ url('/landing') }}">Ver landing</a></p>

    @if ($ok)<div class="ok">{{ $ok }}</div>@endif
    @if ($errors->any())
        <div style="color:#B91C1C;margin-bottom:10px;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('superadmin.landing.guardar') }}">
        @csrf
        <div class="panel">
            <h2>Portada</h2>
            <label for="hero_titulo">Título</label>
            <input id="hero_titulo" name="hero_titulo" maxlength="160" value="{{ old('hero_titulo', $c['hero']['titulo']) }}" required>
            <label for="hero_subtitulo">Subtítulo</label>
            <textarea id="hero_subtitulo" name="hero_subtitulo" rows="2" maxlength="400" required>{{ old('hero_subtitulo', $c['hero']['subtitulo']) }}</textarea>
            <label for="hero_cta">Texto del botón</label>
            <input id="hero_cta" name="hero_cta" maxlength="60" value="{{ old('hero_cta', $c['hero']['cta']) }}" required>
        </div>

        <div class="panel">
            <h2>Beneficios</h2>
            <div class="grid">
                @foreach ($c['beneficios']['items'] as $i => $b)
                    <div>
                        <label>Título {{ $i + 1 }}</label>
                        <input name="beneficio[{{ $i }}][titulo]" maxlength="80" value="{{ $b['titulo'] }}" required>
                        <label>Texto {{ $i + 1 }}</label>
                        <textarea name="beneficio[{{ $i }}][texto]" rows="2" maxlength="250" required>{{ $b['texto'] }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2>Pie y analítica</h2>
            <div class="grid">
                <div><label for="pie_texto">Texto del pie</label><input id="pie_texto" name="pie_texto" maxlength="200" value="{{ $c['pie']['texto'] }}" required></div>
                <div><label for="pie_email">Correo de contacto</label><input id="pie_email" name="pie_email" type="email" maxlength="150" value="{{ $c['pie']['email'] }}"></div>
                <div><label for="gtm_id">ID de Google Tag Manager (GTM-XXXX)</label><input id="gtm_id" name="gtm_id" maxlength="20" value="{{ $c['analitica']['gtm_id'] }}" placeholder="GTM-XXXXXX"></div>
                <div style="display:flex;align-items:end;"><label style="display:flex;gap:6px;align-items:center;margin:0 0 10px;"><input type="checkbox" name="utm_activo" value="1" style="width:auto;" @checked($c['analitica']['utm_activo'])> Guardar UTM en leads</label></div>
            </div>
        </div>

        <div class="panel">
            <h2>Popup de salida</h2>
            <div class="grid">
                <div style="display:flex;align-items:end;"><label style="display:flex;gap:6px;align-items:center;margin:0 0 10px;"><input type="checkbox" name="popup_activo" value="1" style="width:auto;" @checked($c['popup']['activo'])> Mostrar popup</label></div>
                <div><label for="popup_segundos">Segundos antes de mostrarlo</label><input id="popup_segundos" name="popup_segundos" type="number" min="5" max="120" value="{{ $c['popup']['segundos'] }}"></div>
                <div><label for="popup_titulo">Título</label><input id="popup_titulo" name="popup_titulo" maxlength="120" value="{{ $c['popup']['titulo'] }}"></div>
                <div><label for="popup_texto">Texto</label><input id="popup_texto" name="popup_texto" maxlength="250" value="{{ $c['popup']['texto'] }}"></div>
            </div>
        </div>

        <button type="submit">Guardar landing</button>
    </form>

    <div class="panel" style="margin-top:14px;">
        <h2>Leads de la landing ({{ $leads->count() }})</h2>
        <table>
            <thead><tr><th>Nombre</th><th>Contacto</th><th>Origen</th><th>Estado</th></tr></thead>
            <tbody>
                @forelse ($leads as $l)
                    <tr>
                        <td>{{ $l->nombre }}<div style="font-size:11px;color:var(--fg3);">{{ $l->created_at?->format('d/m/Y H:i') }}</div></td>
                        <td>{{ $l->email ?: '”' }}<div style="font-size:11px;color:var(--fg3);">{{ $l->telefono }}</div></td>
                        <td>{{ $l->fuente }}@if (!empty($l->utm['utm_source'])) <div style="font-size:11px;color:var(--fg3);">{{ $l->utm['utm_source'] }} / {{ $l->utm['utm_campaign'] ?? '' }}</div>@endif</td>
                        <td>
                            <form method="POST" action="{{ route('superadmin.landing.lead', $l->id) }}">
                                @csrf
                                <select name="estado" onchange="this.form.submit()" style="width:auto;">
                                    @foreach ($estados as $e)
                                        <option value="{{ $e }}" @selected($l->estado === $e)>{{ ucfirst($e) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="color:var(--fg3);">Aún no hay leads.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
