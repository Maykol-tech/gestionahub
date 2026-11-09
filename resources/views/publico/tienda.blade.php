<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ data_get($contenido, 'seo.titulo') ?: $negocio->nombre_negocio }}</title>
    <meta name="description" content="{{ data_get($contenido, 'seo.descripcion') }}">
    <style>
        :root { --brand: {{ $color }}; --bg:#F6F8FB; --fg:#1A2332; --fg2:#475569; --fg3:#64748B; --card:#fff; --border:#E2E8F0; }
        @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { --bg:#0F172A; --fg:#E2E8F0; --fg2:#CBD5E1; --fg3:#94A3B8; --card:#1E293B; --border:#334155; } }
        :root[data-theme="dark"] { --bg:#0F172A; --fg:#E2E8F0; --fg2:#CBD5E1; --fg3:#94A3B8; --card:#1E293B; --border:#334155; }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--bg); color:var(--fg); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 16px; }
        header.hero { background: var(--brand); color:#fff; padding: 28px 16px; border-radius: 16px; margin-bottom: 16px; animation: up .5s ease both; }
        header.hero h1 { margin:0 0 6px; font-size: 24px; }
        header.hero p { margin:0; opacity:.92; }
        .estado { display:inline-block; margin-top:10px; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; background:#fff; color:#15803D; }
        .estado.cerrado { color:#B91C1C; }
        .grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); gap:12px; }
        .prod { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px; display:flex; flex-direction:column; gap:8px; animation: up .4s ease both; transition: transform .2s, box-shadow .2s; }
        .prod:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08); }
        .prod b { font-size:15px; }
        .precio { font-weight:800; font-variant-numeric: tabular-nums; }
        .btn { border:0; border-radius:10px; padding:10px 14px; font-weight:700; cursor:pointer; background: var(--brand); color:#fff; }
        .btn:disabled { opacity:.5; cursor:not-allowed; }
        .btn.g { background:transparent; color:var(--fg); border:1px solid var(--border); }
        .carrito { position: sticky; bottom: 12px; margin-top: 16px; background: var(--card); border:1px solid var(--border); border-radius:14px; padding:14px; box-shadow: 0 10px 30px rgba(0,0,0,.1); }
        .lbl { display:block; font-size:13px; color:var(--fg2); margin:10px 0 4px; }
        .inp { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--bg); color:var(--fg); font:inherit; }
        .err { color:#B91C1C; font-size:13px; margin-top:6px; }
        @keyframes up { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
        @media (max-width: 480px) { .wrap { padding: 12px; } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
</head>
<body>
<div class="wrap" x-data="tienda(@js($productos), @js($entregas), {{ $abierta ? 'true' : 'false' }})">
    <header class="hero">
        <h1>{{ data_get($contenido, 'portada.titulo') ?: $negocio->nombre_negocio }}</h1>
        <p>{{ data_get($contenido, 'portada.subtitulo') }}</p>
        <span class="estado" :class="{ cerrado: !abierta }" x-text="abierta ? 'Abierto ahora' : 'Cerrado ahora'"></span>
    </header>

    @if ($errors->any())
        <div class="err" style="margin-bottom:12px;">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div>
    @endif

    <section class="grid">
        <template x-for="p in productos" :key="p.id">
            <div class="prod">
                <b x-text="p.nombre"></b>
                <span class="precio" x-text="'S/ ' + p.precio.toFixed(2)"></span>
                <span style="font-size:12px;color:var(--fg3);" x-text="p.stock > 0 ? 'Disponible' : 'Agotado'"></span>
                <button class="btn" type="button" :disabled="p.stock <= 0 || !abierta" @click="agregar(p)">Agregar</button>
            </div>
        </template>
    </section>

    <section class="carrito" x-show="total_items() > 0" x-cloak>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <b x-text="total_items() + ' productos Â· S/ ' + total().toFixed(2)"></b>
            <button class="btn" type="button" @click="paso = 2">Continuar</button>
        </div>

        <form method="POST" action="{{ route('tienda.pedido', ['slug' => $negocio->slug]) }}" x-show="paso === 2" x-cloak>
            @csrf
            <input type="hidden" name="carrito" :value="JSON.stringify(lineas())">

            <label class="lbl" for="nombre">Nombre</label>
            <input class="inp" id="nombre" name="nombre" maxlength="120" required value="{{ old('nombre') }}">

            <label class="lbl" for="telefono">Teléfono</label>
            <input class="inp" id="telefono" name="telefono" maxlength="20" required value="{{ old('telefono') }}">

            <label class="lbl" for="entrega">Entrega</label>
            <select class="inp" id="entrega" name="entrega_id" x-model="entrega" required>
                <option value="">Elige una opción</option>
                <template x-for="e in entregas" :key="e.id">
                    <option :value="e.id" x-text="e.nombre + (e.costo > 0 ? ' (S/ ' + e.costo.toFixed(2) + ')' : '')"></option>
                </template>
            </select>

            <label class="lbl" for="direccion">Dirección (si es delivery)</label>
            <input class="inp" id="direccion" name="direccion" maxlength="255" value="{{ old('direccion') }}">

            <label class="lbl" for="metodo">Pago</label>
            <select class="inp" id="metodo" name="metodo_pago" required>
                <option value="efectivo">Efectivo</option>
                <option value="yape">Yape</option>
                <option value="plin">Plin</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
            </select>

            <label class="lbl" for="notas">Notas</label>
            <input class="inp" id="notas" name="notas" maxlength="300">

            <div style="display:flex;gap:8px;margin-top:14px;">
                <button class="btn g" type="button" @click="paso = 1">Volver</button>
                <button class="btn" type="submit" :disabled="!abierta">Confirmar pedido</button>
            </div>
        </form>
    </section>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tienda', (productos, entregas, abierta) => ({
            productos, entregas, abierta, paso: 1, entrega: '', carrito: {},
            agregar(p) { this.carrito[p.id] = { ...p, cantidad: (this.carrito[p.id]?.cantidad || 0) + 1 }; },
            total_items() { return Object.values(this.carrito).reduce((n, l) => n + l.cantidad, 0); },
            total() { return Object.values(this.carrito).reduce((n, l) => n + l.cantidad * l.precio, 0); },
            lineas() { return Object.values(this.carrito).map(l => ({ producto_id: l.id, cantidad: l.cantidad })); },
        }));
    });
</script>
</body>
</html>
