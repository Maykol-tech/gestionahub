<?php $__env->startSection('titulo', 'Caja del día'); ?>
<?php $__env->startSection('subtitulo', $fechaTexto); ?>

<?php $__env->startSection('acciones'); ?>
    <input type="date" class="inp inp-date" value="<?php echo e($fecha); ?>" max="<?php echo e(now()->toDateString()); ?>"
           onchange="this.value && gh.ir('<?php echo e(route('admin.modulo', 'caja')); ?>?fecha=' + this.value)" aria-label="Fecha de caja">
    <button class="btn btn-g" x-data @click="$dispatch('exportar-caja')"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
    <button class="btn btn-p" x-data @click="$store.toasts.push('El registro de gastos estará disponible pronto', 'info')"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
<?php endif; ?> Registrar gasto</button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
<div x-data="cajaPage(<?php echo \Illuminate\Support\Js::from($movimientos)->toHtml() ?>)" @exportar-caja.window="exportar()">

    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Ingresos <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
            <div class="stat-val">S/ <?php echo e(number_format($ingresos, 2)); ?></div>
            <div class="stat-ch neutral"><?php echo e(count($movimientos)); ?> <?php echo e(count($movimientos) === 1 ? 'pedido cobrado' : 'pedidos cobrados'); ?></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Egresos <span class="stat-ico" style="background:var(--redl);color:var(--red)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
            <div class="stat-val">S/ <?php echo e(number_format($egresos, 2)); ?></div>
            <div class="stat-ch neutral">Sin gastos registrados</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Resultado <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chart','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chart','size' => '14']); ?>
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
            <div class="stat-val" style="color:<?php echo e($resultado < 0 ? 'var(--red)' : 'var(--fg)'); ?>">S/ <?php echo e(number_format($resultado, 2)); ?></div>
            <div class="stat-ch neutral">Ingresos menos egresos</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Por cobrar <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clock','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clock','size' => '14']); ?>
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
            <div class="stat-val">S/ <?php echo e(number_format($porCobrar, 2)); ?></div>
            <div class="stat-ch neutral">Pedidos del día sin cobrar</div>
        </div>
    </div>

    <div class="g-21">
        <div class="panel fade-in" style="animation-delay:.18s">
            <div class="panel-hd" style="padding-bottom:0;border-bottom:none;">
                <div>
                    <div class="panel-ttl">Movimientos</div>
                    <div class="panel-sub"><?php echo e($esHoy ? 'Hoy' : $fechaTexto); ?></div>
                </div>
            </div>
            <div class="tabs">
                <button class="tab" :class="{ on: tab === 'todos' }" @click="tab = 'todos'">Todos<span class="n" x-text="movimientos.length"></span></button>
                <button class="tab" :class="{ on: tab === 'ingreso' }" @click="tab = 'ingreso'">Ingresos<span class="n" x-text="contar('ingreso')"></span></button>
                <button class="tab" :class="{ on: tab === 'egreso' }" @click="tab = 'egreso'">Egresos<span class="n" x-text="contar('egreso')"></span></button>
            </div>

            <div class="tbl-wrap" x-show="filtrados.length" x-cloak>
                <table class="tbl">
                    <thead><tr><th style="width:70px;">Hora</th><th>Concepto</th><th>Método</th><th class="num">Monto</th></tr></thead>
                    <tbody>
                        <template x-for="(m, i) in filtrados" :key="i">
                            <tr class="row-in" :style="'--d:' + Math.min(i * 50, 500) + 'ms'">
                                <td style="color:var(--fg3);font-variant-numeric:tabular-nums;" x-text="m.hora"></td>
                                <td>
                                    <div style="font-weight:600;" x-text="m.concepto"></div>
                                    <div style="font-size:11px;color:var(--fg3);" x-text="m.detalle"></div>
                                </td>
                                <td><span class="badge b-n" x-text="m.metodo_texto"></span></td>
                                <td class="num" style="font-weight:700;" :style="m.tipo === 'egreso' ? 'color:var(--red)' : 'color:var(--green)'"
                                    x-text="(m.tipo === 'egreso' ? 'âˆ’ ' : '+ ') + gh.soles(m.monto)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="!filtrados.length" x-cloak>
                <?php if (isset($component)) { $__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.vacio','data' => ['icono' => 'inbox','titulo' => 'Aún no hay movimientos registrados','texto' => 'Los pedidos cobrados de esta fecha aparecerán aquí automáticamente. El registro de gastos estará disponible pronto.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('vacio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icono' => 'inbox','titulo' => 'Aún no hay movimientos registrados','texto' => 'Los pedidos cobrados de esta fecha aparecerán aquí automáticamente. El registro de gastos estará disponible pronto.']); ?>
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

        <div class="panel fade-in" style="animation-delay:.24s">
            <div class="panel-hd"><div class="panel-ttl">Por método de pago</div></div>
            <?php $__empty_1 = true; $__currentLoopData = $porMetodo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="kv" style="display:block;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-weight:600;"><?php echo e($m['texto']); ?></span>
                        <span style="font-weight:700;font-variant-numeric:tabular-nums;">S/ <?php echo e(number_format($m['monto'], 2)); ?></span>
                    </div>
                    <div style="font-size:11px;color:var(--fg3);"><?php echo e($m['cantidad']); ?> <?php echo e($m['cantidad'] === 1 ? 'operación' : 'operaciones'); ?></div>
                    <div class="hbar"><span style="width:<?php echo e($ingresos > 0 ? round($m['monto'] * 100 / $ingresos) : 0); ?>%"></span></div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php if (isset($component)) { $__componentOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7da4e1918a8f3a573ecf5e52d90ff655 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.vacio','data' => ['icono' => 'card','titulo' => 'Sin cobros en esta fecha','texto' => 'El detalle por efectivo, Yape, Plin y tarjeta se mostrará cuando registres cobros.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('vacio'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icono' => 'card','titulo' => 'Sin cobros en esta fecha','texto' => 'El detalle por efectivo, Yape, Plin y tarjeta se mostrará cuando registres cobros.']); ?>
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
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    gh.ready(() => {
        Alpine.data('cajaPage', (movimientos) => ({
            movimientos, tab: 'todos',
            get filtrados() { return this.tab === 'todos' ? this.movimientos : this.movimientos.filter(m => m.tipo === this.tab); },
            contar(t) { return this.movimientos.filter(m => m.tipo === t).length; },
            exportar() {
                if (!this.movimientos.length) { Alpine.store('toasts').push('No hay movimientos para exportar', 'info'); return; }
                gh.csv('caja-<?php echo e($fecha); ?>.csv', [['Hora', 'Concepto', 'Detalle', 'Método', 'Tipo', 'Monto'],
                    ...this.movimientos.map(m => [m.hora, m.concepto, m.detalle, m.metodo_texto, m.tipo, m.monto])]);
                Alpine.store('toasts').push('Se descargó caja-<?php echo e($fecha); ?>.csv', 'info');
            },
        }));
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/caja.blade.php ENDPATH**/ ?>