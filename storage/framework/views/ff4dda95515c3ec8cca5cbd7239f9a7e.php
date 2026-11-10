<?php $__env->startSection('titulo', 'Repartos'); ?>
<?php $__env->startSection('subtitulo', count($enCamino) . (count($enCamino) === 1 ? ' pedido en camino' : ' pedidos en camino')); ?>

<?php $__env->startSection('acciones'); ?>
    <a href="<?php echo e(route('admin.pedidos')); ?>" class="btn btn-g"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard','size' => '15']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Ver tablero</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
<div x-data="{ buscar: '' }">
    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">En camino <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'truck','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'truck','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span></div>
            <div class="stat-val"><?php echo e(count($enCamino)); ?></div>
            <div class="stat-ch neutral">Pedidos en ruta ahora</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Por cobrar en ruta <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'cash','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cash','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span></div>
            <div class="stat-val">S/ <?php echo e(number_format($porCobrarRuta, 2)); ?></div>
            <div class="stat-ch neutral">Contra entrega</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Entregados hoy <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check-circle','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check-circle','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span></div>
            <div class="stat-val"><?php echo e($entregadosHoy); ?></div>
            <div class="stat-ch neutral">Desde las 00:00</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Repartidores <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'users','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'users','size' => '14']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span></div>
            <div class="stat-val"><?php echo e(count($repartidores)); ?></div>
            <div class="stat-ch neutral"><?php echo e($hayRepartidores ? 'Registrados' : 'Aún sin registrar'); ?></div>
        </div>
    </div>

    <div class="g-21">
        <div class="panel fade-in" style="animation-delay:.18s">
            <div class="panel-hd">
                <div>
                    <div class="panel-ttl">Pedidos en camino</div>
                    <div class="panel-sub">Ordenados por hora de salida</div>
                </div>
                <?php if(count($enCamino)): ?>
                    <div class="search" style="margin-left:auto;">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'search','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','size' => '15']); ?>
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
                        <input class="inp" placeholder="Buscar pedido o repartidor¦" x-model="buscar">
                    </div>
                <?php endif; ?>
            </div>
            <?php if(count($enCamino)): ?>
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead><tr><th>Pedido</th><th>Cliente y destino</th><th>Repartidor</th><th class="num">Monto</th><th>Pago</th></tr></thead>
                        <tbody>
                            <?php $__currentLoopData = $enCamino; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="row-in" style="--d:<?php echo e(min($i * 50, 500)); ?>ms"
                                    x-show="!buscar || gh.norm(<?php echo \Illuminate\Support\Js::from($p['numero'] . ' ' . $p['cliente'] . ' ' . ($p['repartidor'] ?? ''))->toHtml() ?>).includes(gh.norm(buscar))">
                                    <td>
                                        <div style="font-weight:700;">#<?php echo e($p['numero']); ?></div>
                                        <div style="font-size:11px;color:var(--fg3);">Salió <?php echo e($p['salida']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo e($p['cliente']); ?></div>
                                        <div style="font-size:11px;color:var(--fg3);"><?php echo e($p['direccion'] ?: ($p['distrito'] ?: 'Sin dirección registrada')); ?></div>
                                    </td>
                                    <td>
                                        <?php if($p['repartidor']): ?>
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <span class="rav"><?php echo e(mb_strtoupper(mb_substr($p['repartidor'], 0, 1))); ?></span>
                                                <span><?php echo e($p['repartidor']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge b-n">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="num" style="font-weight:700;">S/ <?php echo e(number_format($p['total'], 2)); ?></td>
                                    <td><span class="badge <?php echo e($p['pagado'] ? 'b-ok' : 'b-a'); ?>"><span class="dot"></span><?php echo e($p['pagado'] ? 'Pagado' : 'Cobrar'); ?></span></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (isset($component)) { $__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.vacio','data' => ['icono' => 'truck','titulo' => 'No hay pedidos en camino','texto' => 'Cuando muevas un pedido a Â«En caminoÂ» en el tablero, aparecerá aquí con su repartidor.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('vacio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icono' => 'truck','titulo' => 'No hay pedidos en camino','texto' => 'Cuando muevas un pedido a Â«En caminoÂ» en el tablero, aparecerá aquí con su repartidor.']); ?>
                    <a href="<?php echo e(route('admin.pedidos')); ?>" class="btn btn-g btn-sm">Ir al tablero de pedidos</a>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655)): ?>
<?php $attributes = $__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655; ?>
<?php unset($__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655)): ?>
<?php $component = $__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655; ?>
<?php unset($__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655); ?>
<?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="panel fade-in" style="animation-delay:.24s">
            <div class="panel-hd"><div class="panel-ttl">Repartidores</div></div>
            <?php $__empty_1 = true; $__currentLoopData = $repartidores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="kv">
                    <span class="rav"><?php echo e(mb_strtoupper(mb_substr($r['nombre'], 0, 1))); ?></span>
                    <div style="min-width:0;">
                        <div style="font-weight:600;"><?php echo e($r['nombre']); ?></div>
                        <div style="font-size:11px;color:var(--fg3);"><?php echo e($r['telefono'] ?: 'Sin teléfono'); ?></div>
                    </div>
                    <span class="kv-v"><span class="badge <?php echo e($r['en_ruta'] ? 'b-g' : 'b-n'); ?>"><?php echo e($r['en_ruta'] ? $r['en_ruta'] . ' en ruta' : 'Disponible'); ?></span></span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php if (isset($component)) { $__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.vacio','data' => ['icono' => 'users','titulo' => 'Aún no hay repartidores registrados','texto' => 'Registra a tu equipo de reparto para asignarle pedidos y ver sus rutas.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('vacio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icono' => 'users','titulo' => 'Aún no hay repartidores registrados','texto' => 'Registra a tu equipo de reparto para asignarle pedidos y ver sus rutas.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655)): ?>
<?php $attributes = $__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655; ?>
<?php unset($__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655)): ?>
<?php $component = $__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655; ?>
<?php unset($__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655); ?>
<?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .rav{width:28px;height:28px;border-radius:50%;background:var(--acl);color:var(--accent);font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;}
    </style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/repartos.blade.php ENDPATH**/ ?>