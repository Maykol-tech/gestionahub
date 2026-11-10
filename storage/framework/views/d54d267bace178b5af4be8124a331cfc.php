<?php $__env->startSection('titulo', 'Menús del bot'); ?>
<?php $__env->startSection('subtitulo', 'Opciones numeradas que el bot envía por WhatsApp'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .menu-card { padding:14px; margin-bottom:10px; }
        .opc { font-size:13px; color:var(--fg2); margin-top:4px; white-space:pre-wrap; }
    </style>
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

    <form method="POST" action="<?php echo e(route('admin.bot.menus.guardar')); ?>" class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <?php echo csrf_field(); ?>
        <label class="lbl" for="disparador">Palabra que abre el menú</label>
        <input class="inp" id="disparador" name="disparador" maxlength="80" placeholder="menu" required>

        <label class="lbl" for="titulo">Mensaje de bienvenida</label>
        <textarea class="inp" id="titulo" name="titulo" rows="2" maxlength="500" required>Hola, elige una opción:</textarea>

        <label class="lbl" for="opciones">Opciones, una por línea: numero|texto|respuesta</label>
        <textarea class="inp" id="opciones" name="opciones" rows="5" required placeholder="1|Ver precios|Nuestros precios empiezan en S/ 10.&#10;2|Horarios|Atendemos de lunes a sábado.&#10;3|Hablar con una persona|En breve te escribimos."></textarea>

        <button class="btn btn-p" type="submit" style="margin-top:12px;">Guardar menú</button>
    </form>

    <?php $__empty_1 = true; $__currentLoopData = $menus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="panel menu-card fade-in" style="<?php echo e($m->activo ? '' : 'opacity:.55;'); ?>">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                <div><strong><?php echo e($m->disparador); ?></strong> <span style="font-size:12px;color:var(--fg3);"><?php echo e($m->activo ? 'Activo' : 'Pausado'); ?></span></div>
                <form method="POST" action="<?php echo e(route('admin.bot.menus.alternar', $m->id)); ?>"><?php echo csrf_field(); ?> <button class="btn btn-g" type="submit"><?php echo e($m->activo ? 'Pausar' : 'Activar'); ?></button></form>
            </div>
            <div class="opc"><?php echo e($m->titulo); ?>

<?php $__currentLoopData = $m->opciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo e($o['numero']); ?>. <?php echo e($o['texto']); ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="panel empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay menús</div></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/bot_menus.blade.php ENDPATH**/ ?>