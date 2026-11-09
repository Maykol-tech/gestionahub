@extends('layouts.app')

@section('titulo', 'Mi Vitrina')
@section('subtitulo', 'Tu página web pública')

@section('acciones')
    <a href="{{ route('admin.modulo', 'tienda') }}" class="btn btn-g"><x-icon name="eye" size="15" /> Ver tienda</a>
@endsection

@section('contenido')
<div class="vit-wrap">
    <div class="vit-hero fade-in">
        <div class="vit-ico"><x-icon name="globe" size="24" /></div>
        <div style="flex:1;min-width:0;">
            <span class="pronto"><x-icon name="clock" size="11" /> Constructor en preparación</span>
            <h1 class="vit-ttl">Configura tu tienda pública aquí</h1>
            <p class="vit-txt">Tu vitrina mostrará tu catálogo, ofertas, horarios y ubicación, y recibirá pedidos directos sin comisiones. Completa estos pasos para dejarla lista.</p>
        </div>
        <div class="vit-avance" style="--p:{{ $avance }};" title="Avance {{ $avance }}%">
            <div class="vit-pct">{{ $avance }}%</div>
        </div>
    </div>

    <div class="panel fade-in" style="animation-delay:.08s">
        <div class="panel-hd">
            <div>
                <div class="panel-ttl">Pasos para publicar</div>
                <div class="panel-sub">{{ collect($pasos)->where(2, true)->count() }} de {{ count($pasos) }} completados</div>
            </div>
        </div>
        @foreach ($pasos as $i => [$ttl, $det, $listo, $url])
            <div class="kv row-in" style="--d:{{ 100 + $i * 50 }}ms">
                <span class="paso {{ $listo ? 'ok' : '' }}">
                    @if ($listo) <x-icon name="check" size="13" stroke="2.6" /> @else {{ $i + 1 }} @endif
                </span>
                <div style="min-width:0;">
                    <div style="font-weight:600;">{{ $ttl }}</div>
                    <div style="font-size:12px;color:var(--fg3);">{{ $det }}</div>
                </div>
                <span class="kv-v">
                    @if ($listo)
                        <span class="badge b-ok"><span class="dot"></span>Listo</span>
                    @elseif ($url)
                        <a href="{{ $url }}" class="btn btn-g btn-sm">Completar <x-icon name="chev-r" size="12" /></a>
                    @else
                        <span class="pronto">Próximamente</span>
                    @endif
                </span>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .vit-wrap{max-width:900px;}
        .vit-hero{display:flex;gap:18px;align-items:center;padding:22px;border:1px solid var(--border);border-radius:12px;background:linear-gradient(135deg,#F5F8FF 0%,#fff 65%);margin-bottom:16px;}
        .vit-ico{width:52px;height:52px;border-radius:12px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .vit-ttl{font-size:19px;font-weight:700;letter-spacing:-.3px;margin:8px 0 4px;}
        .vit-txt{font-size:13px;color:var(--fg2);max-width:560px;}
        .vit-avance{width:76px;height:76px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:conic-gradient(var(--accent) calc(var(--p) * 1%), var(--surface2) 0);}
        .vit-pct{width:62px;height:62px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;}
        .paso{width:26px;height:26px;border-radius:50%;border:1.5px solid var(--border);color:var(--fg3);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .paso.ok{background:var(--green);border-color:var(--green);color:#fff;}
        @media (max-width:860px){ .vit-hero{flex-direction:column;align-items:flex-start;} }
    </style>
@endpush
