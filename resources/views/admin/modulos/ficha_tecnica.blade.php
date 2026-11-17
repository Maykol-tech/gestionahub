<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha técnica · {{ $r->nombre }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #0F172A; background: #F6F8FB; font-size: 13px; }
        .hoja { max-width: 820px; margin: 24px auto; background: #fff; padding: 34px; border-radius: 14px; box-shadow: 0 8px 30px rgba(15,23,42,.08); }
        .top { display: grid; grid-template-columns: 1fr 220px; gap: 24px; align-items: start; }
        .tag { display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: .08em; color: #2B6EF6; text-transform: uppercase; }
        h1 { font-size: 26px; margin: 4px 0 6px; letter-spacing: -.02em; }
        .muted { color: #64748B; }
        .foto { width: 220px; height: 160px; border-radius: 12px; object-fit: cover; background: #EEF2F7; display: grid; place-items: center; color: #94A3B8; font-size: 12px; }
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 20px 0; }
        .kpi { border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px; }
        .kpi small { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #64748B; font-weight: 700; }
        .kpi b { font-size: 17px; }
        h2 { font-size: 14px; margin: 22px 0 8px; text-transform: uppercase; letter-spacing: .06em; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #64748B; border-bottom: 1px solid #E2E8F0; padding: 8px; }
        td { padding: 8px; border-bottom: 1px solid #F1F5F9; }
        .r { text-align: right; }
        ol { margin: 0; padding-left: 20px; line-height: 1.7; }
        .alerg { background: #FEF3C7; color: #92400E; border-radius: 10px; padding: 10px 12px; font-weight: 600; }
        .acciones { text-align: center; margin: 16px; }
        .acciones button { font: inherit; font-weight: 700; background: #2B6EF6; color: #fff; border: 0; border-radius: 10px; padding: 10px 18px; cursor: pointer; }
        @media print { body { background: #fff; } .hoja { box-shadow: none; margin: 0; padding: 0; } .acciones { display: none; } }
        @media (max-width: 640px) { .top { grid-template-columns: 1fr; } .kpis { grid-template-columns: 1fr 1fr; } .hoja { padding: 18px; } }
    </style>
</head>
<body>
@php
    $precio = $producto ? (float) ($producto->precio_oferta ?? $producto->precio) : null;
    $margen = $precio ? round(($precio - $costo) / $precio * 100, 1) : null;
    $pasos = collect(preg_split('/\r?\n/', (string) $r->pasos))->map(fn ($p) => trim(preg_replace('/^\d+[\.\)]\s*/', '', $p)))->filter();
@endphp
    <div class="acciones"><button onclick="window.print()">Imprimir o guardar como PDF</button></div>
    <div class="hoja">
        <div class="top">
            <div>
                <span class="tag">Ficha técnica · {{ $negocio->nombre_negocio ?? 'Mi negocio' }}</span>
                <h1>{{ $r->nombre }}</h1>
                <div class="muted">{{ $producto ? 'Producto: ' . $producto->nombre : 'Sin producto vinculado' }} · actualizada {{ $r->updated_at?->format('d/m/Y') }}</div>
                @if ($r->notas)<p class="muted">{{ $r->notas }}</p>@endif
            </div>
            @if ($r->imagen_url)
                <img class="foto" src="{{ $r->imagen_url }}" alt="{{ $r->nombre }}">
            @else
                <div class="foto">Sin foto</div>
            @endif
        </div>

        <div class="kpis">
            <div class="kpi"><small>Rinde</small><b>{{ $r->rendimiento }} porc.</b></div>
            <div class="kpi"><small>Costo por porción</small><b>S/ {{ number_format($costo, 2) }}</b></div>
            <div class="kpi"><small>Precio de venta</small><b>{{ $precio ? 'S/ ' . number_format($precio, 2) : '—' }}</b></div>
            <div class="kpi"><small>Margen</small><b style="color:{{ $margen !== null && $margen < 30 ? '#B91C1C' : '#15803D' }}">{{ $margen !== null ? $margen . '%' : '—' }}</b></div>
        </div>

        <h2>Ingredientes</h2>
        <table>
            <thead><tr><th>Insumo</th><th class="r">Cantidad</th><th class="r">Merma</th><th class="r">Costo unit.</th><th class="r">Costo</th></tr></thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach ($r->items as $it)
                    @php
                        $cu = (float) optional($it->insumo)->costo_unitario;
                        $sub = (float) $it->cantidad * (1 + (float) $it->merma_pct / 100) * $cu;
                        $total += $sub;
                    @endphp
                    <tr>
                        <td>{{ optional($it->insumo)->nombre ?? 'Insumo eliminado' }}</td>
                        <td class="r">{{ rtrim(rtrim(number_format((float) $it->cantidad, 3), '0'), '.') }} {{ optional($it->insumo)->unidad }}</td>
                        <td class="r">{{ (float) $it->merma_pct ? rtrim(rtrim(number_format((float) $it->merma_pct, 2), '0'), '.') . '%' : '—' }}</td>
                        <td class="r">S/ {{ number_format($cu, 2) }}</td>
                        <td class="r">S/ {{ number_format($sub, 2) }}</td>
                    </tr>
                @endforeach
                <tr><td colspan="4" class="r" style="font-weight:800;">Costo de la tanda</td><td class="r" style="font-weight:800;">S/ {{ number_format($total, 2) }}</td></tr>
            </tbody>
        </table>

        <h2>Preparación</h2>
        @if ($pasos->isNotEmpty())
            <ol>@foreach ($pasos as $p)<li>{{ $p }}</li>@endforeach</ol>
        @else
            <p class="muted">Aún no se registraron los pasos. Agrégalos en Producción → Recetas → Editar.</p>
        @endif

        <h2>Alérgenos</h2>
        <div class="alerg">{{ $r->alergenos ?: 'No se declararon alérgenos.' }}</div>
    </div>
</body>
</html>
