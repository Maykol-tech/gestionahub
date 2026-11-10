
<link rel="manifest" href="<?php echo e(asset('manifest.webmanifest')); ?>">
<link rel="icon" href="<?php echo e(asset('icons/icon.svg')); ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?php echo e(asset('icons/icon.svg')); ?>">
<meta name="theme-color" content="#2B6EF6">
<meta name="apple-mobile-web-app-capable" content="yes">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo e(asset('sw.js')); ?>').catch(() => {});
        });
    }
</script>
<?php /**PATH /var/www/html/resources/views/admin/partials/pwa_head.blade.php ENDPATH**/ ?>