@extends('layouts.app')

@section('titulo', 'Personalización')
@section('subtitulo', 'Elige tu rubro para ver una plantilla sugerida, y el color de tu marca')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .chips { display:flex; gap:6px; flex-wrap:wrap; margin-top:6px; }
        .chip { font-size:12px; padding:4px 10px; border-radius:99px; background:var(--bg); border:1px solid var(--border); }
        .muestra { width:44px; height:44px; border-radius:12px; border:1px solid var(--border); }
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

    <form method="POST" action="{{ route('admin.personalizacion.guardar') }}" class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        @csrf
        <div class="grid-2">
            <div>
                <label class="lbl" for="rubro">Rubro de tu negocio</label>
                <select class="inp" id="rubro" name="rubro" required>
                    <option value="">Elige uno</option>
                    @foreach ($plantillas as $k => $p)
                        <option value="{{ $k }}" @selected($rubro === $k)>{{ $p['nombre'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lbl" for="color_primario">Color de tu marca</label>
                <div style="display:flex;gap:10px;align-items:center;">
                    <input id="color_primario" name="color_primario" type="color" value="{{ $color }}" style="width:64px;height:40px;padding:2px;">
                    <span class="muestra" style="background:{{ $color }};"></span>
                </div>
            </div>
        </div>
        <button class="btn btn-p" type="submit" style="margin-top:14px;">Guardar</button>
    </form>

    @if ($plantilla)
        <div class="panel fade-in" style="padding:18px;">
            <div style="font-weight:700;">Plantilla sugerida: {{ $plantilla['nombre'] }}</div>
            <div style="font-size:13px;color:var(--fg3);margin-top:8px;">Categorías para empezar</div>
            <div class="chips">@foreach ($plantilla['categorias'] as $c)<span class="chip">{{ $c }}</span>@endforeach</div>
            <div style="font-size:13px;color:var(--fg3);margin-top:12px;">Servicios o formas de venta sugeridas</div>
            <div class="chips">
                @forelse ($plantilla['servicios'] as $s)<span class="chip">{{ $s }}</span>@empty<span class="chip">Sin servicios sugeridos</span>@endforelse
            </div>
        </div>
    @endif
</div>
@endsection
