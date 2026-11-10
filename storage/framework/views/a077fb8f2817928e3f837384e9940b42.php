<?php $__env->startSection('titulo', 'Reseñas de clientes'); ?>
<?php $__env->startSection('subtitulo', 'Revisa las reseñas antes de publicarlas en tu página'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .kpis { display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap:12px; margin-bottom:12px; }
        .kpi { padding:14px; }
        .kpi .n { font-size:22px; font-weight:800; }
        .kpi .l { font-size:12px; color:var(--fg3); }
        .filtros { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; }
        .filtros a { font-size:12px; font-weight:700; padding:5px 12px; border-radius:99px; border:1px solid var(--border); color:var(--fg2); text-decoration:none; }
        .filtros a.activo { background:var(--brand); color:#fff; border-color:var(--brand); }
        .estrellas { color:#F59E0B; }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('contenido'); ?>
<div>
    <?php if(session('ok')): ?>
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;"><?php echo e(session('ok')); ?></div>
    <?php endif; ?>

    <div class="kpis">
        <div class="panel kpi fade-in"><div class="n"><?php echo e($promedio ?? '”'); ?></div><div class="l">Promedio publicado</div></div>
        <div class="panel kpi fade-in"><div class="n"><?php echo e($total); ?></div><div class="l">Reseñas publicadas</div></div>
        <div class="panel kpi fade-in"><div class="n"><?php echo e($pendientes); ?></div><div class="l">Pendientes de revisar</div></div>
    </div>

    <div class="filtros">
        <a href="<?php echo e(route('admin.resenas')); ?>" class="<?php echo e($filtro ? '' : 'activo'); ?>">Todas</a>
        <?php $__currentLoopData = \App\Models\Resena::ESTADOS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('admin.resenas', ['estado' => $k])); ?>" class="<?php echo e($filtro === $k ? 'activo' : ''); ?>"><?php echo e($label); ?></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="tbl-wrap" <?php if($resenas->isEmpty()): ?> style="display:none" <?php endif; ?>>
            <table class="tbl">
                <thead>
                    <tr><th>Cliente</th><th>Calificación</th><th>Comentario</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $resenas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($r->cliente_nombre); ?><div style="font-size:11px;color:var(--fg3);"><?php echo e($r->created_at?->format('d/m/Y')); ?></div></td>
                            <td><span class="estrellas"><?php echo e(str_repeat('â˜…', $r->calificacion)); ?></span></td>
                            <td style="max-width:320px;"><?php echo e($r->comentario ?: '”'); ?></td>
                            <td>
                                <form method="POST" action="<?php echo e(route('admin.resenas.estado', $r->id)); ?>" style="display:flex;gap:6px;align-items:center;">
                                    <?php echo csrf_field(); ?>
                                    <select class="inp" name="estado" onchange="this.form.submit()" style="width:auto;">
                                        <?php $__currentLoopData = \App\Models\Resena::ESTADOS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($k); ?>" <?php if($r->estado === $k): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php if($resenas->isEmpty()): ?>
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay reseñas</div><div style="font-size:13px;color:var(--fg3);">Comparte tu enlace público de reseñas con tus clientes: /resenas/tu-negocio.</div></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/resenas.blade.php ENDPATH**/ ?>