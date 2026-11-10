<?php $__env->startSection('titulo', $saludo . ', ' . ($layout['negocio'] ?? 'Mi negocio')); ?>

<?php $__env->startSection('acciones'); ?>
    <a href="<?php echo e(route('admin.productos')); ?>?nuevo=1" class="btn btn-p"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'plus','size' => '15','stroke' => '2.4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'plus','size' => '15','stroke' => '2.4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Nuevo producto</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="stat-row">
        <div class="stat fade-in" style="animation-delay:.02s">
            <div class="stat-lbl">Ventas hoy</div>
            <div class="stat-val">S/ <?php echo e(number_format($datos['ventas_hoy'], 2)); ?></div>
            <div class="stat-ch <?php echo e($datos['ventas_variacion'] >= 0 ? 'up' : 'dn'); ?>">
                <?php echo e($datos['ventas_variacion'] >= 0 ? ' ‘' : ' “'); ?> <?php echo e(abs($datos['ventas_variacion'])); ?>% vs ayer
            </div>
        </div>
        <div class="stat fade-in" style="animation-delay:.08s">
            <div class="stat-lbl">Pedidos hoy</div>
            <div class="stat-val"><?php echo e($datos['pedidos_hoy']); ?></div>
            <div class="stat-ch neutral"><?php echo e($datos['pedidos_pendientes']); ?> <?php echo e($datos['pedidos_pendientes'] === 1 ? 'pendiente' : 'pendientes'); ?></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.14s">
            <div class="stat-lbl">Clientes nuevos</div>
            <div class="stat-val"><?php echo e($datos['clientes_nuevos']); ?></div>
            <div class="stat-ch up"> ‘ esta semana · <?php echo e($datos['clientes_total']); ?> en total</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.2s">
            <div class="stat-lbl">Productos activos</div>
            <div class="stat-val"><?php echo e($datos['productos_activos']); ?></div>
            <div class="stat-ch <?php echo e($stockBajo->count() ? 'dn' : 'neutral'); ?>"><?php echo e($stockBajo->count()); ?> con stock bajo</div>
        </div>
    </div>

    <div class="card fade-in" style="background:#F0F5FF;border-color:#C7D9F8;border-left:4px solid var(--accent);margin-bottom:16px;animation-delay:.25s">
        <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--accent);display:flex;align-items:center;gap:6px;margin-bottom:6px;">
            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'sparkles','size' => '13']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'sparkles','size' => '13']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> IA Â· Resumen semanal
        </div>
        <div style="font-size:15px;font-weight:700;">
            <?php if($datos['ventas_semana'] > 0): ?>
                Esta semana vendiste S/ <?php echo e(number_format($datos['ventas_semana'], 2)); ?> en <?php echo e($datos['pedidos_semana']); ?> pedidos
            <?php else: ?>
                Registra tus primeros pedidos para generar el resumen automático
            <?php endif; ?>
        </div>
    </div>

    <div class="two">
        <div class="fade-in" style="animation-delay:.3s">
            <div class="sec-ttl" style="display:flex;justify-content:space-between;">
                Ultimos pedidos <a href="<?php echo e(route('admin.pedidos')); ?>" style="color:var(--accent);font-size:12px;font-weight:600;">Ver todos</a>
            </div>
            <div class="card tbl-wrap" style="padding:0;">
                <?php if($ultimosPedidos->isEmpty()): ?>
                    <div class="empty"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'inbox','size' => '30']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'inbox','size' => '30']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><span>Aún no hay pedidos registrados</span></div>
                <?php else: ?>
                    <table class="tbl">
                        <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php $__currentLoopData = $ultimosPedidos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td style="font-weight:700;">#<?php echo e($p['numero']); ?></td>
                                <td><?php echo e($p['cliente']); ?></td>
                                <td style="font-weight:600;">S/ <?php echo e(number_format($p['total'], 2)); ?></td>
                                <td><span class="badge <?php echo e($p['badge']); ?>"><?php echo e($p['estado_texto']); ?></span></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="fade-in" style="animation-delay:.36s">
            <div class="sec-ttl" style="display:flex;justify-content:space-between;">
                Stock bajo ” alerta <a href="<?php echo e(route('admin.productos')); ?>" style="color:var(--accent);font-size:12px;font-weight:600;">Ver productos</a>
            </div>
            <div class="card tbl-wrap" style="padding:0;">
                <?php if($stockBajo->isEmpty()): ?>
                    <div class="empty"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check-circle','size' => '30']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check-circle','size' => '30']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><span>Todo el stock está en orden</span></div>
                <?php else: ?>
                    <table class="tbl">
                        <thead><tr><th>Producto</th><th>Stock</th><th></th></tr></thead>
                        <tbody>
                        <?php $__currentLoopData = $stockBajo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($prod->nombre); ?></td>
                                <td><span class="badge <?php echo e($prod->stock <= 0 ? 'b-r' : 'b-a'); ?>"><?php echo e($prod->stock); ?> und.</span></td>
                                <td style="text-align:right;"><a href="<?php echo e(route('admin.productos')); ?>?editar=<?php echo e($prod->id); ?>" class="btn btn-g btn-sm">Reponer</a></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>