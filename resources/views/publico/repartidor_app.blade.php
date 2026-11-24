@php
    $nombreNegocio = $negocio->nombre_negocio ?? 'Tu negocio';
    $urls = [
        'pedidos' => route('repartidor.pedidos', $token),
        'ubicacion' => route('repartidor.ubicacion', $token),
        'estado' => url('r/' . $token . '/pedidos'),
        'disponible' => route('repartidor.disponible', $token),
    ];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1F2937">
    <title>Mis repartos · {{ $nombreNegocio }}</title>
    <style>
        :root { --bg:#F3F5F9; --card:#fff; --fg:#111827; --fg2:#4B5563; --fg3:#6B7280; --border:#E5E7EB; --brand:#2B6EF6; --ok:#16A34A; --warn:#F59E0B; }
        @media (prefers-color-scheme: dark) { :root { --bg:#0B1220; --card:#121B2C; --fg:#F3F4F6; --fg2:#CBD5E1; --fg3:#94A3B8; --border:#1F2A3D; } }
        * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        body { margin:0; font-family:system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background:var(--bg); color:var(--fg); -webkit-font-smoothing:antialiased; }
        [x-cloak] { display:none !important; }
        .top { background:#1F2937; color:#fff; padding:16px 16px 22px; padding-top:max(16px, env(safe-area-inset-top)); border-radius:0 0 22px 22px; }
        .top-row { display:flex; align-items:center; gap:12px; }
        .av { width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg, #4B7CF5, #F47B20); display:grid; place-items:center; font-weight:800; font-size:18px; flex:none; }
        .hola { font-size:13px; color:rgba(255,255,255,.65); }
        .nom { font-weight:800; font-size:18px; }
        .estado-btn { margin-left:auto; border:none; border-radius:99px; padding:8px 12px; font:inherit; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; transition:background .2s; }
        .estado-btn i { width:8px; height:8px; border-radius:50%; background:currentColor; }
        .estado-btn.on { background:#DCFCE7; color:#15803D; }
        .estado-btn.off { background:#FEF3C7; color:#B45309; }
        .resumen { display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-top:16px; }
        .resumen div { background:rgba(255,255,255,.08); border-radius:12px; padding:10px; text-align:center; }
        .resumen b { display:block; font-size:20px; }
        .resumen span { font-size:11px; color:rgba(255,255,255,.65); }
        .gps { margin:12px 16px 0; display:flex; align-items:center; gap:10px; font-size:13px; background:var(--card); border:1px solid var(--border); border-radius:12px; padding:10px 12px; }
        .gps .dot { width:10px; height:10px; border-radius:50%; background:#94A3B8; flex:none; }
        .gps .dot.vivo { background:var(--ok); box-shadow:0 0 0 0 rgba(22,163,74,.5); animation:pulso 1.8s infinite; }
        @keyframes pulso { 70% { box-shadow:0 0 0 10px rgba(22,163,74,0); } 100% { box-shadow:0 0 0 0 rgba(22,163,74,0); } }
        .gps button { margin-left:auto; border:1px solid var(--border); background:var(--bg); color:var(--fg); border-radius:9px; padding:6px 10px; font:inherit; font-size:12px; font-weight:700; }
        main { padding:14px 16px 90px; max-width:560px; margin:0 auto; }
        .sec { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--fg3); margin:18px 2px 8px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:14px; margin-bottom:12px; animation:entra .35s ease both; }
        .card.ruta { border-color:var(--brand); box-shadow:0 8px 24px rgba(43,110,246,.15); }
        .card.hecho { opacity:.6; }
        @keyframes entra { from { opacity:0; transform:translateY(10px); } }
        .c-top { display:flex; align-items:center; gap:6px 8px; flex-wrap:wrap; }
        .num { font-weight:800; font-size:15px; word-break:break-all; }
        .chip { font-size:11px; font-weight:700; padding:3px 9px; border-radius:99px; background:var(--bg); color:var(--fg2); }
        .chip.ruta { background:#DBEAFE; color:#1D4ED8; }
        .chip.listo { background:#DCFCE7; color:#15803D; }
        .chip.hecho { background:#E2E8F0; color:#475569; }
        .total { margin-left:auto; font-weight:800; font-size:16px; white-space:nowrap; }
        .cobrar { margin-top:8px; font-size:13px; font-weight:700; padding:8px 10px; border-radius:10px; background:#FEF3C7; color:#92400E; }
        .cobrar.ok { background:#DCFCE7; color:#166534; }
        .cli { margin-top:10px; font-weight:700; }
        .dir { font-size:14px; color:var(--fg2); margin-top:2px; line-height:1.4; }
        .nota { font-size:13px; color:var(--fg2); background:var(--bg); border-radius:10px; padding:8px 10px; margin-top:8px; }
        .items { font-size:13px; color:var(--fg3); margin-top:8px; }
        .acc { display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-top:12px; }
        .acc a, .acc button { display:flex; flex-direction:column; align-items:center; gap:4px; padding:10px 6px; border-radius:12px; border:1px solid var(--border); background:var(--bg); color:var(--fg); text-decoration:none; font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
        .acc svg { width:20px; height:20px; }
        .acc .wa { color:#15803D; }
        .principal { width:100%; margin-top:10px; border:none; border-radius:14px; padding:15px; font:inherit; font-weight:800; font-size:15px; color:#fff; background:var(--brand); cursor:pointer; transition:transform .12s, filter .15s; box-shadow:0 8px 20px rgba(43,110,246,.3); }
        .principal.verde { background:var(--ok); box-shadow:0 8px 20px rgba(22,163,74,.3); }
        .principal:active { transform:scale(.98); }
        .principal:disabled { opacity:.6; }
        .vacio { text-align:center; padding:50px 20px; color:var(--fg3); }
        .vacio div { font-size:44px; margin-bottom:8px; }
        .toast { position:fixed; left:50%; bottom:calc(20px + env(safe-area-inset-bottom)); transform:translateX(-50%); background:#111827; color:#fff; padding:12px 16px; border-radius:12px; font-size:14px; font-weight:600; box-shadow:0 12px 30px rgba(0,0,0,.3); max-width:calc(100% - 32px); z-index:20; }
        .modal { position:fixed; inset:0; background:rgba(15,23,42,.55); display:flex; align-items:flex-end; justify-content:center; z-index:30; }
        .hoja { background:var(--card); width:100%; max-width:560px; border-radius:22px 22px 0 0; padding:20px 16px calc(20px + env(safe-area-inset-bottom)); }
        .hoja h3 { margin:0 0 6px; }
        .hoja p { margin:0 0 14px; color:var(--fg2); font-size:14px; }
        .hoja .sec-btn { width:100%; margin-top:8px; border:1px solid var(--border); background:var(--bg); color:var(--fg); border-radius:14px; padding:14px; font:inherit; font-weight:700; }
        .ruta-box { background:var(--card); border:1px solid var(--border); border-radius:18px; padding:12px; margin-top:14px; }
        .ruta-hd { display:flex; align-items:center; gap:6px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--fg3); margin:2px 2px 10px; }
        #mapa-ruta { height:210px; border-radius:12px; overflow:hidden; background:#0F172A; }
        .ruta-sin { height:120px; border-radius:12px; display:grid; place-items:center; text-align:center; font-size:13px; color:var(--fg3); background:var(--bg); padding:12px; }
        .ruta-chips { display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
        .ruta-chips span { flex:none; font-size:12px; color:var(--fg3); background:var(--bg); border-radius:8px; padding:6px 10px; }
        .ruta-chips b { color:var(--fg); margin-left:4px; }
        .ruta-chips .cobro b { color:#EA580C; }
        .pin { width:26px; height:26px; border-radius:50%; display:grid; place-items:center; color:#fff; font-size:12px; font-weight:800; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,.35); }
        .pin.p { background:var(--brand); } .pin.r { background:#F97316; } .pin.h { background:var(--ok); } .pin.yo { background:#111827; }
        .orden { display:inline-grid; place-items:center; width:24px; height:24px; border-radius:50%; background:var(--brand); color:#fff; font-size:12px; font-weight:800; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation:none !important; transition:none !important; } }
    </style>
</head>
<body>
<div x-data="appRepartidor(@js($pedidos), @js((bool) $repartidor->disponible), @js($urls))" x-init="iniciar()">
    <header class="top">
        <div class="top-row">
            <div class="av">{{ mb_strtoupper(mb_substr($repartidor->nombre, 0, 1)) }}</div>
            <div>
                <div class="hola">{{ $nombreNegocio }}</div>
                <div class="nom">Hola, {{ \Illuminate\Support\Str::of($repartidor->nombre)->explode(' ')->first() }}</div>
            </div>
            <button class="estado-btn" :class="disponible ? 'on' : 'off'" @click="alternar()" type="button"><i></i><span x-text="disponible ? 'Disponible' : 'En pausa'"></span></button>
        </div>
        <div class="resumen">
            <div><b x-text="pendientes.length"></b><span>por llevar</span></div>
            <div><b x-text="enRuta.length"></b><span>en camino</span></div>
            <div><b x-text="hechos.length"></b><span>entregados hoy</span></div>
        </div>
    </header>

    <div class="gps">
        <span class="dot" :class="{ vivo: gps === 'vivo' }"></span>
        <span x-text="gpsTexto"></span>
        <button type="button" x-show="gps !== 'vivo'" @click="activarGps()">Activar</button>
    </div>

    <main>
        {{-- Ruta del día (como el prototipo): paradas en orden, distancia y tiempo estimado --}}
        <section class="ruta-box" x-show="pedidos.length" x-cloak>
            <div class="ruta-hd">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z"/><path d="M8 2v16M16 6v16"/></svg>
                <span x-text="'Ruta del día — ' + paradas.length + (paradas.length === 1 ? ' parada' : ' paradas')"></span>
            </div>
            <div id="mapa-ruta" x-show="conCoordenadas.length" x-ref="mapa"></div>
            <div class="ruta-sin" x-show="!conCoordenadas.length">Los pedidos aún no tienen ubicación en el mapa. Usa el botón Ruta de cada pedido para abrir Google Maps.</div>
            <div class="ruta-chips">
                <span>Distancia <b x-text="distanciaKm.toFixed(1) + ' km'"></b></span>
                <span>T. estimado <b x-text="tiempoTexto"></b></span>
                <span class="cobro">Cobrar en efectivo <b x-text="'S/ ' + porCobrar.toFixed(2)"></b></span>
            </div>
        </section>

        <template x-if="pedidos.length === 0">
            <div class="vacio"><div>🛵</div><b>No tienes pedidos asignados</b><br>Cuando el negocio te asigne uno aparecerá aquí solo.</div>
        </template>

        <template x-for="(grupo, gi) in grupos" :key="grupo.titulo">
            <section x-show="grupo.lista.length">
                <div class="sec" x-text="grupo.titulo"></div>
                <template x-for="(p, i) in grupo.lista" :key="p.id + p.estado">
                    <article class="card" :class="{ ruta: p.estado === 'en_camino', hecho: p.estado === 'entregado' }" :style="'animation-delay:' + (i * 50) + 'ms'">
                        <div class="c-top">
                            <span class="orden" x-show="p.estado !== 'entregado' && orden(p)" x-text="orden(p)"></span>
                            <span class="num" x-text="'#' + p.numero"></span>
                            <span class="chip" :class="{ ruta: p.estado === 'en_camino', listo: p.estado === 'listo', hecho: p.estado === 'entregado' }" x-text="textoEstado(p)"></span>
                            <span class="total" x-text="'S/ ' + p.total.toFixed(2)"></span>
                        </div>
                        <div class="cobrar" :class="{ ok: p.pagado || p.metodo_pago !== 'efectivo' }" x-show="p.estado !== 'entregado'"
                             x-text="p.pagado ? '✓ Ya pagado' : (p.metodo_pago === 'efectivo' ? '💵 Cobrar S/ ' + p.total.toFixed(2) + ' en efectivo' : '✓ Paga con ' + metodo(p.metodo_pago))"></div>
                        <div class="cli" x-text="p.cliente"></div>
                        <div class="dir" x-text="p.direccion || 'Sin dirección'"></div>
                        <div class="nota" x-show="p.notas" x-text="'📝 ' + p.notas"></div>
                        <div class="items" x-show="p.items.length" x-text="p.items.map(i => i.cantidad + '× ' + i.nombre).join(' · ')"></div>
                        <template x-if="p.estado !== 'entregado'">
                            <div>
                                <div class="acc">
                                    <a :href="mapa(p)" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>Ruta</a>
                                    <a :href="p.telefono ? 'tel:+' + tel(p.telefono) : null" :style="!p.telefono && 'opacity:.4;pointer-events:none'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.4 1.8.7 2.7a2 2 0 01-.5 2.1L8.1 9.8a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.7.7a2 2 0 011.7 2z"/></svg>Llamar</a>
                                    <a class="wa" :href="p.telefono ? 'https://wa.me/' + tel(p.telefono) + '?text=' + encodeURIComponent(mensaje(p)) : null" target="_blank" rel="noopener" :style="!p.telefono && 'opacity:.4;pointer-events:none'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 01-12.4 7.4L3 21l2.1-5.5A8.4 8.4 0 1121 11.5z"/></svg>WhatsApp</a>
                                </div>
                                <button class="principal" type="button" x-show="p.estado !== 'en_camino'" :disabled="ocupado" @click="mover(p, 'en_camino')">🛵 Salí a entregar</button>
                                <button class="principal verde" type="button" x-show="p.estado === 'en_camino'" :disabled="ocupado" @click="entregar(p)">✓ Entregado</button>
                            </div>
                        </template>
                        <div class="items" x-show="p.estado === 'entregado'" x-text="'Entregado a las ' + (p.hora || '')"></div>
                    </article>
                </template>
            </section>
        </template>
    </main>

    <div class="modal" x-show="cobro" x-cloak x-transition.opacity @click.self="cobro = null">
        <div class="hoja" x-show="cobro" x-transition>
            <h3>¿Cobraste el pedido?</h3>
            <p x-text="cobro ? 'El cliente debe pagar S/ ' + cobro.total.toFixed(2) + ' en efectivo.' : ''"></p>
            <button class="principal verde" type="button" @click="confirmarEntrega(true)">Sí, cobré y lo entregué</button>
            <button class="sec-btn" type="button" @click="confirmarEntrega(false)">Lo entregué, pero no pagó</button>
        </div>
    </div>

    <div class="toast" x-show="toast" x-cloak x-transition x-text="toast"></div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('appRepartidor', (inicial, disp, urls) => ({
        pedidos: inicial, disponible: disp, ocupado: false, toast: '', cobro: null,
        gps: 'apagado', watch: null, ultimoEnvio: 0,

        get enRuta() { return this.pedidos.filter(p => p.estado === 'en_camino'); },
        get pendientes() { return this.pedidos.filter(p => !['en_camino', 'entregado'].includes(p.estado)); },
        get hechos() { return this.pedidos.filter(p => p.estado === 'entregado'); },
        get grupos() { return [{ titulo: 'En camino', lista: this.enRuta }, { titulo: 'Por llevar', lista: this.pendientes }, { titulo: 'Entregados hoy', lista: this.hechos }]; },
        mapaObj: null, capa: null, yo: null,
        get paradas() { return [...this.enRuta, ...this.pendientes]; },
        get conCoordenadas() { return this.pedidos.filter(p => p.lat && p.lng); },
        orden(p) { const i = this.paradas.findIndex(x => x.id === p.id); return i >= 0 ? i + 1 : null; },
        get porCobrar() { return this.paradas.filter(p => !p.pagado && p.metodo_pago === 'efectivo').reduce((t, p) => t + p.total, 0); },
        km(a, b) { const R = 6371, r = x => x * Math.PI / 180, dLat = r(b[0] - a[0]), dLng = r(b[1] - a[1]);
            const h = Math.sin(dLat / 2) ** 2 + Math.cos(r(a[0])) * Math.cos(r(b[0])) * Math.sin(dLng / 2) ** 2; return 2 * R * Math.asin(Math.sqrt(h)); },
        get puntosRuta() { const pts = this.paradas.filter(p => p.lat && p.lng).map(p => [p.lat, p.lng]); return this.yo ? [this.yo, ...pts] : pts; },
        get distanciaKm() { const pts = this.puntosRuta; let d = 0; for (let i = 1; i < pts.length; i++) d += this.km(pts[i - 1], pts[i]); return d * 1.3; },
        get tiempoTexto() { const min = Math.round(this.distanciaKm / 20 * 60 + this.paradas.length * 5); return min >= 60 ? Math.floor(min / 60) + 'h ' + (min % 60) + 'min' : min + ' min'; },
        cargarMapa() {
            if (!this.conCoordenadas.length) return;
            if (window.L) return this.dibujar();
            const css = document.createElement('link'); css.rel = 'stylesheet'; css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'; document.head.appendChild(css);
            const js = document.createElement('script'); js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'; js.onload = () => this.dibujar(); document.head.appendChild(js);
        },
        dibujar() {
            if (!window.L || !this.$refs.mapa || !this.conCoordenadas.length) return;
            if (!this.mapaObj) {
                this.mapaObj = L.map(this.$refs.mapa, { zoomControl: false, attributionControl: true });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(this.mapaObj);
            }
            if (this.capa) this.capa.remove();
            this.capa = L.layerGroup().addTo(this.mapaObj);
            const icono = (cls, txt) => L.divIcon({ className: '', html: '<div class="pin ' + cls + '">' + txt + '</div>', iconSize: [26, 26], iconAnchor: [13, 13] });
            const linea = this.puntosRuta;
            if (linea.length > 1) L.polyline(linea, { color: '#4B7CF5', weight: 3, dashArray: '6 6' }).addTo(this.capa);
            this.hechos.filter(p => p.lat && p.lng).forEach(p => L.marker([p.lat, p.lng], { icon: icono('h', '✓') }).addTo(this.capa).bindPopup('#' + p.numero + ' · ' + p.cliente));
            this.paradas.forEach((p, i) => { if (p.lat && p.lng) L.marker([p.lat, p.lng], { icon: icono(p.estado === 'en_camino' ? 'r' : 'p', i + 1) }).addTo(this.capa).bindPopup('#' + p.numero + ' · ' + p.cliente); });
            if (this.yo) L.marker(this.yo, { icon: icono('yo', '🛵') }).addTo(this.capa);
            const todos = [...this.conCoordenadas.map(p => [p.lat, p.lng]), ...(this.yo ? [this.yo] : [])];
            this.mapaObj.fitBounds(todos, { padding: [24, 24], maxZoom: 15 });
            setTimeout(() => this.mapaObj.invalidateSize(), 100);
        },
        get gpsTexto() { return { vivo: 'Compartiendo tu ubicación con el negocio', negado: 'Ubicación bloqueada: actívala en tu navegador', apagado: 'Tu ubicación no se está compartiendo', sin: 'Este celular no comparte ubicación' }[this.gps]; },

        iniciar() {
            this.$nextTick(() => this.cargarMapa());
            this.$watch('pedidos', () => this.$nextTick(() => this.cargarMapa()));
            setInterval(() => this.refrescar(), 30000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) this.refrescar(); });
            try { if (localStorage.getItem('gh_rep_gps') === '1') this.activarGps(); } catch (e) {}
        },
        async api(url, method = 'GET', body = null) {
            const r = await fetch(url, { method, headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: body ? JSON.stringify(body) : null });
            let data = {}; try { data = await r.json(); } catch (e) {}
            return { ok: r.ok, data };
        },
        avisar(t) { this.toast = t; clearTimeout(this._t); this._t = setTimeout(() => this.toast = '', 2800); },
        async refrescar() {
            const r = await this.api(urls.pedidos);
            if (!r.ok) return;
            const antes = new Set(this.pedidos.map(p => p.id));
            const nuevos = r.data.data.filter(p => !antes.has(p.id));
            this.pedidos = r.data.data; this.disponible = r.data.disponible;
            if (nuevos.length) { this.avisar('Nuevo pedido asignado: #' + nuevos[0].numero); try { navigator.vibrate && navigator.vibrate([200, 100, 200]); } catch (e) {} }
        },
        async alternar() {
            const r = await this.api(urls.disponible, 'POST');
            if (r.ok) { this.disponible = r.data.disponible; this.avisar(this.disponible ? 'Estás disponible' : 'Quedaste en pausa'); }
        },
        async mover(p, estado, cobrado = null) {
            this.ocupado = true;
            const r = await this.api(urls.estado + '/' + p.id + '/estado', 'PATCH', { estado, cobrado });
            this.ocupado = false;
            if (!r.ok) { this.avisar(r.data.message || 'No se pudo actualizar. Revisa tu internet.'); return; }
            this.pedidos = r.data.data; this.avisar(r.data.message);
            if (estado === 'en_camino' && this.gps !== 'vivo') this.activarGps();
        },
        entregar(p) {
            if (!p.pagado && p.metodo_pago === 'efectivo') { this.cobro = p; return; }
            this.mover(p, 'entregado');
        },
        confirmarEntrega(cobrado) { const p = this.cobro; this.cobro = null; this.mover(p, 'entregado', cobrado); },
        activarGps() {
            if (!('geolocation' in navigator)) { this.gps = 'sin'; return; }
            if (this.watch !== null) return;
            this.watch = navigator.geolocation.watchPosition(pos => {
                this.gps = 'vivo';
                const nuevoYo = [pos.coords.latitude, pos.coords.longitude];
                if (!this.yo || this.km(this.yo, nuevoYo) > 0.1) { this.yo = nuevoYo; this.dibujar(); }
                try { localStorage.setItem('gh_rep_gps', '1'); } catch (e) {}
                if (Date.now() - this.ultimoEnvio < 25000) return;
                this.ultimoEnvio = Date.now();
                this.api(urls.ubicacion, 'POST', { lat: pos.coords.latitude, lng: pos.coords.longitude, precision: pos.coords.accuracy });
            }, err => { this.gps = err.code === 1 ? 'negado' : 'apagado'; this.watch = null; }, { enableHighAccuracy: true, maximumAge: 15000, timeout: 30000 });
        },
        metodo(m) { return { yape: 'Yape', plin: 'Plin', tarjeta: 'tarjeta', mercadopago: 'Mercado Pago', transferencia: 'transferencia' }[m] || 'otro medio'; },
        tel(t) { t = String(t || '').replace(/\D/g, ''); return t.length === 9 ? '51' + t : t; },
        mapa(p) { return p.lat && p.lng ? 'https://www.google.com/maps/dir/?api=1&destination=' + p.lat + ',' + p.lng : 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(p.direccion || ''); },
        mensaje(p) { return 'Hola ' + p.cliente + ', soy ' + @js((string) \Illuminate\Support\Str::of($repartidor->nombre)->explode(' ')->first()) + ' de ' + @js($nombreNegocio) + '. Estoy en camino con tu pedido #' + p.numero + '.'; },
        textoEstado(p) { return { en_camino: 'En camino', listo: 'Listo', preparando: 'Preparando', confirmado: 'Confirmado', pendiente: 'Pendiente', entregado: 'Entregado' }[p.estado] || p.estado; },
    }));
});
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" defer></script>
</body>
</html>
