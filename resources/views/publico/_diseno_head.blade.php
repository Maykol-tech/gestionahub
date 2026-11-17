{{-- GEST-84: fuente, favicon y variables de color del negocio. Recibe $d (App\Support\DisenoNegocio::de). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="{{ $d['fuente_url'] }}">
@if ($d['favicon_url'])
    <link rel="icon" href="{{ $d['favicon_url'] }}">
@elseif ($d['logo_url'])
    <link rel="icon" href="{{ $d['logo_url'] }}">
@endif
<meta name="theme-color" content="{{ $d['color_primario'] }}">
<style>
    :root { --brand: {{ $d['color_primario'] }}; --brand2: {{ $d['color_secundario'] }}; --font: {!! $d['fuente_css'] !!}; }
    body { font-family: var(--font) !important; }
</style>
