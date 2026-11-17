<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orden de compra {{ $o->numero }}</title>
    <style>
        @page { size: A4; margin: 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #0F172A; background: #F6F8FB; font-size: 13px; }
        .hoja { max-width: 800px; margin: 24px auto; background: #fff; padding: 36px; border-radius: 14px; box-shadow: 0 8px 30px rgba(15,23,42,.08); }
        .top { display: flex; justify-content: space-between; gap: 20px; align-items: flex-start; border-bottom: 2px solid #2B6EF6; padding-bottom: 16px; }
        h1 { font-size: 22px; margin: 0; }
        .num { font-size: 18px; font-weight: 800; color: #2B6EF6; text-align: right; }
        .muted { color: #64748B; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .box { border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; }
        .box b { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #64748B; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #64748B; border-bottom: 1px solid #E2E8F0; padding: 8px; }
        td { padding: 9px 8px; border-bottom: 1px solid #F1F5F9; }
        .r { text-align: right; }
        .total { display: flex; justify-content: flex-end; gap: 30px; font-size: 16px; font-weight: 800; margin-top: 14px; }
        .firmas { display: flex; justify-content: space-around; margin-top: 70px; text-align: center; }
        .firmas div { border-top: 1px solid #94A3B8; padding-top: 6px; width: 200px; color: #64748B; }
        .acciones { text-align: center; margin: 16px; }
        .acciones button { font: inherit; font-weight: 700; background: #2B6EF6; color: #fff; border: 0; border-radius: 10px; padding: 10px 18px; cursor: pointer; }
        @media print { body { background: #fff; } .hoja { box-shadow: none; margin: 0; padding: 0; } .acciones { display: none; } }
    </style>
</head>
<body>
    <div class="acciones"><button onclick="window.print()">Imprimir o guardar como PDF</button></div>
    <div class="hoja">
        <div class="top">
            <div>
                <h1>{{ $negocio->nombre_negocio ?? 'Mi negocio' }}</h1>
                <div class="muted">@if ($negocio?->ruc) RUC {{ $negocio->ruc }} · @endif{{ $negocio->email ?? '' }}</div>
            </div>
            <div>
                <div class="muted" style="text-align:right;">ORDEN DE COMPRA</div>
                <div class="num">{{ $o->numero }}</div>
            </div>
        </div>
        <div class="grid">
            <div class="box">
                <b>Proveedor</b>
                <div style="font-weight:700;">{{ $o->proveedor->nombre ?? '—' }}</div>
                @if ($o->proveedor?->ruc)<div>RUC {{ $o->proveedor->ruc }}</div>@endif
                @if ($o->proveedor?->direccion)<div>{{ $o->proveedor->direccion }}</div>@endif
                @if ($o->proveedor?->telefono)<div>Tel. {{ $o->proveedor->telefono }}</div>@endif
            </div>
            <div class="box">
                <b>Datos de la orden</b>
                <div>Fecha: {{ $o->fecha->format('d/m/Y') }}</div>
                @if ($o->fecha_esperada)<div>Entrega esperada: {{ \Carbon\Carbon::parse($o->fecha_esperada)->format('d/m/Y') }}</div>@endif
                <div>Pago: {{ \App\Models\Proveedor::CONDICIONES[$o->proveedor->condicion_pago ?? 'contado'] ?? 'Contado' }}</div>
                <div>Estado: {{ \App\Models\OrdenCompra::ESTADOS[$o->estado] ?? ucfirst($o->estado) }}</div>
            </div>
        </div>
        <table>
            <thead><tr><th>#</th><th>Descripción</th><th class="r">Cantidad</th><th class="r">Precio unit.</th><th class="r">Subtotal</th></tr></thead>
            <tbody>
                @foreach ($o->items as $i => $it)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $it->descripcion }}</td>
                        <td class="r">{{ rtrim(rtrim(number_format((float) $it->cantidad, 3), '0'), '.') }}</td>
                        <td class="r">S/ {{ number_format((float) $it->precio_unitario, 2) }}</td>
                        <td class="r">S/ {{ number_format((float) $it->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="total"><span>Total</span><span>S/ {{ number_format((float) $o->total, 2) }}</span></div>
        @if ($o->notas)<p class="muted" style="margin-top:18px;">Notas: {{ $o->notas }}</p>@endif
        <div class="firmas"><div>Solicitado por</div><div>Recibido por</div></div>
    </div>
</body>
</html>
