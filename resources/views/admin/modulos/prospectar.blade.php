@extends('layouts.app')

@section('titulo', 'Prospectar')
@section('subtitulo', 'Encuentra negocios que pueden ser tus clientes')

@section('acciones')
    <span class="pronto"><x-icon name="clock" size="11" /> Próximamente</span>
@endsection

@section('contenido')
<div x-data="{ rubro: '', distrito: '', radio: '2', buscado: false }">
    <form class="panel fade-in" style="margin-bottom:16px;" @submit.prevent="buscado = true">
        <div class="panel-hd"><div class="panel-ttl">Buscar prospectos</div></div>
        <div class="busq">
            <div class="field" style="margin:0;">
                <label class="lbl">Rubro</label>
                <select class="inp" x-model="rubro">
                    <option value="">Todos los rubros</option>
                    <option>Restaurantes</option><option>Bodegas</option><option>Panaderías</option>
                    <option>Cafeterías</option><option>Oficinas</option><option>Colegios</option>
                </select>
            </div>
            <div class="field" style="margin:0;">
                <label class="lbl">Distrito o zona</label>
                <input class="inp" x-model="distrito" maxlength="80" placeholder="Ej: San Martín de Porres">
            </div>
            <div class="field" style="margin:0;">
                <label class="lbl">Radio</label>
                <select class="inp" x-model="radio">
                    <option value="1">1 km</option><option value="2">2 km</option><option value="5">5 km</option>
                </select>
            </div>
            <button type="submit" class="btn btn-p" style="align-self:flex-end;"><x-icon name="search" size="15" /> Buscar</button>
        </div>
    </form>

    <div class="panel fade-in" style="animation-delay:.08s">
        <div class="panel-hd">
            <div>
                <div class="panel-ttl">Prospectos</div>
                <div class="panel-sub">0 encontrados</div>
            </div>
        </div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead><tr><th>Negocio</th><th>Rubro</th><th>Distancia</th><th>Teléfono</th><th>Estado</th></tr></thead>
            </table>
        </div>
        <div x-show="!buscado">
            <x-vacio icono="search" titulo="Aún no hay prospectos" texto="Elige un rubro y una zona para encontrar negocios cercanos a los que puedas ofrecer tus productos." />
        </div>
        <div x-show="buscado" x-cloak>
            <x-vacio icono="map" titulo="La búsqueda estará disponible pronto"
                     texto="Estamos conectando el buscador de negocios por zona. Tus filtros se usarán apenas esté listo." />
        </div>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .busq{display:grid;grid-template-columns:1fr 1.4fr .6fr auto;gap:12px;padding:16px;}
        @media (max-width:860px){ .busq{grid-template-columns:1fr;} }
    </style>
@endpush
