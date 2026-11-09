@extends('layouts.app')

@section('titulo', 'Tienda pública')
@section('subtitulo', $totalPublicados . ($totalPublicados === 1 ? ' producto publicado' : ' productos publicados'))

@section('acciones')
    <a href="{{ route('admin.productos') }}" class="btn btn-g"><x-icon name="package" size="15" /> Gestionar productos</a>
@endsection

@section('contenido')
<div>
    <div class="panel fade-in" style="margin-bottom:16px;">
        <div class="kv" style="flex-wrap:wrap;">
            <span class="stat-ico" style="background:var(--acl);color:var(--accent);width:32px;height:32px;"><x-icon name="globe" size="16" /></span>
            <div style="min-width:0;">
                <div style="font-weight:600;">Dirección de tu tienda</div>
                @if ($dominio)
                    <a href="https://{{ $dominio }}" target="_blank" rel="noopener" style="font-size:12px;color:var(--accent);">{{ $dominio }}</a>
                @else
                    <div style="font-size:12px;color:var(--fg3);">Tu tienda aún no tiene una dirección pública. Se activará junto con Mi Vitrina.</div>
                @endif
            </div>
            <span class="kv-v">
                @if ($dominio)
                    <span class="badge b-ok"><span class="dot"></span>Publicada</span>
                @else
                    <span class="pronto"><x-icon name="clock" size="11" /> Sin publicar</span>
                @endif
            </span>
        </div>
    </div>

    <div class="sec-ttl fade-in" style="animation-delay:.06s;display:flex;align-items:center;gap:8px;">
        Vista previa del catálogo <span class="badge b-n">Así verán tus clientes tus productos</span>
    </div>

    @if (count($productos))
        <div class="shop">
            @foreach ($productos as $i => $p)
                <div class="shop-card fade-in" style="animation-delay:{{ .1 + $i * .05 }}s">
                    <div class="shop-img" @if ($p['imagen_url']) style="background-image:url('{{ $p['imagen_url'] }}')" @endif>
                        @unless ($p['imagen_url']) <x-icon name="image" size="26" stroke="1.5" /> @endunless
                        @if ($p['precio_oferta']) <span class="badge b-r shop-tag">Oferta</span> @endif
                    </div>
                    <div class="shop-bd">
                        <div class="shop-cat">{{ $p['categoria'] ?: 'Sin categoría' }}</div>
                        <div class="shop-nom">{{ $p['nombre'] }}</div>
                        <div class="shop-pr">
                            <strong>S/ {{ number_format($p['precio_oferta'] ?? $p['precio'], 2) }}</strong>
                            @if ($p['precio_oferta']) <s>S/ {{ number_format($p['precio'], 2) }}</s> @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="panel">
            <x-vacio icono="package" titulo="Aún no tienes productos publicados" texto="Publica productos para que aparezcan en tu tienda pública.">
                <a href="{{ route('admin.productos') }}?nuevo=1" class="btn btn-p btn-sm"><x-icon name="plus" size="14" /> Crear producto</a>
            </x-vacio>
        </div>
    @endif
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .shop{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
        .shop-card{border:1px solid var(--border);border-radius:10px;overflow:hidden;background:#fff;transition:transform .2s var(--ease),box-shadow .2s var(--ease);}
        .shop-card:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(26,35,50,.08);}
        .shop-img{position:relative;aspect-ratio:4/3;background:var(--surface2) center/cover no-repeat;display:flex;align-items:center;justify-content:center;color:var(--fg3);}
        .shop-tag{position:absolute;top:8px;left:8px;}
        .shop-bd{padding:12px;}
        .shop-cat{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--fg3);}
        .shop-nom{font-weight:600;font-size:13px;margin:2px 0 6px;}
        .shop-pr{display:flex;align-items:baseline;gap:6px;font-variant-numeric:tabular-nums;}
        .shop-pr s{font-size:11px;color:var(--fg3);}
        @media (max-width:1100px){ .shop{grid-template-columns:repeat(2,1fr);} }
    </style>
@endpush
