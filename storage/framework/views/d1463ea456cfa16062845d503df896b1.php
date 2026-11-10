<?php $__env->startSection('titulo', 'Variantes y venta por kilo'); ?>
<?php $__env->startSection('subtitulo', 'Tamaños, sabores y productos que se venden por peso'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .prod { padding:14px; margin-bottom:10px; }
        .grid-3 { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; align-items:end; }
        .chip { display:inline-flex; gap:6px; align-items:center; font-size:12px; padding:3px 10px; border-radius:99px; background:var(--bg); border:1px solid var(--border); margin:2px; }
        @media (max-width: 640px) { .grid-3 { grid-template-columns: 1fr; } }
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

    <?php $__currentLoopData = $productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="panel prod fade-in">
            <div style="font-weight:700;"><?php echo e($p['nombre']); ?> <span style="font-weight:400;color:var(--fg3);">Â· S/ <?php echo e(number_format($p['precio'], 2)); ?></span></div>

            <div style="margin-top:6px;">
                <?php $__empty_1 = true; $__currentLoopData = $p['variantes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <span class="chip"><?php echo e($v->nombre); ?> Â· S/ <?php echo e(number_format($v->precio, 2)); ?> Â· stock <?php echo e($v->stock); ?>

                        <form method="POST" action="<?php echo e(route('admin.variantes.eliminar', $v->id)); ?>" style="display:inline;"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button type="submit" style="background:none;border:0;color:#B91C1C;cursor:pointer;" aria-label="Eliminar variante">—</button></form>
                    </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <span style="font-size:12px;color:var(--fg3);">Sin variantes</span>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?php echo e(route('admin.variantes.guardar', $p['id'])); ?>" class="grid-3" style="margin-top:10px;">
                <?php echo csrf_field(); ?>
                <div><label class="lbl">Variante (ej. Grande)</label><input class="inp" name="nombre" maxlength="60" required></div>
                <div><label class="lbl">Precio (S/)</label><input class="inp" name="precio" type="number" step="0.01" min="0" required></div>
                <div><label class="lbl">Stock</label><input class="inp" name="stock" type="number" min="0" value="0"></div>
                <div><button class="btn btn-g" type="submit">Agregar variante</button></div>
            </form>

            <form method="POST" action="<?php echo e(route('admin.variantes.kilo', $p['id'])); ?>" class="grid-3" style="margin-top:12px;border-top:1px dashed var(--border);padding-top:10px;">
                <?php echo csrf_field(); ?>
                <div><label class="lbl">Precio por kilo (S/)</label><input class="inp" name="precio_kg" type="number" step="0.01" min="0.01" value="<?php echo e($p['kilo']->precio_kg ?? ''); ?>" required></div>
                <div style="font-size:12px;color:var(--fg3);"><?php echo e($p['kilo'] ? ($p['kilo']->activo ? 'Se vende por kilo' : 'Venta por kilo pausada') : 'No se vende por kilo'); ?></div>
                <div><button class="btn btn-g" type="submit">Guardar precio por kilo</button></div>
            </form>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <div class="panel fade-in" style="padding:18px;margin-top:12px;">
        <div style="font-weight:700;margin-bottom:8px;">Ajustar peso real al despachar</div>
        <form method="POST" action="<?php echo e(route('admin.variantes.peso', $pedidosKilo->first()->id ?? 0)); ?>" class="grid-3">
            <?php echo csrf_field(); ?>
            <div>
                <label class="lbl">Pedido</label>
                <select class="inp" name="pedido_id_ref" onchange="this.form.action = this.form.action.replace(/\/\d+\/peso$/, '/' + this.value + '/peso')">
                    <?php $__currentLoopData = $pedidosKilo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ped): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($ped->id); ?>"><?php echo e($ped->numero); ?> Â· S/ <?php echo e(number_format($ped->total, 2)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div><label class="lbl">ID del producto por kilo</label><input class="inp" name="producto_id" type="number" min="1" required></div>
            <div><label class="lbl">Peso estimado (kg)</label><input class="inp" name="peso_estimado_kg" type="number" step="0.001" min="0.001" required></div>
            <div><label class="lbl">Peso real (kg)</label><input class="inp" name="peso_real_kg" type="number" step="0.001" min="0.001" required></div>
            <div><button class="btn btn-p" type="submit">Ajustar total</button></div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/variantes.blade.php ENDPATH**/ ?>