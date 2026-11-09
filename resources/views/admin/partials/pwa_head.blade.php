{{-- Incluir dentro de <head> del layout del panel: manifest, icono y registro del service worker --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" href="{{ asset('icons/icon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('icons/icon.svg') }}">
<meta name="theme-color" content="#2B6EF6">
<meta name="apple-mobile-web-app-capable" content="yes">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
        });
    }
</script>
