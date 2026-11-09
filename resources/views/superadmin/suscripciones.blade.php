<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suscripciones · GestionaHub</title>
    <style>
        :root { --brand:#2B6EF6; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --border:#E2E8F0; --bg:#F6F8FB; --card:#fff; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --fg:#E2E8F0; --fg2:#94A3B8; --fg3:#64748B; --border:#1E293B; --bg:#0B1220; --card:#111A2B; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .wrap { max-width:1000px; margin:0 auto; padding:20px 16px 60px; }
        h1 { font-size:22px; margin:0 0 14px; }
        .panel { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:0; overflow:hidden; animation: subir .4s ease both; }
        .tbl-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th, td { padding:10px 12px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; }
        th { font-size:12px; color:var(--fg3); font-weight:600; }
        .chip { font-size:12px; font-weight:700; padding:3px 9px; border-radius:99px; }
        .pendiente { background:#FEF3C7; color:#B45309; }
        .confirmado { background:#DCFCE7; color:#15803D; }
        .rechazado { background:#FEE2E2; color:#B91C1C; }
        .btn { border:0; border-radius:8px; padding:7px 11px; font-weight:700; cursor:pointer; color:#fff; }
        .ok { background:#15803D; } .no { background:#B91C1C; }
        .msg { padding:12px; border-radius:10px; margin-bottom:12px; background:var(--card); border:1px solid var(--border); }
        @keyframes subir { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation:none !important; } }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Pagos de suscripción</h1>

    @if (session('ok')) <div class="msg" style="color:#15803D;">{{ session('ok') }}</div> @endif
    @if (session('error')) <div class="msg" style="color:#B91C1C;">{{ session('error') }}</div> @endif

    <div class="panel">
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr><th>Fecha</th><th>Negocio</th><th>Plan</th><th>Método</th><th>Monto</th><th>Operación</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($pagos as $p)
                    <tr>
                        <td>{{ $p['fecha'] }}</td>
                        <td>{{ $p['negocio'] }}</td>
                        <td>{{ $p['plan'] }}</td>
                        <td>{{ $p['metodo'] }}</td>
                        <td>S/ {{ number_format($p['monto'], 2) }}</td>
                        <td>{{ $p['referencia'] }}</td>
                        <td><span class="chip {{ $p['estado'] }}">{{ ucfirst($p['estado']) }}</span></td>
                        <td>
                            @if ($p['estado'] === 'pendiente')
                                <form method="POST" action="{{ route('superadmin.suscripciones.confirmar', $p['id']) }}" style="display:inline;">@csrf
                                    <button class="btn ok" type="submit">Confirmar</button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.suscripciones.rechazar', $p['id']) }}" style="display:inline;">@csrf
                                    <button class="btn no" type="submit">Rechazar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="color:var(--fg3);text-align:center;padding:24px;">Aún no hay pagos de suscripción.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
