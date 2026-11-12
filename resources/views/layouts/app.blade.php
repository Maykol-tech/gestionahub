@php
    $negocio = auth()->user()->tenant->nombre ?? 'Mi negocio';
    $iniciales = strtoupper(substr($negocio, 0, 2));
    $plan = auth()->user()->tenant->plan->nombre ?? 'Plan de prueba';
    
    $menu = [
        'Principal' => [
            ['Inicio', 'admin.dashboard'],
            ['Pedidos', 'admin.pedidos'],
            ['Productos', 'admin.productos'],
            ['Clientes', 'admin.clientes'],
            ['Fidelización', 'admin.fidelizacion'],
            ['Servicios', 'admin.servicios'],
            ['Lista de espera', 'admin.espera'],
        ],
        'Control' => [
            ['Punto de venta', 'admin.pos.index'],
            ['Comprobantes', 'admin.comprobantes.index'],
            ['Caja del día', 'admin.caja.index'],
            ['Ingresos manuales', 'admin.ingresos.index'],
            ['Reportes', 'admin.reportes.index'],
            ['Cobros y Pagos', 'admin.cobros.index'],
            ['Repartos', 'admin.repartos.index'],
            ['Mesas y QR', 'admin.mesas.index'],
            ['Producción', 'admin.produccion.index'],
            ['Publicidad', 'admin.publicidad.index'],
            ['Proveedores', 'admin.proveedores.index'],
        ],
        'Presencia' => [
            ['Mi Vitrina', 'admin.vitrina.index'],
            ['Tienda pública', 'admin.tienda.index'],
            ['Entregas', 'admin.entregas.index'],
            ['Pagos manuales', 'admin.pagos.manuales.index'],
            ['Reseñas', 'admin.resenas.index'],
            ['Galería', 'admin.galeria.index'],
            ['Bot WhatsApp', 'admin.bot.index'],
            ['Menús del bot', 'admin.bot.menus.index'],
            ['Prospectar', 'admin.prospectar.index'],
        ],
        'Cuenta' => [
            ['Sucursales', 'admin.sucursales.index'],
            ['Repartidores', 'admin.repartidores.index'],
            ['Horarios', 'admin.horarios.index'],
            ['Planes y facturación', 'admin.planes.index'],
            ['Personalizar', 'admin.personalizar.index'],
            ['Variantes', 'admin.variantes.index'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2B6EF6">
    <title>@yield('titulo', 'Panel') · GestionaHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; }
        .nav-bg { background-color: #2B6EF6; }
        .cta { background-color: #F47B20; }
        .cta:hover { background-color: #d96a10; }
        .nav-scroll::-webkit-scrollbar { width: 6px; }
        .nav-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 3px; }
    </style>
</head>
<body>
<div class="flex h-screen overflow-hidden">
    
    <!-- SIDEBAR -->
    <nav class="w-64 nav-bg text-white flex flex-col h-full shrink-0">
        <div class="p-4 text-xl font-bold border-b border-blue-500 flex items-center gap-2 shrink-0">
            <span class="bg-white text-[#2B6EF6] p-1 rounded text-sm">GH</span> GestionaHub
        </div>
        <div class="flex-1 py-4 overflow-y-auto nav-scroll">
            @foreach ($menu as $seccion => $items)
                <div class="mb-4">
                    <div class="px-4 text-[10px] font-bold text-blue-300 uppercase tracking-wider mb-2">{{ $seccion }}</div>
                    @foreach ($items as $item)
                        @php
                            $texto = $item[0];
                            $destino = $item[1];
                            try {
                                $url = route($destino);
                                $path = parse_url($url, PHP_URL_PATH);
                                $esActiva = request()->is(ltrim($path, '/')) || request()->is(ltrim($path, '/') . '/*');
                            } catch (\Exception $e) {
                                $url = '#';
                                $esActiva = false;
                            }
                        @endphp
                        <a href="{{ $url }}" class="block px-4 py-2 hover:bg-blue-600 transition-colors text-sm {{ $esActiva ? 'bg-blue-700 font-semibold border-l-4 border-white' : '' }}">
                            {{ $texto }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div class="p-4 border-t border-blue-500 text-sm shrink-0">
            <div class="font-bold">{{ $negocio }}</div>
            <div class="text-blue-200 text-xs">{{ $plan }}</div>
        </div>
    </nav>

    <!-- MAIN -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- TOPBAR -->
        <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 shrink-0">
            <div class="flex items-center gap-4">
                <h1 class="text-lg font-bold text-gray-800">@yield('titulo', 'Panel')</h1>
                <span class="text-sm text-gray-500">@yield('subtitulo')</span>
            </div>
            <div class="flex items-center gap-4">
                @yield('acciones')
                
                <!-- CAMPANA -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="p-2 text-gray-400 hover:text-gray-600 relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                        <div class="p-3 border-b border-gray-100 font-bold text-sm flex justify-between">
                            <span>Notificaciones</span>
                            <a href="#" class="text-xs text-[#2B6EF6]">Marcar todas</a>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <a href="/admin/pedidos" class="block p-3 border-b border-gray-50 hover:bg-gray-50">
                                <div class="font-semibold text-sm">Nuevo pedido registrado</div>
                                <div class="text-xs text-gray-500">Pedido #P-0005 · Hace 2 min</div>
                            </a>
                            <a href="/admin/productos" class="block p-3 border-b border-gray-50 hover:bg-gray-50">
                                <div class="font-semibold text-sm">Stock bajo: Alitas BBQ</div>
                                <div class="text-xs text-gray-500">0 unidades · Hace 15 min</div>
                            </a>
                            <a href="/admin/caja" class="block p-3 hover:bg-gray-50">
                                <div class="font-semibold text-sm">Pago Yape por verificar</div>
                                <div class="text-xs text-gray-500">S/ 120.00 · Hace 1 hora</div>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- AVATAR -->
                <div class="w-9 h-9 rounded-full bg-[#2B6EF6] text-white flex items-center justify-center text-sm font-bold cursor-pointer">
                    {{ $iniciales }}
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="flex-1 overflow-y-auto p-6" id="gh-main">
            @yield('contenido')
        </main>
    </div>
</div>

<!-- PANTALLA DE BLOQUEO -->
<div id="screen-lock" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.9); z-index: 99999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 40px; border-radius: 20px; max-width: 400px; text-align: center;">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 10px;">Pantalla Bloqueada</h2>
        <p style="color: #64748B; margin-bottom: 20px; font-size: 14px;">Ingresa tu contraseña para continuar.</p>
        <input type="password" id="unlock-pass" placeholder="Contraseña" style="width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 15px;">
        <button onclick="document.getElementById('screen-lock').style.display='none'" class="cta" style="width: 100%; padding: 12px; border-radius: 8px; color: white; font-weight: bold; border: none; cursor: pointer;">Desbloquear</button>
    </div>
</div>

<script>
    // Navegación instantánea
    document.addEventListener('click', async (e) => {
        const link = e.target.closest('a');
        if (!link || !link.href || !link.href.includes('/admin') || link.target === '_blank' || link.href.includes('#')) return;
        e.preventDefault();
        const main = document.getElementById('gh-main');
        main.innerHTML = '<div class="text-center py-10 text-gray-500">Cargando...</div>';
        try {
            const res = await fetch(link.href, { headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' } });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newMain = doc.getElementById('gh-main');
            if (newMain) {
                main.innerHTML = newMain.innerHTML;
                document.title = doc.title;
                history.pushState({}, '', link.href);
                
                // ACTUALIZAR MENÚ ACTIVO
                document.querySelectorAll('nav a').forEach(a => {
                    a.classList.remove('bg-blue-700', 'font-semibold', 'border-l-4', 'border-white');
                    // Comparar solo la ruta base para evitar problemas con parámetros GET
                    if (a.pathname === link.pathname) {
                        a.classList.add('bg-blue-700', 'font-semibold', 'border-l-4', 'border-white');
                    }
                });
                main.querySelectorAll('script').forEach(s => {
                    const newScript = document.createElement('script');
                    newScript.textContent = s.textContent;
                    document.body.appendChild(newScript).remove();
                });
            } else {
                window.location.href = link.href;
            }
        } catch (err) {
            window.location.href = link.href;
        }
    });
    
    // Bloqueo por inactividad
    let inactivityTimer;
    function resetTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(() => {
            document.getElementById('screen-lock').style.display = 'flex';
        }, 10 * 60 * 1000);
    }
    document.addEventListener('mousemove', resetTimer);
    document.addEventListener('keydown', resetTimer);
    resetTimer();

    // Service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }
</script>
@stack('scripts')
</body>
</html>