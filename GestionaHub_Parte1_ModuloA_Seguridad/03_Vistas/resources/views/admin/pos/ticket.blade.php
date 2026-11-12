{{-- Ticket de venta POS (impresora termica de 80 mm). Se abre en otra pestaña e imprime solo. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $venta->numero }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', ui-monospace, monospace; font-size: 12px; color: #000; background: #F4F6F9; }
        .t { width: 72mm; margin: 16px auto; background: #fff; padding: 12px 10px; box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        .c { text-align: center; }
        .b { font-weight: 700; }
        .hr { border-top: 1px dashed #000; margin: 8px 0; }
        .r { display: flex; justify-content: space-between; gap: 6px; }
        .it { margin: 3px 0; }
        .tot { font-size: 15px; font-weight: 700; }
        .anulada { border: 2px solid #000; padding: 4px; margin: 6px 0; font-weight: 700; text-align: center; }
        .acc { text-align: center; margin: 12px auto; }
        .acc button { font: 600 13px system-ui, sans-serif; padding: 9px 16px; border-radius: 9px; border: none; background: #F47B20; color: #fff; cursor: pointer; }
        @media print { body { background: #fff; } .t { margin: 0; box-shadow: none; width: auto; } .acc { display: none; } }
    </style>
</head>
<body>
<div class="t">
    <div class="c b" style="font-size:14px;">{{ $negocio->nombre_negocio ?? 'Mi negocio' }}</div>
    @if (! empty($negocio?->ruc))<div class="c">RUC {{ $negocio->ruc }}</div>@endif
    @if (! empty($negocio?->telefono))<div class="c">Tel. {{ $negocio->telefono }}</div>@endif
    <div class="hr"></div>
    <div class="c b">
        @if ($comprobante)
            {{ strtoupper($comprobante->nombre_tipo) }} ELECTRÓNICA<br>{{ $comprobante->numero_completo }}
        @else
            NOTA DE VENTA<br>{{ $venta->numero }}
        @endif
    </div>
    <div class="hr"></div>
    <div class="r"><span>Fecha</span><span>{{ $venta->created_at->timezone('America/Lima')->format('d/m/Y H:i') }}</span></div>
    <div class="r"><span>Venta</span><span>{{ $venta->numero }}</span></div>
    @if ($venta->cliente_nombre)<div class="r"><span>Cliente</span><span>{{ \Illuminate\Support\Str::limit($venta->cliente_nombre, 24) }}</span></div>@endif
    @if ($venta->cliente_documento)<div class="r"><span>{{ strlen($venta->cliente_documento) === 11 ? 'RUC' : 'DNI' }}</span><span>{{ $venta->cliente_documento }}</span></div>@endif
    @if ($venta->anulada)<div class="anulada">VENTA ANULADA</div>@endif
    <div class="hr"></div>
    @foreach ((array) $venta->items as $l)
        <div class="it">
            <div>{{ $l['nombre'] }}</div>
            <div class="r"><span>{{ $l['cantidad'] }} x {{ number_format($l['precio'], 2) }}</span><span>{{ number_format($l['subtotal'] ?? $l['precio'] * $l['cantidad'], 2) }}</span></div>
        </div>
    @endforeach
    <div class="hr"></div>
    <div class="r"><span>Op. gravada</span><span>S/ {{ number_format((float) $venta->gravada, 2) }}</span></div>
    <div class="r"><span>IGV 18%</span><span>S/ {{ number_format((float) $venta->igv, 2) }}</span></div>
    <div class="r tot"><span>TOTAL</span><span>S/ {{ number_format((float) $venta->total, 2) }}</span></div>
    <div class="hr"></div>
    <div class="r"><span>Pago</span><span>{{ $venta->nombre_metodo }}</span></div>
    @if ($venta->metodo_pago === 'efectivo')
        <div class="r"><span>Recibido</span><span>S/ {{ number_format((float) $venta->recibido, 2) }}</span></div>
        <div class="r b"><span>Vuelto</span><span>S/ {{ number_format((float) $venta->vuelto, 2) }}</span></div>
    @endif
    <div class="hr"></div>
    @if ($comprobante)
        <div class="c" style="font-size:10px;">Representación impresa de la {{ strtolower($comprobante->nombre_tipo) }} electrónica.<br>Hash: {{ $comprobante->codigo_hash ?: '—' }}</div>
        @if ($comprobante->simulado)<div class="c b" style="font-size:10px;margin-top:4px;">*** MODO SIMULADO · SIN VALOR TRIBUTARIO ***</div>@endif
    @else
        <div class="c" style="font-size:10px;">Documento interno, no válido como comprobante de pago.</div>
    @endif
    <div class="c" style="margin-top:8px;">¡Gracias por su compra!</div>
</div>
<div class="acc"><button type="button" onclick="window.print()">Imprimir</button></div>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
