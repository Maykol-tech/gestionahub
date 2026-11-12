@extends('layouts.app')

@section('titulo', 'Mesas y QR')
@section('subtitulo', 'Cada mesa tiene su QR. El cliente escanea y pide desde el celular')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .mesas-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap:12px; }
        .mesa-card { padding:16px; text-align:center; }
        .mesa-card img { border-radius:8px; background:#fff; padding:6px; }
        .estado-mesa { display:inline-block; font-size:11px; font-weight:700; padding:3px 10px; border-radius:99px; }
        .libre { background:#DCFCE7; color:#15803D; }
        .ocupada { background:#FEF3C7; color:#92400E; }
        .por_cobrar { background:#E0E7FF; color:#3730A3; }
    </style>
@endpush

@section('contenido')
<div>
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if (session('error') || $errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            {{ session('error') }} @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <form method="POST" action="{{ route('admin.mesas.guardar') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
            @csrf
            <div>
                <label class="lbl" for="nombre">Nombre de la mesa</label>
                <input class="inp" id="nombre" name="nombre" maxlength="40" placeholder="Mesa 1, Terraza 3…" required>
            </div>
            <div>
                <label class="lbl" for="capacidad">Capacidad</label>
                <input class="inp" id="capacidad" name="capacidad" type="number" min="1" max="50" value="4" required style="width:110px;">
            </div>
            <button class="btn btn-p" type="submit">Agregar mesa</button>
        </form>
    </div>

    <div class="mesas-grid">
        @forelse ($mesas as $mesa)
            <div class="panel mesa-card fade-in">
                <div style="font-weight:700;font-size:16px;">{{ $mesa->nombre }}</div>
                <div style="font-size:12px;color:var(--fg3);margin-bottom:8px;">{{ $mesa->capacidad }} personas · {{ $mesa->abiertas }} comandas abiertas</div>
                <span class="estado-mesa {{ $mesa->estado }}">{{ \App\Models\Mesa::ESTADOS[$mesa->estado] ?? $mesa->estado }}</span>

                <div style="margin:12px 0;">
                    <img width="150" height="150" alt="QR de {{ $mesa->nombre }}" src="{{ route('admin.mesas.qr', $mesa->id) }}">
                </div>
                <div style="font-size:11px;word-break:break-all;color:var(--fg3);margin-bottom:8px;">{{ $mesa->urlPublica() }}</div>
                <a class="btn btn-g btn-sm" href="{{ route('admin.mesas.qr', ['mesa' => $mesa->id, 'descargar' => 1]) }}">Descargar QR</a>

                @if ($mesa->estado !== 'libre')
                    <form method="POST" action="{{ route('admin.mesas.cobrar', $mesa->id) }}">
                        @csrf
                        <button class="btn btn-g" type="submit">Cobrar y liberar</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="panel empty" style="grid-column:1 / -1;">
                <div style="font-weight:600;color:var(--fg2);">Aún no tienes mesas</div>
                <div style="font-size:13px;color:var(--fg3);">Agrega la primera para generar su QR.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection
