@extends('layouts.app')

@section('titulo', 'Cocina')
@section('subtitulo', 'Comandas en curso. Se actualiza sola cada 5 segundos')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .cocina-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:12px; }
        .comanda { padding:14px; }
        .comanda-hd { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
        .linea { display:flex; justify-content:space-between; font-size:14px; padding:3px 0; }
        .chip-estado { font-size:11px; font-weight:700; padding:3px 10px; border-radius:99px; }
        .pendiente { background:#FEF3C7; color:#92400E; }
        .en_preparacion { background:#DBEAFE; color:#1E40AF; }
        .lista { background:#DCFCE7; color:#15803D; }
    </style>
@endpush

@section('contenido')
<div>
<div>
    <div id="cocina-grid" data-url="{{ route('admin.cocina.fragmento') }}">
        @include('admin.modulos._cocina_grid')
    </div>
</div>

<script>
    // Refresca solo las comandas cada 5 s (sin recargar la pagina). Se detiene al salir de la vista.
    (function () {
        const caja = document.getElementById('cocina-grid');
        if (window.__cocinaTimer) clearInterval(window.__cocinaTimer);
        if (!caja) return;
        window.__cocinaTimer = setInterval(async () => {
            if (!document.body.contains(caja)) { clearInterval(window.__cocinaTimer); return; }
            if (document.hidden) return;
            try {
                const r = await fetch(caja.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                if (r.ok) caja.innerHTML = await r.text();
            } catch (e) { /* sin conexion: se reintenta en el siguiente ciclo */ }
        }, 5000);
    })();
</script>
@endsection
