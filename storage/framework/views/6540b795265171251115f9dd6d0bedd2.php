<?php $__env->startSection('titulo', 'Ingresos manuales'); ?>
<?php $__env->startSection('subtitulo', 'Ingresos que no pasan por pedidos ni por el POS. Se suman en Finanzas.'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('contenido'); ?>
<div>
    <?php if(session('ok')): ?>
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;"><?php echo e(session('ok')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <div><?php echo e($e); ?></div> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <div class="panel fade-in" style="padding:14px;margin-bottom:12px;">
        <div style="font-size:12px;color:var(--fg3);">Ingresos manuales del mes</div>
        <div style="font-size:22px;font-weight:700;">S/ <?php echo e(number_format($totalMes, 2)); ?></div>
    </div>

    <form method="POST" action="<?php echo e(route('admin.ingresos.guardar')); ?>" class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <?php echo csrf_field(); ?>
        <label class="lbl" for="fecha">Fecha</label>
        <input class="inp" id="fecha" type="date" name="fecha" value="<?php echo e(now()->toDateString()); ?>" required>

        <label class="lbl" for="concepto">Concepto</label>
        <input class="inp" id="concepto" name="concepto" maxlength="120" placeholder="Ej. Alquiler de local" required>

        <label class="lbl" for="monto">Monto (S/)</label>
        <input class="inp" id="monto" type="number" step="0.01" min="0.01" name="monto" required>

        <label class="lbl" for="categoria">Categoría (opcional)</label>
        <input class="inp" id="categoria" name="categoria" maxlength="40" placeholder="Ej. Alquiler">

        <label class="lbl" for="notas">Notas (opcional)</label>
        <input class="inp" id="notas" name="notas" maxlength="255">

        <button class="btn btn-p" type="submit" style="margin-top:12px;">Registrar ingreso</button>
    </form>

    <div class="panel fade-in" style="padding:0;overflow:hidden;">
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr><th>Fecha</th><th>Concepto</th><th>Categoría</th><th style="text-align:right;">Monto</th><th></th></tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $ingresos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="row-in">
                        <td><?php echo e($i->categoria ?? '”'); ?></td>
                        <td style="text-align:right;">S/ <?php echo e(number_format((float) $i->monto, 2)); ?></td>
                        <td style="text-align:right;">
                            <form method="POST" action="<?php echo e(route('admin.ingresos.eliminar', $i->id)); ?>" onsubmit="return confirm('Â¿Eliminar este ingreso?')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button class="btn btn-g" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5"><div class="empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay ingresos manuales</div></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/ingresos_manuales.blade.php ENDPATH**/ ?>