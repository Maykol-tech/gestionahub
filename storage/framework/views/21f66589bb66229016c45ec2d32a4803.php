<?php $__env->startSection('titulo', 'Lista de espera y boletín'); ?>
<?php $__env->startSection('subtitulo', 'Avisa cuando se libere un horario y reactiva a tus clientes inactivos'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
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
        <div style="font-weight:700;margin-bottom:10px;">Boletín automático para clientes inactivos</div>
        <form method="POST" action="<?php echo e(route('admin.espera.boletin')); ?>">
            <?php echo csrf_field(); ?>
            <label style="display:flex;gap:6px;align-items:center;font-size:13px;margin-bottom:8px;">
                <input type="checkbox" name="boletin_activo" value="1" <?php if($boletinActivo): echo 'checked'; endif; ?>> Enviar cada mes a clientes sin compras en 60 días
            </label>
            <label class="lbl" for="boletin_texto">Mensaje (se envía solo dentro de la ventana de 24 horas; para clientes lejanos usa una plantilla en Campañas)</label>
            <textarea class="inp" id="boletin_texto" name="boletin_texto" rows="3" maxlength="1000"><?php echo e(old('boletin_texto', $boletinTexto)); ?></textarea>
            <button class="btn btn-p" type="submit" style="margin-top:10px;">Guardar boletín</button>
        </form>
    </div>

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <div style="font-weight:700;margin-bottom:10px;">Agregar a la lista de espera</div>
        <form method="POST" action="<?php echo e(route('admin.espera.guardar')); ?>" class="grid-2">
            <?php echo csrf_field(); ?>
            <div><label class="lbl" for="cliente_nombre">Nombre</label><input class="inp" id="cliente_nombre" name="cliente_nombre" maxlength="120" required></div>
            <div><label class="lbl" for="cliente_telefono">WhatsApp</label><input class="inp" id="cliente_telefono" name="cliente_telefono" maxlength="30" required></div>
            <div><label class="lbl" for="fecha_deseada">Día que quiere</label><input class="inp" id="fecha_deseada" name="fecha_deseada" type="date" min="<?php echo e(now()->toDateString()); ?>" required></div>
            <div>
                <label class="lbl" for="servicio_id">Servicio</label>
                <select class="inp" id="servicio_id" name="servicio_id">
                    <option value="">Cualquiera</option>
                    <?php $__currentLoopData = $servicios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($s->id); ?>"><?php echo e($s->nombre); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div style="grid-column:1 / -1;"><button class="btn btn-p" type="submit">Agregar</button></div>
        </form>
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="tbl-wrap" <?php if($espera->isEmpty()): ?> style="display:none" <?php endif; ?>>
            <table class="tbl">
                <thead>
                    <tr><th>Cliente</th><th>Día</th><th>Servicio</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $espera; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($e->cliente_nombre); ?><div style="font-size:11px;color:var(--fg3);"><?php echo e($e->cliente_telefono); ?></div></td>
                            <td><?php echo e($e->fecha_deseada->format('d/m/Y')); ?></td>
                            <td><?php echo e($e->servicio->nombre ?? 'Cualquiera'); ?></td>
                            <td>
                                <form method="POST" action="<?php echo e(route('admin.espera.estado', $e->id)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <select class="inp" name="estado" onchange="this.form.submit()" style="width:auto;">
                                        <?php $__currentLoopData = $estados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($k); ?>" <?php if($e->estado === $k): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php if($espera->isEmpty()): ?>
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">La lista de espera está vacía</div></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/lista_espera.blade.php ENDPATH**/ ?>