<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido recibido</title>
    <style>
        :root { --brand: {{ $color }}; --bg:#F6F8FB; --fg:#1A2332; --fg2:#475569; --card:#fff; --border:#E2E8F0; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --bg:#0F172A; --fg:#E2E8F0; --fg2:#CBD5E1; --card:#1E293B; --border:#334155; } }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        .wrap { max-width: 560px; margin: 0 auto; padding: 16px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:22px; animation: up .5s ease both; }
        h1 { color: var(--brand); margin: 0 0 8px; font-size: 22px; }
        table { width:100%; border-collapse: collapse; margin-top: 12px; }
        td { padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
        td.r { text-align:right; font-variant-numeric: tabular-nums; }
        @keyframes up { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Gracias, {{ $negocio->nombre_negocio }} recibió tu pedido!</h1>
        <div style="color:var(--fg2);font-size:14px;">Código de pedido: <b>{{ $pedido->numero }}</b></div>
        <table>
            @foreach ($pedido->items as $item)
                <tr><td>{{ $item->cantidad }} × {{ $item->nombre }}</td><td class="r">S/ {{ number_format((float) $item->subtotal, 2) }}</td></tr>
            @endforeach
            @if ((float) $pedido->costo_envio > 0)
                <tr><td>Envío</td><td class="r">S/ {{ number_format((float) $pedido->costo_envio, 2) }}</td></tr>
            @endif
            <tr><td><b>Total</b></td><td class="r"><b>S/ {{ number_format((float) $pedido->total, 2) }}</b></td></tr>
        </table>
        <p style="font-size:14px;color:var(--fg2);">Te confirmaremos por WhatsApp. Pago: {{ ucfirst($pedido->metodo_pago) }}.</p>
    </div>
</div>
</body>
</html>
