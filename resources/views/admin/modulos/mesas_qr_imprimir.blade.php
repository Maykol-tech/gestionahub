<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR de las mesas · {{ $negocio }}</title>
    <style>
        @page { size:A4; margin:10mm; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color:#111827; background:#F3F4F6; }
        .barra { position:sticky; top:0; display:flex; align-items:center; gap:10px; padding:12px 16px; background:#fff; border-bottom:1px solid #E5E7EB; }
        .barra button { border:none; background:#111827; color:#fff; border-radius:10px; padding:10px 16px; font:inherit; font-weight:700; cursor:pointer; }
        .hoja { display:grid; grid-template-columns:repeat(2, 1fr); gap:8mm; max-width:190mm; margin:16px auto; }
        .qr { background:#fff; border:1.5px dashed #9CA3AF; border-radius:6mm; padding:7mm 6mm 6mm; text-align:center; break-inside:avoid; page-break-inside:avoid; height:88mm; display:flex; flex-direction:column; align-items:center; justify-content:center; }
        .qr .neg { font-size:12px; color:#6B7280; font-weight:600; letter-spacing:.04em; text-transform:uppercase; }
        .qr .mesa { font-size:22px; font-weight:800; margin:2px 0 8px; }
        .qr svg { width:48mm; height:48mm; }
        .qr .txt { font-size:12px; color:#374151; margin-top:8px; }
        @media print { body { background:#fff; } .barra { display:none; } .hoja { margin:0 auto; } }
        @media (max-width:600px) { .hoja { grid-template-columns:1fr; padding:0 16px; } }
    </style>
</head>
<body>
    <div class="barra">
        <b>{{ $mesas->count() }} {{ $mesas->count() === 1 ? 'mesa' : 'mesas' }}</b>
        <span style="color:#6B7280;font-size:13px;">Se imprimen 6 por hoja. Recorta por la línea punteada.</span>
        <button type="button" style="margin-left:auto;" onclick="window.print()">Imprimir</button>
    </div>
    <div class="hoja">
        @foreach ($mesas as $m)
            <div class="qr">
                <div class="neg">{{ $negocio }}</div>
                <div class="mesa">{{ $m['nombre'] }}</div>
                {!! $m['svg'] !!}
                <div class="txt">Escanea para ver la carta y pedir</div>
            </div>
        @endforeach
    </div>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
</body>
</html>
