@extends('layouts.app')

@section('titulo', $titulo)
@section('subtitulo', $grupo)

@section('contenido')
<div class="mod-wrap">
    <div class="mod-hero fade-in">
        <div class="mod-ico"><x-icon :name="$icono" size="26" /></div>
        <div style="flex:1;min-width:0;">
            <span class="badge b-a"><span class="dot"></span>En preparación</span>
            <h1 class="mod-ttl">{{ $titulo }}</h1>
            <p class="mod-desc">{{ $descripcion }}</p>
        </div>
    </div>

    <div class="sec-ttl fade-in" style="animation-delay:.06s">Lo que incluirá este módulo</div>
    <div class="mod-grid">
        @foreach ($funciones as $i => $f)
            <div class="mod-feat fade-in" style="animation-delay:{{ .1 + $i * .05 }}s">
                <span class="mod-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                <span>{{ $f }}</span>
            </div>
        @endforeach
    </div>

    <div class="panel fade-in" style="animation-delay:.32s;">
        <div class="panel-hd">
            <x-icon name="sparkles" size="16" style="color:var(--accent)" />
            <div>
                <div class="panel-ttl">Mientras tanto, ya puedes usar</div>
                <div class="panel-sub">Los módulos activos de tu panel</div>
            </div>
        </div>
        <div class="mod-links">
            <a href="{{ route('admin.pedidos') }}" class="mod-link"><x-icon name="clipboard" size="16" /> Pedidos <x-icon name="chev-r" size="14" class="mod-arrow" /></a>
            <a href="{{ route('admin.productos') }}" class="mod-link"><x-icon name="package" size="16" /> Productos <x-icon name="chev-r" size="14" class="mod-arrow" /></a>
            <a href="{{ route('admin.clientes') }}" class="mod-link"><x-icon name="users" size="16" /> Clientes <x-icon name="chev-r" size="14" class="mod-arrow" /></a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .mod-wrap{max-width:860px;}
    .mod-hero{display:flex;gap:16px;align-items:flex-start;padding:22px;border:1px solid var(--border);border-radius:12px;background:linear-gradient(135deg,#F5F8FF 0%,#fff 60%);margin-bottom:22px;}
    .mod-ico{width:52px;height:52px;border-radius:12px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 16px rgba(75,124,245,.3);}
    .mod-ttl{font-size:20px;font-weight:700;letter-spacing:-.3px;margin:8px 0 4px;}
    .mod-desc{font-size:13px;color:var(--fg2);max-width:560px;}
    .mod-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:22px;}
    .mod-feat{display:flex;align-items:center;gap:12px;padding:14px 16px;border:1px solid var(--border);border-radius:10px;font-size:13px;font-weight:500;transition:border-color .2s,transform .2s var(--ease),box-shadow .2s;}
    .mod-feat:hover{border-color:var(--acm);transform:translateY(-1px);box-shadow:0 6px 16px rgba(26,35,50,.06);}
    .mod-num{font-size:11px;font-weight:700;color:var(--accent);background:var(--acl);border-radius:6px;padding:3px 7px;font-variant-numeric:tabular-nums;}
    .mod-links{display:grid;grid-template-columns:repeat(3,1fr);}
    .mod-link{display:flex;align-items:center;gap:10px;padding:14px 16px;font-size:13px;font-weight:600;color:var(--fg2);border-right:1px solid var(--border);transition:background .15s,color .15s;}
    .mod-link:last-child{border-right:none;}
    .mod-link:hover{background:var(--surface2);color:var(--accent);}
    .mod-arrow{margin-left:auto;transition:transform .2s var(--ease);}
    .mod-link:hover .mod-arrow{transform:translateX(3px);}
    @media (max-width:860px){.mod-grid,.mod-links{grid-template-columns:1fr;}.mod-link{border-right:none;border-bottom:1px solid var(--border);}}
</style>
@endpush
