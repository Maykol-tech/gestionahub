{{-- Representacion impresa de boleta, factura o nota de credito (A4). En modo simulado lleva marca de agua. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $c->nombre_tipo }} {{ $c->numero_completo }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; font-size: 12px; color: #1A2332; background: #F4F6F9; }
        .hoja { position: relative; max-width: 800px; margin: 20px auto; background: #fff; padding: 36px; border-radius: 12px; box-shadow: 0 10px 30px rgba(26,35,50,.08); overflow: hidden; }
        .marca { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; font-size: 80px; font-weight: 800; color: rgba(220,38,38,.07); transform: rotate(-24deg); }
        .cab { display: grid; grid-template-columns: 1fr 260px; gap: 20px; align-items: start; }
        .neg { font-size: 20px; font-weight: 800; letter-spacing: -.4px; }
        .caja { border: 2px solid #2B6EF6; border-radius: 12px; padding: 14px; text-align: center; }
        .caja .ruc { font-weight: 700; }
        .caja .tipo { margin: 6px 0; font-weight: 800; font-size: 14px; color: #2B6EF6; text-transform: uppercase; }
        .caja .num { font-size: 16px; font-weight: 800; }
        .datos { margin: 22px 0 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; padding: 14px; background: #F8FAFC; border-radius: 10px; }
        .datos b { display: inline-block; min-width: 110px; color: #4A5568; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #2B6EF6; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; padding: 8px 10px; text-align: left; }
        th:first-child { border-top-left-radius: 8px; } th:last-child { border-top-right-radius: 8px; }
        td { padding: 8px 10px; border-bottom: 1px solid #E2E7EF; }
        .n { text-align: right; font-variant-numeric: tabular-nums; }
        .tots { margin-left: auto; width: 280px; margin-top: 14px; }
        .tots div { display: flex; justify-content: space-between; padding: 4px 0; }
        .tots .t { border-top: 2px solid #1A2332; margin-top: 4px; padding-top: 8px; font-size: 16px; font-weight: 800; }
        .pie { margin-top: 26px; font-size: 10px; color: #64748B; border-top: 1px dashed #CBD5E1; padding-top: 12px; }
        .estado { display: inline-block; padding: 3px 9px; border-radius: 99px; font-size: 10px; font-weight: 700; }
        .e-aceptado { background: #DCFCE7; color: #15803D; } .e-anulado, .e-rechazado, .e-error { background: #FEE2E2; color: #B91C1C; } .e-pendiente { background: #FEF3C7; color: #B45309; }
        .acc { text-align: center; margin: 14px; }
        .acc button { font: 600 13px Inter, sans-serif; padding: 10px 18px; border-radius: 10px; border: none; background: #F47B20; color: #fff; cursor: pointer; }
        @media print { body { background: #fff; } .hoja { margin: 0; box-shadow: none; border-radius: 0; padding: 0; } .acc { display: none; } }
    </style>
</head>
<body>
<div class="hoja">
    @if ($c->simulado)<div class="marca">SIMULADO</div>@endif
    @if ($c->estado === 'anulado')<div class="marca" style="color:rgba(220,38,38,.10);">ANULADO</div>@endif

    <div class="cab">
        <div>
            <div class="neg">{{ $negocio->nombre_negocio ?? 'Mi negocio' }}</div>
            @if (! empty($negocio?->email))<div>{{ $negocio->email }}</div>@endif
            @if (! empty($negocio?->telefono))<div>Tel. {{ $negocio->telefono }}</div>@endif
        </div>
        <div class="caja">
            <div class="ruc">RUC {{ $negocio->ruc ?? '—' }}</div>
            <div class="tipo">{{ $c->nombre_tipo }} electrónica</div>
            <div class="num">{{ $c->numero_completo }}</div>
        </div>
    </div>

    <div class="datos">
        <div><b>Fecha de emisión</b> {{ ($c->emitido_at ?? $c->created_at)->timezone('America/Lima')->format('d/m/Y H:i') }}</div>
        <div><b>Estado</b> <span class="estado e-{{ $c->estado }}">{{ ucfirst($c->estado) }}</span></div>
        <div><b>Cliente</b> {{ $c->cliente_nombre }}</div>
        <div><b>{{ $c->cliente_tipo_documento === '6' ? 'RUC' : 'DNI' }}</b> {{ $c->cliente_documento ?: '—' }}</div>
        @if ($c->cliente_direccion)<div style="grid-column:1/-1;"><b>Dirección</b> {{ $c->cliente_direccion }}</div>@endif
        @if ($c->referencia)
            <div style="grid-column:1/-1;"><b>Modifica a</b> {{ $c->referencia->nombre_tipo }} {{ $c->referencia->numero_completo }}
                @if (! empty($c->payload['motivo'])) · Motivo: {{ $c->payload['motivo'] }} @endif</div>
        @endif
        <div><b>Moneda</b> Soles (PEN)</div>
    </div>

    <table>
        <thead><tr><th>Cant.</th><th>Código</th><th>Descripción</th><th class="n">P. unit.</th><th class="n">Valor venta</th><th class="n">IGV</th><th class="n">Importe</th></tr></thead>
        <tbody>
        @forelse ($items as $it)
            <tr>
                <td>{{ rtrim(rtrim(number_format((float) $it['cantidad'], 2), '0'), '.') }}</td>
                <td>{{ $it['codigo'] ?? '' }}</td>
                <td>{{ $it['descripcion'] }}</td>
                <td class="n">{{ number_format((float) $it['precio_unitario'], 2) }}</td>
                <td class="n">{{ number_format((float) ($it['subtotal'] ?? 0), 2) }}</td>
                <td class="n">{{ number_format((float) ($it['igv'] ?? 0), 2) }}</td>
                <td class="n">{{ number_format((float) ($it['total'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#94A3B8;">Sin detalle guardado</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="tots">
        <div><span>Op. gravada</span><span>S/ {{ number_format((float) $c->subtotal, 2) }}</span></div>
        <div><span>IGV 18%</span><span>S/ {{ number_format((float) $c->igv, 2) }}</span></div>
        <div class="t"><span>Importe total</span><span>S/ {{ number_format((float) $c->total, 2) }}</span></div>
    </div>

    <div class="pie">
        Representación impresa de la {{ strtolower($c->nombre_tipo) }} electrónica.
        @if ($c->codigo_hash) Código hash: {{ $c->codigo_hash }}. @endif
        @if ($c->simulado)
            <br><b>Documento generado en modo simulado: no fue enviado a SUNAT y no tiene valor tributario.</b>
        @elseif ($c->enlace_pdf && ! str_contains($c->enlace_pdf, '/admin/comprobantes/'))
            <br>Consulta el PDF oficial: {{ $c->enlace_pdf }}
        @endif
    </div>
</div>
<div class="acc"><button type="button" onclick="window.print()">Imprimir o guardar PDF</button></div>
</body>
</html>
