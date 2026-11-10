<?php $__env->startSection('titulo', 'Reportes'); ?>
<?php $__env->startSection('subtitulo', $desdeTexto . ' - ' . $hastaTexto); ?>

<?php $__env->startSection('acciones'); ?>
    <div class="seg" role="tablist" aria-label="Periodo">
        <?php $__currentLoopData = [7 => '7 días', 30 => '30 días', 90 => '90 días']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dias => $texto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('admin.modulo', 'reportes')); ?>?rango=<?php echo e($dias); ?>" class="<?php echo e($rango === $dias ? 'on' : ''); ?>"><?php echo e($texto); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <button class="btn btn-g" x-data @click="$dispatch('exportar-reporte')"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'download','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'download','size' => '15']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Exportar</button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
<?php ($maximo = max(1, collect($serie)->max('monto'))); ?>
<div x-data="reportesPage(<?php echo \Illuminate\Support\Js::from($serie)->toHtml() ?>)" @exportar-reporte.window="exportar()">

    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Ventas totales <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'trending','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trending','size' => '14']); ?>
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
            <div class="stat-val">S/ <?php echo e(number_format($ventas, 2)); ?></div>
            <?php if($variacion === null): ?>
                <div class="stat-ch neutral">Sin periodo anterior para comparar</div>
            <?php else: ?>
                <div class="stat-ch <?php echo e($variacion >= 0 ? 'up' : 'dn'); ?>"><?php echo e($variacion >= 0 ? '+' : ''); ?><?php echo e($variacion); ?>% vs. periodo anterior</div>
            <?php endif; ?>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Pedidos <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard','size' => '14']); ?>
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
            <div class="stat-val"><?php echo e($cantidad); ?></div>
            <div class="stat-ch neutral">Sin contar cancelados</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Ticket promedio <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'tag','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tag','size' => '14']); ?>
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
            <div class="stat-val">S/ <?php echo e(number_format($ticket, 2)); ?></div>
            <div class="stat-ch neutral">Venta media por pedido</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Clientes únicos <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
            <div class="stat-val"><?php echo e($clientesUnicos); ?></div>
            <div class="stat-ch neutral">Con al menos un pedido</div>
        </div>
    </div>

    <div class="panel fade-in" style="animation-delay:.18s;margin-bottom:14px;">
        <div class="panel-hd">
            <div>
                <div class="panel-ttl"><?php echo e($rango === 90 ? 'Ventas por semana' : 'Ventas por día'); ?></div>
                <div class="panel-sub">Ultimos <?php echo e($rango); ?> días</div>
            </div>
            <div style="margin-left:auto;font-size:12px;color:var(--fg3);" x-show="sel !== null" x-cloak>
                <span x-text="sel !== null ? serie[sel].detalle : ''"></span> Â·
                <strong style="color:var(--fg);" x-text="sel !== null ? gh.soles(serie[sel].monto) : ''"></strong>
            </div>
        </div>

        <?php if($ventas > 0): ?>
            <div class="chart" @mouseleave="sel = null">
                <?php $__currentLoopData = $serie; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="chart-col" @mouseenter="sel = <?php echo e($i); ?>" :class="{ 'on': sel === <?php echo e($i); ?> }">
                        <div class="chart-bar" style="height:<?php echo e(max(1.5, round($s['monto'] * 100 / $maximo, 1))); ?>%;animation-delay:<?php echo e(min($i * 30, 600)); ?>ms" title="<?php echo e($s['detalle']); ?>: S/ <?php echo e(number_format($s['monto'], 2)); ?>"></div>
                        <div class="chart-lbl"><?php echo e($s['etiqueta']); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="chart chart-off">
                <?php $__currentLoopData = $serie; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="chart-col"><div class="chart-bar" style="height:2px;"></div><div class="chart-lbl"><?php echo e($s['etiqueta']); ?></div></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <div class="chart-vacio">
                    <?php if (isset($component)) { $__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.vacio','data' => ['icono' => 'chart','titulo' => 'Aún no hay ventas en este periodo','texto' => 'El gráfico se completará con los pedidos registrados en los últimos '.e($rango).' días.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('vacio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icono' => 'chart','titulo' => 'Aún no hay ventas en este periodo','texto' => 'El gráfico se completará con los pedidos registrados en los últimos '.e($rango).' días.']); ?>
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
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="g-11">
        <?php $__currentLoopData = [['Ventas por canal', 'globe', $porCanal, 'Aquí verás qué canal (web, WhatsApp, punto de venta) vende más.'], ['Ventas por método de pago', 'card', $porMetodo, 'Aquí verás cuánto entra por efectivo, Yape, Plin y tarjeta.']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j => [$ttl, $ico, $lista, $ayuda]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="panel fade-in" style="animation-delay:<?php echo e(.24 + $j * .05); ?>s">
                <div class="panel-hd"><div class="panel-ttl"><?php echo e($ttl); ?></div></div>
                <?php $__empty_1 = true; $__currentLoopData = $lista; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="kv" style="display:block;">
                        <div style="display:flex;justify-content:space-between;gap:10px;">
                            <span style="font-weight:600;"><?php echo e($f['texto']); ?></span>
                            <span style="font-weight:700;font-variant-numeric:tabular-nums;">S/ <?php echo e(number_format($f['monto'], 2)); ?></span>
                        </div>
                        <div style="font-size:11px;color:var(--fg3);"><?php echo e($f['cantidad']); ?> <?php echo e($f['cantidad'] === 1 ? 'pedido' : 'pedidos'); ?> Â· <?php echo e($ventas > 0 ? round($f['monto'] * 100 / $ventas) : 0); ?>%</div>
                        <div class="hbar"><span style="width:<?php echo e($ventas > 0 ? round($f['monto'] * 100 / $ventas) : 0); ?>%"></span></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php if (isset($component)) { $__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.vacio','data' => ['icono' => $ico,'titulo' => 'Aún no hay datos registrados','texto' => $ayuda]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('vacio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icono' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ico),'titulo' => 'Aún no hay datos registrados','texto' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ayuda)]); ?>
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
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .chart{position:relative;display:flex;align-items:flex-end;gap:6px;height:240px;padding:20px 16px 12px;background:linear-gradient(var(--border) 1px,transparent 1px) 0 20px/100% 52px;}
        .chart-col{flex:1;min-width:0;height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:6px;cursor:default;}
        .chart-bar{width:100%;max-width:42px;background:var(--acm);border-radius:5px 5px 0 0;transform-origin:bottom;animation:barUp .55s var(--ease) backwards;transition:background .15s;}
        .chart-col.on .chart-bar,.chart-col:hover .chart-bar{background:var(--accent);}
        .chart-lbl{font-size:10px;color:var(--fg3);white-space:nowrap;height:14px;}
        .chart-off .chart-bar{background:var(--border);animation:none;}
        .chart-vacio{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.82);}
        @keyframes barUp{from{transform:scaleY(0)}}
        @media (max-width:860px){ .chart{gap:2px;padding:16px 8px 10px;} .chart-lbl{font-size:9px;} }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    gh.ready(() => {
        Alpine.data('reportesPage', (serie) => ({
            serie, sel: null,
            exportar() {
                gh.csv('reporte-<?php echo e($rango); ?>-dias.csv', [['Periodo', 'Ventas'], ...this.serie.map(s => [s.detalle, s.monto])]);
                Alpine.store('toasts').push('Se descargó el reporte', 'info');
            },
        }));
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/reportes.blade.php ENDPATH**/ ?>