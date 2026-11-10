<?php $__env->startSection('titulo', 'Galería de trabajos'); ?>
<?php $__env->startSection('subtitulo', 'Muestra tus trabajos en tu página pública'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .galeria { display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:12px; }
        .foto { padding:10px; }
        .foto img { width:100%; height:150px; object-fit:cover; border-radius:10px; background:var(--bg); }
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
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

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <form method="POST" action="<?php echo e(route('admin.galeria.guardar')); ?>" class="grid-2">
            <?php echo csrf_field(); ?>
            <div><label class="lbl" for="titulo">Título del trabajo</label><input class="inp" id="titulo" name="titulo" maxlength="120" required></div>
            <div><label class="lbl" for="orden">Orden (menor primero)</label><input class="inp" id="orden" name="orden" type="number" min="0" value="0"></div>
            <div style="grid-column:1 / -1;"><label class="lbl" for="imagen_url">Enlace de la foto (https://¦)</label><input class="inp" id="imagen_url" name="imagen_url" type="url" maxlength="500" required></div>
            <div style="grid-column:1 / -1;"><label class="lbl" for="descripcion">Descripción</label><input class="inp" id="descripcion" name="descripcion" maxlength="255"></div>
            <div style="grid-column:1 / -1;"><button class="btn btn-p" type="submit">Agregar trabajo</button></div>
        </form>
    </div>

    <div class="galeria">
        <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="panel foto fade-in" style="<?php echo e($item->activo ? '' : 'opacity:.55;'); ?>">
                <img src="<?php echo e($item->imagen_url); ?>" alt="<?php echo e($item->titulo); ?>" loading="lazy">
                <div style="font-weight:600;margin-top:8px;"><?php echo e($item->titulo); ?></div>
                <div style="font-size:12px;color:var(--fg3);"><?php echo e($item->descripcion); ?></div>
                <div style="display:flex;gap:6px;margin-top:8px;">
                    <form method="POST" action="<?php echo e(route('admin.galeria.alternar', $item->id)); ?>"><?php echo csrf_field(); ?> <button class="btn btn-g" type="submit"><?php echo e($item->activo ? 'Ocultar' : 'Mostrar'); ?></button></form>
                    <form method="POST" action="<?php echo e(route('admin.galeria.eliminar', $item->id)); ?>" onsubmit="return confirm('Â¿Eliminar este trabajo?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?> <button class="btn btn-g" type="submit">Eliminar</button></form>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="panel empty" style="grid-column:1 / -1;"><div style="font-weight:600;color:var(--fg2);">Aún no hay trabajos en la galería</div></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/galeria.blade.php ENDPATH**/ ?>