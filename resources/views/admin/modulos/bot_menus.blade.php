@extends('layouts.app')

@section('titulo', 'Menús del bot')
@section('subtitulo', 'Opciones numeradas que el bot envía por WhatsApp')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .menu-card { padding:14px; margin-bottom:10px; }
        .opc { font-size:13px; color:var(--fg2); margin-top:4px; white-space:pre-wrap; }
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

    <form method="POST" action="{{ route('admin.bot.menus.guardar') }}" class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        @csrf
        <label class="lbl" for="disparador">Palabra que abre el menú</label>
        <input class="inp" id="disparador" name="disparador" maxlength="80" placeholder="menu" required>

        <label class="lbl" for="titulo">Mensaje de bienvenida</label>
        <textarea class="inp" id="titulo" name="titulo" rows="2" maxlength="500" required>Hola, elige una opción:</textarea>

        <label class="lbl" for="opciones">Opciones, una por línea: numero|texto|respuesta</label>
        <textarea class="inp" id="opciones" name="opciones" rows="5" required placeholder="1|Ver precios|Nuestros precios empiezan en S/ 10.&#10;2|Horarios|Atendemos de lunes a sábado.&#10;3|Hablar con una persona|En breve te escribimos."></textarea>

        <button class="btn btn-p" type="submit" style="margin-top:12px;">Guardar menú</button>
    </form>

    @forelse ($menus as $m)
        <div class="panel menu-card fade-in" style="{{ $m->activo ? '' : 'opacity:.55;' }}">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                <div><strong>{{ $m->disparador }}</strong> <span style="font-size:12px;color:var(--fg3);">{{ $m->activo ? 'Activo' : 'Pausado' }}</span></div>
                <form method="POST" action="{{ route('admin.bot.menus.alternar', $m->id) }}">@csrf <button class="btn btn-g" type="submit">{{ $m->activo ? 'Pausar' : 'Activar' }}</button></form>
            </div>
            <div class="opc">{{ $m->titulo }}
@foreach ($m->opciones as $o){{ $o['numero'] }}. {{ $o['texto'] }}
@endforeach</div>
        </div>
    @empty
        <div class="panel empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay menús</div></div>
    @endforelse
</div>
@endsection
