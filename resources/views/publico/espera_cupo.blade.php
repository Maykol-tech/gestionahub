<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Cupo disponible · {{ $negocio->nombre_negocio }}</title>
    <style>
        :root { --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--fg); }
        .wrap { max-width:460px; margin:0 auto; padding:32px 16px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:20px; padding:26px; box-shadow:0 14px 40px rgba(15,23,42,.08); animation: subir .4s ease both; text-align:center; }
        .logo { width:56px; height:56px; border-radius:14px; object-fit:contain; margin-bottom:8px; }
        h1 { font-size:22px; margin:6px 0; }
        .hora { font-size:34px; font-weight:800; color:var(--brand); margin:14px 0 2px; }
        .muted { color:var(--fg3); font-size:14px; }
        .reloj { display:inline-block; margin-top:14px; font-size:13px; font-weight:700; background:#FEF3C7; color:#92400E; border-radius:999px; padding:5px 12px; }
        button { margin-top:20px; width:100%; padding:14px; border:0; border-radius:12px; background:var(--brand2); color:#fff; font:inherit; font-weight:800; font-size:16px; cursor:pointer; transition: transform .2s, filter .2s; }
        button:hover { transform: translateY(-1px); filter: brightness(1.05); }
        .ok { background:#DCFCE7; color:#166534; border-radius:12px; padding:14px; margin-top:16px; font-weight:600; }
        .no { background:#F1F5F9; color:#475569; border-radius:12px; padding:14px; margin-top:16px; }
        .err { color:#B91C1C; margin-top:10px; }
        @keyframes subir { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform:none; } }
    </style>
    @include('publico._diseno_head', ['d' => $d])
</head>
<body>
<div class="wrap">
    <div class="card">
        @if ($d['logo_url'])<img class="logo" src="{{ $d['logo_url'] }}" alt="">@endif
        <div class="muted">{{ $negocio->nombre_negocio }}</div>
        <h1>Hola {{ $espera->cliente_nombre }}, se liberó un cupo</h1>
        <div class="hora">{{ $cita->inicio->format('H:i') }}</div>
        <div class="muted">{{ ucfirst($cita->inicio->locale('es')->translatedFormat('l d \d\e F')) }}@if ($cita->servicio) · {{ $cita->servicio->nombre }}@endif</div>

        @if ($tomada || $oferta->estado === 'tomada')
            <div class="ok">¡Listo! Tu cita quedó confirmada. Te esperamos.</div>
        @elseif ($vigente)
            <div class="reloj" id="reloj" data-fin="{{ \Carbon\Carbon::parse($oferta->expira_at)->toIso8601String() }}">Disponible por unos minutos</div>
            <form method="POST" action="{{ route('publico.espera.tomar', $oferta->token) }}">
                @csrf
                <button type="submit">Tomar este cupo</button>
            </form>
            @error('cupo')<div class="err">{{ $message }}</div>@enderror
        @else
            <div class="no">Este cupo ya no está disponible. Seguirás en la lista de espera y te avisaremos si se libera otro.</div>
        @endif
    </div>
</div>
<script>
    (() => {
        const el = document.getElementById('reloj'); if (!el) return;
        const fin = new Date(el.dataset.fin).getTime();
        const tic = () => {
            const s = Math.max(0, Math.round((fin - Date.now()) / 1000));
            el.textContent = s > 0 ? 'Disponible por ' + Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0') + ' min' : 'El tiempo terminó';
            if (s <= 0) { document.querySelector('button[type=submit]')?.setAttribute('disabled', 'disabled'); return; }
            setTimeout(tic, 1000);
        };
        tic();
    })();
</script>
</body>
</html>
