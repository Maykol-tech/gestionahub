<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cierre de caja {{ $c['fecha'] }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body { margin: 0 auto; width: 72mm; font-family: "Courier New", ui-monospace, monospace; font-size: 12px; color: #000; background: #fff; }
        h1 { font-size: 15px; text-align: center; margin: 6px 0 2px; }
        .c { text-align: center; }
        .row { display: flex; justify-content: space-between; padding: 1px 0; }
        .sep { border-top: 1px dashed #000; margin: 6px 0; }
        .b { font-weight: 700; }
        .big { font-size: 14px; }
        .acciones { text-align: center; margin: 14px 0; }
        .acciones button { font: inherit; padding: 6px 14px; cursor: pointer; }
        @media print { .acciones { display: none; } }
    </style>
</head>
<body>
    <h1>{{ $negocio }}</h1>
    <div class="c">CIERRE DE CAJA</div>
    <div class="c">{{ $c['fecha'] }}</div>
    <div class="sep"></div>
    <div class="row"><span>Monto inicial</span><span>S/ {{ number_format($c['monto_inicial'], 2) }}</span></div>
    <div class="row"><span>+ Ventas efectivo</span><span>S/ {{ number_format($c['ventas_efectivo'], 2) }}</span></div>
    <div class="row"><span>- Gastos efectivo</span><span>S/ {{ number_format($c['gastos_efectivo'], 2) }}</span></div>
    <div class="sep"></div>
    <div class="row b"><span>Esperado</span><span>S/ {{ number_format($c['esperado'], 2) }}</span></div>
    <div class="row b"><span>Contado</span><span>S/ {{ number_format($c['contado'], 2) }}</span></div>
    <div class="row b big"><span>Diferencia</span><span>S/ {{ number_format($c['diferencia'], 2) }}</span></div>
    <div class="sep"></div>
    <div class="b">Por método de pago</div>
    @forelse ($porMetodo as $m => $v)
        <div class="row"><span>{{ ucfirst($m) }} ({{ $v['operaciones'] }})</span><span>S/ {{ number_format($v['neto'], 2) }}</span></div>
    @empty
        <div>Sin movimientos</div>
    @endforelse
    <div class="row"><span>Ventas digitales</span><span>S/ {{ number_format($c['ventas_digitales'], 2) }}</span></div>
    <div class="row"><span>Gastos digitales</span><span>S/ {{ number_format($c['gastos_digitales'], 2) }}</span></div>
    @if ($c['notas'])
        <div class="sep"></div>
        <div>Notas: {{ $c['notas'] }}</div>
    @endif
    <div class="sep"></div>
    <div>Cerrado por: {{ $c['cerrado_por'] }}</div>
    <div>Impreso: {{ now()->format('d/m/Y H:i') }}</div>
    <br><br>
    <div class="c">______________________</div>
    <div class="c">Firma</div>
    <div class="acciones"><button onclick="window.print()">Imprimir</button></div>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
