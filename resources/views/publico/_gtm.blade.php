{{-- GEST-99: Google Tag Manager + dataLayer con los UTM guardados (cookie de 30 días) y los eventos del flujo --}}
@php
    $gtmId = \App\Models\LandingContenido::gtm();
    $utmCookie = json_decode((string) request()->cookie(\App\Http\Middleware\CapturarUtm::COOKIE), true) ?: [];
    $eventoFlash = session('evento');
    $eventoDatos = (array) session('evento_datos', []);
@endphp
<script>
    window.dataLayer = window.dataLayer || [];
    @if ($utmCookie)
    window.dataLayer.push(Object.assign({ event: 'gh_utm' }, @json(array_intersect_key($utmCookie, array_flip(\App\Http\Middleware\CapturarUtm::CLAVES)))));
    @endif
    @if ($eventoFlash)
    window.dataLayer.push(Object.assign({ event: @json($eventoFlash) }, @json((object) $eventoDatos)));
    @endif
</script>
@if ($gtmId)
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json($gtmId));</script>
@endif
