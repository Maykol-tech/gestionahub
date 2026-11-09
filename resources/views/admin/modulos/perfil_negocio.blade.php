@extends('layouts.app')

@section('titulo', 'Perfil del negocio')
@section('subtitulo', 'Datos que ven tus clientes en la tienda y en los comprobantes')

@push('styles')
    @include('admin.modulos._base')
    <style>
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

    <div class="panel fade-in" style="padding:18px;">
        <form method="POST" action="{{ route('admin.perfil.guardar') }}" class="grid-2">
            @csrf
            <div style="grid-column:1 / -1;">
                <label class="lbl" for="nombre_negocio">Nombre del negocio</label>
                <input class="inp" id="nombre_negocio" name="nombre_negocio" maxlength="120" value="{{ old('nombre_negocio', $perfil->nombre_negocio ?? '') }}" required>
            </div>
            <div>
                <label class="lbl" for="telefono">Teléfono</label>
                <input class="inp" id="telefono" name="telefono" maxlength="30" value="{{ old('telefono', $perfil->telefono ?? '') }}">
            </div>
            <div>
                <label class="lbl" for="ruc">RUC (11 dígitos)</label>
                <input class="inp" id="ruc" name="ruc" maxlength="11" inputmode="numeric" value="{{ old('ruc', $perfil->ruc ?? '') }}">
            </div>
            <div style="grid-column:1 / -1;">
                <label class="lbl" for="direccion">Dirección</label>
                <input class="inp" id="direccion" name="direccion" maxlength="255" value="{{ old('direccion', $perfil->direccion ?? '') }}">
            </div>
            <div>
                <label class="lbl" for="sitio_web">Sitio web</label>
                <input class="inp" id="sitio_web" name="sitio_web" type="url" maxlength="255" value="{{ old('sitio_web', $perfil->sitio_web ?? '') }}">
            </div>
            <div style="grid-column:1 / -1;">
                <label class="lbl" for="descripcion">Descripción</label>
                <textarea class="inp" id="descripcion" name="descripcion" rows="3" maxlength="1000">{{ old('descripcion', $perfil->descripcion ?? '') }}</textarea>
            </div>
            <div style="grid-column:1 / -1;">
                <button class="btn btn-p" type="submit">Guardar perfil</button>
            </div>
        </form>
    </div>
</div>
@endsection
