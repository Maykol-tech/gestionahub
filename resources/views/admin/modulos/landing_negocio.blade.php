@extends('layouts.app')

@section('titulo', 'Landing de mi negocio')
@section('subtitulo', 'Tu página pública con formulario para captar clientes')

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

    <form method="POST" action="{{ route('admin.landing_negocio.guardar') }}" class="panel fade-in" style="padding:18px;">
        @csrf
        <div class="grid-2">
            <div><label class="lbl" for="titulo">Título</label><input class="inp" id="titulo" name="titulo" maxlength="120" value="{{ old('titulo', $c['titulo']) }}" required></div>
            <div><label class="lbl" for="boton">Texto del botón</label><input class="inp" id="boton" name="boton" maxlength="40" value="{{ old('boton', $c['boton']) }}" required></div>
            <div style="grid-column:1 / -1;"><label class="lbl" for="subtitulo">Subtítulo</label><textarea class="inp" id="subtitulo" name="subtitulo" rows="2" maxlength="250" required>{{ old('subtitulo', $c['subtitulo']) }}</textarea></div>
        </div>

        <div style="font-weight:700;margin:16px 0 8px;">Beneficios</div>
        <div class="grid-2">
            @foreach ($c['beneficios'] as $i => $b)
                <div>
                    <label class="lbl">Título {{ $i + 1 }}</label>
                    <input class="inp" name="beneficio[{{ $i }}][titulo]" maxlength="80" value="{{ $b['titulo'] }}" required>
                    <label class="lbl">Texto {{ $i + 1 }}</label>
                    <textarea class="inp" name="beneficio[{{ $i }}][texto]" rows="2" maxlength="200" required>{{ $b['texto'] }}</textarea>
                </div>
            @endforeach
        </div>

        <button class="btn btn-p" type="submit" style="margin-top:14px;">Guardar landing</button>
    </form>
</div>
@endsection
