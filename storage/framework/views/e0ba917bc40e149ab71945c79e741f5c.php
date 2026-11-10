<?php $__env->startSection('titulo', 'Servicios'); ?>
<?php $__env->startSection('subtitulo', 'Gestiona tu catálogo y agenda de citas'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('admin.modulos._base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .kpi-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .kpi-title { font-size: 11px; font-weight: 700; color: var(--fg3); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .kpi-value { font-size: 28px; font-weight: 700; color: var(--fg1); line-height: 1; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(3px); z-index: 9999; align-items: center; justify-content: center; }
        .modal-content { background: #fff; width: 100%; max-width: 550px; border-radius: 16px; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--fg2); margin-bottom: 6px; }
        .form-input { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: #fff; }
        .form-input:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 0 3px rgba(43,110,246,0.1); }
        
        .empty-icon { width: 64px; height: 64px; color: var(--fg3); margin-bottom: 16px; opacity: 0.5; }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('contenido'); ?>
<div class="vit-wrap">
    
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-title">Citas Hoy</div>
            <div class="kpi-value">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Por Confirmar</div>
            <div class="kpi-value">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Próximas Citas</div>
            <div class="kpi-value">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Ingresos Proyectados</div>
            <div class="kpi-value">S/ 0.00</div>
        </div>
    </div>

    <div class="panel" style="padding: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; gap: 12px; flex: 1; min-width: 300px;">
            <input type="text" class="form-input" placeholder="Buscar cliente o servicio..." style="flex: 1;">
            <select class="form-input" style="width: 180px;">
                <option>Todos los estados</option>
                <option>Confirmada</option>
                <option>Pendiente</option>
            </select>
        </div>
        <button type="button" class="btn btn-p" onclick="document.getElementById('modalNuevaCita').style.display='flex'" style="padding: 10px 20px; font-weight: 600;">
            + Nueva Cita
        </button>
    </div>

    <div class="panel" style="padding: 60px 20px; text-align: center; margin-bottom: 24px; background: #f8fafc; border: 1px dashed var(--border); display: flex; flex-direction: column; align-items: center; justify-content: center;">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
            <path d="M8 14h.01"></path>
            <path d="M12 14h.01"></path>
            <path d="M16 14h.01"></path>
            <path d="M8 18h.01"></path>
            <path d="M12 18h.01"></path>
            <path d="M16 18h.01"></path>
        </svg>
        <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: var(--fg1);">No hay citas agendadas</h3>
        <p style="color: var(--fg3); font-size: 14px; max-width: 400px; margin: 0 auto;">Los turnos agendados aparecerán aquí. Haz clic en "Nueva Cita" para registrar tu primer cliente.</p>
    </div>

    <div class="panel" style="padding: 24px;">
        <div class="panel-hd" style="margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div class="panel-ttl" style="font-size: 16px; font-weight: 600;">Catálogo de Servicios</div>
            <button class="btn btn-g" style="font-size: 12px; padding: 6px 12px;">+ Añadir Servicio</button>
        </div>
        <div style="text-align: center; padding: 30px 0;">
            <p style="color: var(--fg3); font-size: 14px;">Aún no has configurado los servicios que ofreces.</p>
        </div>
    </div>

</div>

<div id="modalNuevaCita" class="modal-overlay">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h3 style="font-size: 20px; font-weight: 700; color: var(--fg1); margin: 0;">Agendar Cita</h3>
                <p style="font-size: 13px; color: var(--fg3); margin: 4px 0 0 0;">Registra los detalles del servicio a realizar.</p>
            </div>
            <button type="button" onclick="document.getElementById('modalNuevaCita').style.display='none'" style="background: var(--surface2); border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--fg2);">
                &times;
            </button>
        </div>
        
        <form action="#" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label class="form-label">Nombre del Cliente</label>
                <input type="text" name="cliente_nombre" class="form-input" required placeholder="Ej. Carlos Mendoza">
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Teléfono de Contacto</label>
                    <input type="tel" name="cliente_telefono" class="form-input" placeholder="Ej. 999 888 777">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Servicio a realizar</label>
                    <select name="servicio_id" class="form-input" required>
                        <option value="">Seleccione un servicio...</option>
                        <option value="1">Servicio de Ejemplo</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-input" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Hora</label>
                    <input type="time" name="hora" class="form-input" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Atendido por</label>
                    <select name="profesional_id" class="form-input">
                        <option value="">Cualquier profesional</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notas del Pedido (Opcional)</label>
                <textarea name="notas" class="form-input" rows="2" placeholder="Instrucciones especiales..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <button type="button" class="btn btn-g" onclick="document.getElementById('modalNuevaCita').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-p" style="font-weight: 600;">Confirmar Cita</button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/modulos/servicios.blade.php ENDPATH**/ ?>