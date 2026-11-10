
<?php ($tema = \App\Support\AjustesNegocio::leer((int) (auth()->user()?->tenant_id ?? 1))); ?>
<style id="gh-tema">
:root{--nav-bg:<?php echo e($tema['color_menu']); ?>;--cta:<?php echo e($tema['color_accion']); ?>;}
.btn-p:hover{background:var(--cta);filter:brightness(.93);}
</style>
<?php /**PATH /var/www/html/resources/views/layouts/tema.blade.php ENDPATH**/ ?>