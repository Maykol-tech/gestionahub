{{-- Colores elegidos en Personalizar (se guardan por negocio) --}}
@php($tema = \App\Support\AjustesNegocio::leer((int) (auth()->user()?->tenant_id ?? 1)))
<style id="gh-tema">
:root{--nav-bg:{{ $tema['color_menu'] }};--cta:{{ $tema['color_accion'] }};}
.btn-p:hover{background:var(--cta);filter:brightness(.93);}
</style>
