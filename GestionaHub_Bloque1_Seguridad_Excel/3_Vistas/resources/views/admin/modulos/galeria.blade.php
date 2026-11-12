@extends('layouts.app')

@section('titulo', 'Galería de trabajos')
@section('subtitulo', 'Muestra tus trabajos en tu página pública')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .galeria { display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:12px; }
        .foto { padding:10px; }
        .foto img { width:100%; height:150px; object-fit:cover; border-radius:10px; background:var(--bg); }
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('contenido')
<div>
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <form method="POST" action="{{ route('admin.galeria.guardar') }}" class="grid-2">
            @csrf
            <div><label class="lbl" for="titulo">Título del trabajo</label><input class="inp" id="titulo" name="titulo" maxlength="120" required></div>
            <div><label class="lbl" for="orden">Orden (menor primero)</label><input class="inp" id="orden" name="orden" type="number" min="0" value="0"></div>
            <div style="grid-column:1 / -1;"><label class="lbl" for="imagen_url">Enlace de la foto (https://…)</label><input class="inp" id="imagen_url" name="imagen_url" type="url" maxlength="500" required></div>
            <div style="grid-column:1 / -1;"><label class="lbl" for="descripcion">Descripción</label><input class="inp" id="descripcion" name="descripcion" maxlength="255"></div>
            <div style="grid-column:1 / -1;"><button class="btn btn-p" type="submit">Agregar trabajo</button></div>
        </form>
    </div>

    <div class="galeria">
        @forelse ($items as $item)
            <div class="panel foto fade-in" style="{{ $item->activo ? '' : 'opacity:.55;' }}">
                <img src="{{ $item->imagen_url }}" alt="{{ $item->titulo }}" loading="lazy">
                <div style="font-weight:600;margin-top:8px;">{{ $item->titulo }}</div>
                <div style="font-size:12px;color:var(--fg3);">{{ $item->descripcion }}</div>
                <div style="display:flex;gap:6px;margin-top:8px;">
                    <form method="POST" action="{{ route('admin.galeria.alternar', $item->id) }}">@csrf <button class="btn btn-g" type="submit">{{ $item->activo ? 'Ocultar' : 'Mostrar' }}</button></form>
                    <form method="POST" action="{{ route('admin.galeria.eliminar', $item->id) }}" onsubmit="return confirm('¿Eliminar este trabajo?')">@csrf @method('DELETE') <button class="btn btn-g" type="submit">Eliminar</button></form>
                </div>
            </div>
        @empty
            <div class="panel empty" style="grid-column:1 / -1;"><div style="font-weight:600;color:var(--fg2);">Aún no hay trabajos en la galería</div></div>
        @endforelse
    </div>
</div>
@endsection
