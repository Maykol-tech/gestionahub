<?php
    $layout = $layout ?? [];
    $negocio = $layout['negocio'] ?? 'Mi negocio';
    $iniciales = $layout['iniciales'] ?? 'GH';
    $plan = $layout['plan'] ?? 'Plan de prueba';
    $pendientes = $layout['pedidos_pendientes'] ?? 0;
    $notificaciones = $layout['notificaciones'] ?? [];
    $actual = request()->route('modulo');

    // [texto, icono, ruta o slug de modulo]
    // Las rutas con nombre "admin." que no existan se ocultan solas (ver el @continue del menu).
    $menu = [
        'Principal' => [
            ['Inicio', 'grid', 'admin.dashboard'],
            ['Pedidos', 'clipboard', 'admin.pedidos'],
            ['Productos', 'package', 'admin.productos'],
            ['Clientes', 'users', 'admin.clientes'],
            ['Fidelización', 'heart', 'fidelizacion'],
            ['Servicios', 'wrench', 'servicios'],
            ['Lista de espera', 'clock', 'admin.espera'],
        ],
        'Control' => [
            ['Caja del día', 'card', 'caja'],
            ['Ingresos manuales', 'card', 'admin.ingresos'],
            ['Reportes', 'chart', 'reportes'],
            ['Cobros y Pagos', 'clock', 'cobros'],
            ['Repartos', 'truck', 'repartos'],
            ['Producción', 'settings', 'produccion'],
            ['Publicidad', 'activity', 'publicidad'],
            ['Proveedores', 'home', 'proveedores'],
        ],
        'Presencia' => [
            ['Mi Vitrina', 'globe', 'vitrina'],
            ['Tienda pública', 'home', 'tienda'],
            ['Entregas', 'truck', 'admin.entregas'],
            ['Pagos manuales', 'card', 'admin.pagos'],
            ['Landing del negocio', 'home', 'admin.landing-negocio'],
            ['Reseñas', 'heart', 'admin.resenas'],
            ['Galería', 'globe', 'admin.galeria'],
            ['Bot WhatsApp', 'bot', 'bot'],
            ['Menús del bot', 'bot', 'admin.bot.menus'],
            ['Prospectar', 'search', 'prospectar'],
        ],
        'Cuenta' => [
            ['Sucursales', 'map-pin', 'sucursales'],
            ['Planes y facturación', 'briefcase', 'planes'],
            ['Personalizar', 'pen', 'personalizar'],
            ['Variantes', 'package', 'admin.variantes'],
        ],
    ];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('titulo', 'Panel'); ?> · GestionaHub</title>
    <?php echo $__env->make('layouts.estilos', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('layouts.tema', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('admin.partials.pwa_head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
            } catch (e) {
                return { ok: false, status: 0, data: { message: 'Sin conexión con el servidor' } };
            }
            return { ok: res.ok, status: res.status, data };
        },
        soles(n) { return 'S/ ' + Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        norm(t) { return String(t || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); },
        badgePedidos(n) {
            const b = document.querySelector('[data-badge="pedidos"]');
            if (b) { b.textContent = n; b.style.display = n > 0 ? '' : 'none'; }
        },
        iniciales(nombre) { return (nombre || '?').split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase(); },
        csv(nombre, filas) {
            const txt = filas.map(f => f.map(v => '"' + String(v ?? '').replace(/"/g, '""') + '"').join(';')).join('\r\n');
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob(['\uFEFF' + txt], { type: 'text/csv;charset=utf-8' }));
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

<style>
    /* ELIMINAR CAMPANA FLOTANTE DEFINITIVAMENTE */
    div[style*="position: fixed"][style*="bottom"],
    button[style*="position: fixed"][style*="bottom"],
    .floating-notification, .notification-bell, .fab, .fixed-bottom, .floating-btn, .fixed-bottom-left {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        z-index: -9999 !important;
    }
    body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
</style>
</head>
<body x-data="{ navOpen: false }" @gh-navegando.window="navOpen = false">
<div id="gh-progress"></div>
<div class="app">
    <div class="nav-overlay" x-show="navOpen" x-transition.opacity @click="navOpen = false" x-cloak></div>

    
    <nav class="nav" :class="{ 'open': navOpen }">
        <div class="nav-logo">
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="nav-brand">GestionaHub <small>Panel de control</small></a>
        </div>

        <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seccion => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="nav-sec">
                <div class="nav-sec-lbl"><?php echo e($seccion); ?></div>
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$texto, $icono, $destino]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(str_starts_with($destino, 'admin.') && ! \Illuminate\Support\Facades\Route::has($destino)) continue; ?>
                    <?php if(! \App\Support\Roles::ver($destino)) continue; ?>
                    <?php
                        $esRuta = str_starts_with($destino, 'admin.');
                        $url = $esRuta ? route($destino) : route('admin.modulo', $destino);
                        $activo = $esRuta ? request()->routeIs($destino) : $actual === $destino;
                    ?>
                    <a href="<?php echo e($url); ?>" class="ni <?php echo e($activo ? 'on' : ''); ?>">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => $icono]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icono)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php echo e($texto); ?>

                        <?php if($destino === 'admin.pedidos'): ?>
                            <span class="ni-badge" data-badge="pedidos" <?php if($pendientes <= 0): ?> style="display:none" <?php endif; ?>><?php echo e($pendientes); ?></span>
                        <?php elseif(! $esRuta && ! view()->exists('admin.modulos.' . $destino)): ?>
                            <span class="ni-soon" title="En preparación"></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div class="nav-foot">
            <div class="nav-usr">
                <div class="nav-av"><?php echo e($iniciales); ?></div>
                <div>
                    <div class="nav-un"><?php echo e($negocio); ?></div>
                    <a href="<?php echo e(route('admin.modulo', 'planes')); ?>" class="nav-up"><?php echo e($plan); ?></a>
                </div>
                <form method="POST" action="<?php echo e(route('logout')); ?>" style="margin-left:auto;">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="nav-out" title="Cerrar sesión" aria-label="Cerrar sesión"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'logout','size' => '16']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'logout','size' => '16']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></button>
                </form>
            </div>
        </div>
    </nav>

    
    <div class="main" id="gh-main">
        <header class="topbar">
            <button class="icon-btn menu-btn" @click="navOpen = true" aria-label="Abrir menú"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'menu']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'menu']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></button>
            <span class="topbar-ttl"><?php echo $__env->yieldContent('titulo', 'Panel'); ?></span>
            <span class="topbar-sub"><?php echo $__env->yieldContent('subtitulo'); ?></span>

            <div class="tba">
                <?php echo $__env->yieldContent('acciones'); ?>

                <div style="position:relative;" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button class="icon-btn" @click="open = !open" aria-label="Notificaciones">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'bell','class' => ''.e(count($notificaciones) ? 'bell-ring' : '').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell','class' => ''.e(count($notificaciones) ? 'bell-ring' : '').'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        <?php if(count($notificaciones)): ?>
                            <span class="bell-dot"><?php echo e(count($notificaciones)); ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown" x-show="open" x-cloak x-transition.origin.top.right.duration.200ms>
                        <div class="dd-hd"><span>Notificaciones</span><span class="badge b-n"><?php echo e(count($notificaciones)); ?></span></div>
                        <?php $__empty_1 = true; $__currentLoopData = $notificaciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <a href="<?php echo e($n['url']); ?>" class="dd-item" @click="open = false">
                                <div class="dd-ico" style="background:<?php echo e($n['fondo']); ?>;color:<?php echo e($n['color']); ?>;"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => $n['icono'],'size' => '16']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($n['icono']),'size' => '16']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></div>
                                <div><div class="dd-ttl"><?php echo e($n['titulo']); ?></div><div class="dd-txt"><?php echo e($n['detalle']); ?></div></div>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="dd-empty"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check-circle','size' => '26']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check-circle','size' => '26']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><div style="margin-top:6px;">Todo en orden, no hay alertas</div></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="position:relative;" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button class="top-av" style="border:none;cursor:pointer;font-family:inherit;" @click="open = !open" title="<?php echo e(auth()->user()?->name ?? $negocio); ?>" aria-label="Mi cuenta"><?php echo e($iniciales); ?></button>
                    <div class="dropdown" style="width:250px;" x-show="open" x-cloak x-transition.origin.top.right.duration.200ms>
                        <div style="padding:14px;border-bottom:1px solid var(--border);">
                            <div style="font-weight:700;font-size:13px;"><?php echo e(auth()->user()?->name ?? 'Administrador'); ?></div>
                            <div style="font-size:12px;color:var(--fg3);overflow:hidden;text-overflow:ellipsis;"><?php echo e(auth()->user()?->email); ?></div>
                            <div style="font-size:11px;color:var(--fg2);margin-top:4px;"><?php echo e($negocio); ?></div>
                        </div>
                        <a href="<?php echo e(route('admin.modulo', 'personalizar')); ?>" class="dd-item" @click="open = false" style="align-items:center;"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'pen','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pen','size' => '15']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><span class="dd-ttl">Personalizar negocio</span></a>
                        <a href="<?php echo e(route('admin.modulo', 'planes')); ?>" class="dd-item" @click="open = false" style="align-items:center;"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'briefcase','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','size' => '15']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><span class="dd-ttl">Planes y facturación</span></a>
                        <form method="POST" action="<?php echo e(route('logout')); ?>" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="dd-item" style="width:100%;border:none;background:none;cursor:pointer;font-family:inherit;color:var(--red);align-items:center;text-align:left;"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'logout','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'logout','size' => '15']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><span class="dd-ttl">Cerrar sesión</span></button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <?php echo $__env->yieldContent('contenido'); ?>
        </main>

        
        <?php echo $__env->yieldPushContent('styles'); ?>
        <?php echo $__env->yieldPushContent('scripts'); ?>
    </div>
</div>

<div class="toasts" x-data>
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div class="toast fade-in" x-show="t.visible" x-transition.duration.250ms>
            <span class="tdot" :style="'background:' + (t.tipo === 'error' ? 'var(--red)' : (t.tipo === 'info' ? 'var(--accent)' : 'var(--green)'))">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check','size' => '13','stroke' => '3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check','size' => '13','stroke' => '3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
            </span>
            <span x-text="t.texto"></span>
        </div>
    </template>
</div>

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
</html><?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>