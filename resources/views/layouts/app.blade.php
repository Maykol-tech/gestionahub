@php
    $layout = $layout ?? [];
    $negocio = $layout['negocio'] ?? 'Mi negocio';
    $iniciales = $layout['iniciales'] ?? 'GH';
    $plan = $layout['plan'] ?? 'Plan de prueba';
    $pendientes = $layout['pedidos_pendientes'] ?? 0;
    $notificaciones = $layout['notificaciones'] ?? [];
    $actual = request()->route('modulo');
    $ocultos = $layout['ocultos'] ?? [];

    // [texto, icono, ruta o slug de modulo, (opcional) pestañas [[texto, destino], ...]]
    // Las pantallas parecidas van juntas en un solo item con pestañas arriba, asi el menu es corto.
    // Las rutas con nombre "admin." que no existan se ocultan solas.
    $menu = [
        'Principal' => [
            ['Inicio', 'grid', 'admin.dashboard'],
            ['Pedidos', 'clipboard', 'admin.pedidos'],
            ['Productos', 'package', 'admin.productos', [['Productos', 'admin.productos'], ['Variantes y presentaciones', 'admin.variantes']]],
            ['Clientes', 'users', 'admin.clientes'],
            ['Fidelización', 'heart', 'fidelizacion'],
            ['Membresías', 'calendar', 'admin.membresias'],
            ['Servicios', 'wrench', 'servicios'],
            ['Lista de espera', 'clock', 'admin.espera'],
        ],
        'Control' => [
            ['Punto de venta', 'cash', 'admin.pos'],
            ['Caja', 'card', 'caja', [['Caja del día', 'caja'], ['Ingresos manuales', 'admin.ingresos']]],
            ['Comprobantes', 'layers', 'admin.comprobantes'],
            ['Cobros y Pagos', 'clock', 'cobros'],
            ['Reportes', 'chart', 'reportes'],
            ['Repartos', 'truck', 'repartos', [['Repartos y mapa', 'repartos'], ['Repartidores', 'admin.repartidores.crud.index']]],
            ['Mesas y QR', 'grid', 'admin.mesas'],
            ['Cocina', 'clock', 'admin.cocina'],
            ['Producción', 'settings', 'produccion'],
            ['Proveedores', 'home', 'proveedores'],
            ['Publicidad', 'activity', 'publicidad'],
        ],
        'Presencia' => [
            ['Mi página', 'globe', 'vitrina', [['Mi vitrina', 'vitrina'], ['Diseño y textos', 'admin.personalizacion'], ['Galería', 'admin.galeria']]],
            ['Tienda online', 'home', 'tienda', [['Tienda', 'tienda'], ['Contenido y cupones', 'admin.tienda.contenido'], ['Entregas', 'admin.entregas'], ['Pagos por revisar', 'admin.pagos'], ['Carritos abandonados', 'admin.carritos']]],
            ['Reseñas', 'heart', 'admin.resenas'],
            ['WhatsApp', 'bot', 'bot', [['Bot', 'bot'], ['Menús del bot', 'admin.bot.menus'], ['Flujos', 'admin.bot.flujos'], ['Campañas', 'admin.campanas']]],
            ['Prospectos', 'search', 'admin.prospectos', [['Mis prospectos', 'admin.prospectos'], ['Buscar negocios', 'prospectar']]],
        ],
        'Cuenta' => [
            ['Datos del negocio', 'pen', 'personalizar'],
            ['Seguridad', 'shield', 'admin.seguridad'],
            ['Sucursales', 'map-pin', 'sucursales', [['Mis sucursales', 'sucursales'], ['Inventario por sucursal', 'admin.sucursales.inventario']]],
            ['Planes y facturación', 'briefcase', 'planes'],
            ['API y conexiones', 'layers', 'admin.api'],
            ['Asistente de inicio', 'grid', 'admin.bienvenida'],
        ],
    ];

    $menuVisible = function (string $destino) use ($ocultos) {
        if (str_starts_with($destino, 'admin.') && ! \Illuminate\Support\Facades\Route::has($destino)) return false;
        return \App\Support\Roles::ver($destino) && ! in_array($destino, $ocultos, true);
    };
    $menuUrl = fn (string $d) => str_starts_with($d, 'admin.') ? route($d) : route('admin.modulo', $d);
    $menuActivo = fn (string $d) => str_starts_with($d, 'admin.') ? request()->routeIs($d) : $actual === $d;

    // Pestañas de la pantalla actual (si pertenece a un grupo)
    $pestanas = [];
    foreach ($menu as $itemsSec) {
        foreach ($itemsSec as $it) {
            if (empty($it[3])) continue;
            $vis = array_values(array_filter($it[3], fn ($t) => $menuVisible($t[1])));
            if (count($vis) > 1 && collect($vis)->contains(fn ($t) => $menuActivo($t[1]))) $pestanas = $vis;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Panel') · GestionaHub</title>
    @include('layouts.estilos')
    @include('layouts.partials.animaciones')
    @include('layouts.tema')
    @include('admin.partials.pwa_head')
<script>
    window.gh = {
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        alpineListo: false,

        // Registra componentes Alpine tanto en la primera carga como al navegar sin recargar
        ready(fn) {
            if (this.alpineListo) fn();
            else document.addEventListener('alpine:init', fn);
        },

        async api(url, { method = 'GET', body = null } = {}) {
            const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' } };
            if (body instanceof FormData) { opts.body = body; }
            else if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
            let res, data = {};
            try {
                res = await fetch(url, opts);
                data = await res.json().catch(() => ({}));
                if (res.status === 401 || res.status === 419) { location.href = '/login'; }
                // 423: la pantalla esta bloqueada por inactividad
                if (res.status === 423) { window.dispatchEvent(new CustomEvent('gh-bloquear')); }
            } catch (e) {
                return { ok: false, status: 0, data: { message: 'Sin conexión con el servidor' } };
            }
            return { ok: res.ok, status: res.status, data };
        },
        soles(n) { return 'S/ ' + Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        norm(t) { return String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); },
        badgePedidos(n) {
            const b = document.querySelector('[data-badge="pedidos"]');
            if (b) { b.textContent = n; b.style.display = n > 0 ? '' : 'none'; }
        },
        // Descarga un .xlsx con el estilo de GestionaHub (Laravel-Excel). columnas: [['Titulo', 'tipo'], ...]
        // tipos: texto | moneda | numero | decimal | fecha. Si el servidor falla, se descarga un CSV como respaldo.
        async excel(titulo, columnas, filas, { totales = true } = {}) {
            const toast = (t, tipo = 'ok') => Alpine.store('toasts').push(t, tipo);
            if (!filas.length) { toast('No hay datos para exportar', 'info'); return; }
            const cols = columnas.map(c => ({ titulo: c[0], tipo: c[1] || 'texto' }));
            toast('Generando Excel…', 'info');
            try {
                const res = await fetch(@js(\Illuminate\Support\Facades\Route::has('admin.exportar.tabla') ? route('admin.exportar.tabla') : ''), {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ titulo, columnas: cols, filas, totales }),
                });
                if (res.status === 423) { window.dispatchEvent(new CustomEvent('gh-bloquear')); return; }
                if (res.status === 401 || res.status === 419) { location.href = '/login'; return; }
                if (!res.ok) {
                    const d = await res.json().catch(() => ({}));
                    const err = d.errors ? Object.values(d.errors)[0][0] : d.message;
                    toast(err || 'No se pudo generar el Excel', 'error'); return;
                }
                const blob = await res.blob();
                const m = (res.headers.get('Content-Disposition') || '').match(/filename="?([^";]+)"?/);
                const nombre = m ? m[1] : titulo + '.xlsx';
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob); a.download = nombre; document.body.appendChild(a); a.click(); a.remove();
                setTimeout(() => URL.revokeObjectURL(a.href), 1500);
                toast('Se descargó ' + nombre);
            } catch (e) {
                this.csv(titulo + '.csv', [cols.map(c => c.titulo), ...filas]);
                toast('Sin conexión con el servidor: se descargó un CSV de respaldo', 'info');
            }
        },
        iniciales(nombre) { return (nombre || '?').split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase(); },
        csv(nombre, filas) {
            const txt = filas.map(f => f.map(v => '"' + String(v ?? '').replace(/"/g, '""') + '"').join(';')).join('\r\n');
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob(['﻿' + txt], { type: 'text/csv;charset=utf-8' }));
            a.download = nombre; a.click();
            setTimeout(() => URL.revokeObjectURL(a.href), 1000);
        },
    };

    document.addEventListener('alpine:init', () => {
        gh.alpineListo = true;
        Alpine.store('toasts', {
            items: [],
            push(texto, tipo = 'ok') {
                const id = Date.now() + Math.random();
                this.items.push({ id, texto, tipo, visible: true });
                setTimeout(() => { const t = this.items.find(i => i.id === id); if (t) t.visible = false; }, 3200);
                setTimeout(() => { this.items = this.items.filter(i => i.id !== id); }, 3600);
            },
        });
    });
</script>
    @include('publico._gtm')
</head>
<body x-data="{ navOpen: false }" @gh-navegando.window="navOpen = false">
<div id="gh-progress"></div>
<div class="app">
    <div class="nav-overlay" x-show="navOpen" x-transition.opacity @click="navOpen = false" x-cloak></div>

    {{-- ======= SIDEBAR (4 grupos del prototipo) ======= --}}
    <nav class="nav" :class="{ 'open': navOpen }">
        <div class="nav-logo">
            <a href="{{ route('admin.dashboard') }}" class="nav-brand">GestionaHub <small>Panel de control</small></a>
        </div>

        @if (auth()->user()?->hasRole('superadmin'))
            {{-- GEST-90/95/98: herramientas de la plataforma, solo para el superadmin --}}
            <div class="nav-sec">
                <div class="nav-sec-lbl">Plataforma</div>
                @foreach ([['Negocios y planes', 'grid', 'superadmin.panel'], ['Leads', 'users', 'superadmin.leads'], ['Pagos de suscripción', 'card', 'superadmin.suscripciones'], ['Editor de la landing', 'pen', 'superadmin.landing']] as [$texto, $icono, $destino])
                    @continue(! \Illuminate\Support\Facades\Route::has($destino))
                    <a href="{{ route($destino) }}" class="ni {{ request()->routeIs($destino) ? 'on' : '' }}"><x-icon :name="$icono" />{{ $texto }}</a>
                @endforeach
                {{-- Accesos directos a las paginas publicas (se abren en otra pestana) --}}
                <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="ni"><x-icon name="globe" />Ver landing</a>
                <a href="{{ route('registro') }}" target="_blank" rel="noopener" class="ni"><x-icon name="user-plus" />Ver registro</a>
            </div>
        @endif
        @foreach ($menu as $seccion => $items)
            <div class="nav-sec">
                <div class="nav-sec-lbl">{{ $seccion }}</div>
                @foreach ($items as $item)
                    @php
                        [$texto, $icono, $destino] = $item;
                        $tabs = array_values(array_filter($item[3] ?? [], fn ($t) => $menuVisible($t[1])));
                        if ($tabs) { $destino = $tabs[0][1]; }
                        elseif (! empty($item[3])) { continue; }
                    @endphp
                    @continue(! $menuVisible($destino))
                    @php
                        $esRuta = str_starts_with($destino, 'admin.');
                        $url = $menuUrl($destino);
                        $activo = $tabs ? collect($tabs)->contains(fn ($t) => $menuActivo($t[1])) : $menuActivo($destino);
                    @endphp
                    <a href="{{ $url }}" class="ni {{ $activo ? 'on' : '' }}">
                        <x-icon :name="$icono" />{{ $texto }}
                        @if ($destino === 'admin.pedidos')
                            <span class="ni-badge" data-badge="pedidos" @if ($pendientes <= 0) style="display:none" @endif>{{ $pendientes }}</span>
                        @elseif (! $esRuta && ! view()->exists('admin.modulos.' . $destino))
                            <span class="ni-soon" title="En preparación"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach

        <div class="nav-foot">
            <div class="nav-usr">
                <div class="nav-av">{{ $iniciales }}</div>
                <div>
                    <div class="nav-un">{{ $negocio }}</div>
                    <a href="{{ route('admin.modulo', 'planes') }}" class="nav-up">{{ $plan }}</a>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="margin-left:auto;">
                    @csrf
                    <button type="submit" class="nav-out" title="Cerrar sesión" aria-label="Cerrar sesión"><x-icon name="logout" size="16" /></button>
                </form>
            </div>
        </div>
    </nav>

    {{-- ======= CONTENIDO (lo unico que se reemplaza al navegar) ======= --}}
    <div class="main" id="gh-main">
        <header class="topbar">
            <button class="icon-btn menu-btn" @click="navOpen = true" aria-label="Abrir menú"><x-icon name="menu" /></button>
            <span class="topbar-ttl">@yield('titulo', 'Panel')</span>
            <span class="topbar-sub">@yield('subtitulo')</span>

            <div class="tba">
                @yield('acciones')

                @if (\Illuminate\Support\Facades\Route::has('admin.pulso'))
                    <button class="icon-btn" type="button" x-data @click="$store.sonido.alternar()" :title="$store.sonido.on ? 'Sonido de pedidos nuevos: activado' : 'Sonido de pedidos nuevos: apagado'" :aria-label="$store.sonido.on ? 'Silenciar avisos' : 'Activar sonido de avisos'">
                        <span x-show="$store.sonido.on"><x-icon name="volume" /></span>
                        <span x-show="!$store.sonido.on" x-cloak><x-icon name="volume-x" /></span>
                    </button>
                @endif
                <div style="position:relative;" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button class="icon-btn" @click="open = !open" aria-label="Notificaciones">
                        <x-icon name="bell" class="{{ count($notificaciones) ? 'bell-ring' : '' }}" />
                        @if (count($notificaciones))
                            <span class="bell-dot">{{ count($notificaciones) }}</span>
                        @endif
                    </button>
                    <div class="dropdown" x-show="open" x-cloak x-transition.origin.top.right.duration.200ms>
                        <div class="dd-hd"><span>Notificaciones</span><span class="badge b-n">{{ count($notificaciones) }}</span></div>
                        @forelse ($notificaciones as $n)
                            <a href="{{ $n['url'] }}" class="dd-item" @click="open = false">
                                <div class="dd-ico" style="background:{{ $n['fondo'] }};color:{{ $n['color'] }};"><x-icon :name="$n['icono']" size="16" /></div>
                                <div><div class="dd-ttl">{{ $n['titulo'] }}</div><div class="dd-txt">{{ $n['detalle'] }}</div></div>
                            </a>
                        @empty
                            <div class="dd-empty"><x-icon name="check-circle" size="26" /><div style="margin-top:6px;">Todo en orden, no hay alertas</div></div>
                        @endforelse
                    </div>
                </div>

                <div style="position:relative;" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button class="top-av" style="border:none;cursor:pointer;font-family:inherit;" @click="open = !open" title="{{ auth()->user()?->name ?? $negocio }}" aria-label="Mi cuenta">{{ $iniciales }}</button>
                    <div class="dropdown" style="width:250px;" x-show="open" x-cloak x-transition.origin.top.right.duration.200ms>
                        <div style="padding:14px;border-bottom:1px solid var(--border);">
                            <div style="font-weight:700;font-size:13px;">{{ auth()->user()?->name ?? 'Administrador' }}</div>
                            <div style="font-size:12px;color:var(--fg3);overflow:hidden;text-overflow:ellipsis;">{{ auth()->user()?->email }}</div>
                            <div style="font-size:11px;color:var(--fg2);margin-top:4px;">{{ $negocio }}</div>
                        </div>
                        <a href="{{ route('admin.modulo', 'personalizar') }}" class="dd-item" @click="open = false" style="align-items:center;"><x-icon name="pen" size="15" /><span class="dd-ttl">Datos del negocio</span></a>
                        <a href="{{ route('admin.modulo', 'planes') }}" class="dd-item" @click="open = false" style="align-items:center;"><x-icon name="briefcase" size="15" /><span class="dd-ttl">Planes y facturación</span></a>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="dd-item" style="width:100%;border:none;background:none;cursor:pointer;font-family:inherit;color:var(--red);align-items:center;text-align:left;"><x-icon name="logout" size="15" /><span class="dd-ttl">Cerrar sesión</span></button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            @php($prueba = auth()->user()?->hasRole('superadmin') ? \App\Support\Prueba::de(null) : \App\Support\Prueba::de(auth()->user()?->tenant))
            @if ($prueba['en_prueba'] || $prueba['solo_lectura'])
                {{-- GEST-91: aviso de la prueba gratis; al vencer, el panel queda en solo lectura --}}
                @php($urgente = $prueba['solo_lectura'] || ($prueba['dias'] ?? 99) <= 3)
                <div class="prueba-banda {{ $prueba['solo_lectura'] ? 'vencida' : ($urgente ? 'urgente' : '') }}" role="status">
                    <x-icon :name="$prueba['solo_lectura'] ? 'alert' : 'clock'" size="16" />
                    <span>
                        @if ($prueba['solo_lectura'] && $prueba['motivo'] === 'pago')
                            <b>No pudimos cobrar tu plan.</b> Tu panel está en solo lectura: puedes ver todo, pero no guardar cambios.
                        @elseif ($prueba['solo_lectura'])
                            <b>Tu prueba gratis terminó.</b> Tu información está a salvo. Elige un plan para seguir guardando cambios.
                        @elseif ($prueba['dias'] === 0)
                            <b>Tu prueba gratis termina hoy.</b>
                        @else
                            <b>Te {{ $prueba['dias'] === 1 ? 'queda 1 día' : 'quedan ' . $prueba['dias'] . ' días' }} de prueba gratis</b> (hasta el {{ $prueba['vence']->format('d/m') }}).
                        @endif
                    </span>
                    @if (\Illuminate\Support\Facades\Route::has('admin.modulo'))
                        <a href="{{ route('admin.modulo', 'planes') }}" class="btn btn-sm {{ $urgente ? 'btn-p' : 'btn-g' }}">{{ $prueba['solo_lectura'] ? 'Activar mi plan' : 'Ver planes' }}</a>
                    @endif
                </div>
            @endif
            @if (session('solo_lectura'))
                <div class="prueba-banda vencida" role="alert"><x-icon name="alert" size="16" /><span>{{ session('solo_lectura') }}</span></div>
            @endif
            @if (count($pestanas) > 1)
                <nav class="subnav" aria-label="Secciones">
                    @foreach ($pestanas as [$tTexto, $tDestino])
                        <a href="{{ $menuUrl($tDestino) }}" class="{{ $menuActivo($tDestino) ? 'on' : '' }}" @if ($menuActivo($tDestino)) aria-current="page" @endif>{{ $tTexto }}</a>
                    @endforeach
                </nav>
                <script>document.querySelector('.subnav a.on')?.scrollIntoView({ inline: 'center', block: 'nearest' });</script>
            @endif
            @yield('contenido')
        </main>

        {{-- Estilos y scripts de cada pagina: viajan con el contenido al navegar --}}
        <style>
            .prueba-banda { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 14px; margin-bottom:14px; border-radius:12px; font-size:13px; background:#EEF3FF; color:#1E3A8A; border:1px solid #C7D9F8; animation:fadeUp .35s ease both; }
            .prueba-banda span { flex:1; min-width:200px; }
            .prueba-banda.urgente { background:#FFF7ED; color:#9A3412; border-color:#FED7AA; }
            .prueba-banda.vencida { background:#FEF2F2; color:#991B1B; border-color:#FECACA; }
        </style>
        @stack('styles')
        @stack('scripts')
    </div>
</div>

<div class="toasts" x-data>
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div class="toast fade-in" x-show="t.visible" x-transition.duration.250ms>
            <span class="tdot" :style="'background:' + (t.tipo === 'error' ? 'var(--red)' : (t.tipo === 'info' ? 'var(--accent)' : 'var(--green)'))">
                <x-icon name="check" size="13" stroke="3" />
            </span>
            <span x-text="t.texto"></span>
        </div>
    </template>
</div>

{{-- La campana flotante antigua (admin.partials.campana) se quito: duplicaba la campana de la barra superior
     y su script nunca cargaba porque se incluia despues del @stack('scripts'). --}}

@auth
    @include('layouts.partials.bloqueo')
    @include('layouts.partials.sonido')
@endauth

<script>
    // ===== Navegacion instantanea (sin recargar la pagina) =====
    // Al pasar el mouse sobre un enlace se pide la pagina por adelantado;
    // al hacer clic solo se reemplaza el bloque de contenido: el sidebar no se vuelve a dibujar.
    (() => {
        const cache = new Map();
        const barra = document.getElementById('gh-progress');
        let enCurso = 0;

        const esInterno = (a) => a && a.href && a.origin === location.origin && a.pathname.startsWith('/admin')
            && !a.target && !a.hasAttribute('download') && !a.dataset.recargar && a.getAttribute('href') !== '#';

        const pedir = (url) => {
            const c = cache.get(url);
            if (c && Date.now() - c.t < 15000) return c.p;
            const p = fetch(url, { headers: { 'X-GH-Nav': '1' }, credentials: 'same-origin' })
                .then(r => (r.ok && !r.redirected) ? r.text() : Promise.reject(r));
            cache.set(url, { t: Date.now(), p });
            p.catch(() => cache.delete(url));
            return p;
        };

        const progreso = (v) => {
            if (v === null) { barra.style.width = '100%'; setTimeout(() => { barra.style.opacity = 0; }, 180); setTimeout(() => { barra.style.width = 0; }, 450); return; }
            barra.style.opacity = 1; barra.style.width = v + '%';
        };

        async function ir(url, empujar = true) {
            const id = ++enCurso;
            const main = document.getElementById('gh-main');
            window.dispatchEvent(new CustomEvent('gh-navegando'));
            progreso(25);
            const lento = setTimeout(() => { if (id === enCurso) { main.classList.add('saliendo'); progreso(70); } }, 120);
            let html;
            try { html = await pedir(url); }
            catch (e) { location.href = url; return; }
            finally { clearTimeout(lento); }
            if (id !== enCurso) return;
            cache.delete(url);

            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nuevo = doc.getElementById('gh-main');
            if (!nuevo) { location.href = url; return; }

            // 1) Ejecuta los scripts de la pagina nueva (registran sus componentes Alpine)
            nuevo.querySelectorAll('script').forEach(viejo => {
                const s = document.createElement('script');
                s.textContent = viejo.textContent;
                document.head.appendChild(s).remove();
                viejo.remove();
            });
            // 2) Cambia el contenido (Alpine inicializa lo nuevo y limpia lo anterior)
            main.replaceWith(document.adoptNode(nuevo));
            document.title = doc.title;
            setTimeout(() => window.ghAnim && ghAnim.animar(), 0);
            // 3) Sincroniza el sidebar: opcion activa y contador de pedidos
            const links = document.querySelectorAll('.nav a.ni');
            doc.querySelectorAll('.nav a.ni').forEach((a, i) => { if (links[i]) links[i].className = a.className; });
            const b = doc.querySelector('[data-badge="pedidos"]');
            if (b) gh.badgePedidos(Number(b.textContent) || 0);
            if (empujar) history.pushState({ gh: 1 }, '', url);
            progreso(null);
        }
        gh.ir = ir;

        document.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!esInterno(a) || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();
            if (a.href === location.href && !a.search) return;
            ir(a.href);
        });

        let timer;
        document.addEventListener('pointerover', (e) => {
            const a = e.target.closest && e.target.closest('a');
            if (!esInterno(a) || a.href === location.href) return;
            clearTimeout(timer);
            timer = setTimeout(() => pedir(a.href), 60);
        });
        document.addEventListener('pointerdown', (e) => {
            const a = e.target.closest && e.target.closest('a');
            if (esInterno(a)) pedir(a.href);
        });

        history.replaceState({ gh: 1 }, '', location.href);
        window.addEventListener('popstate', (e) => { if (e.state && e.state.gh) ir(location.href, false); });

        // Alpine.js se carga al final para que todo lo anterior ya este registrado
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js';
        document.body.appendChild(s);
    })();
</script>
</body>
</html>
