@extends('layouts.app')

@section('titulo', 'Sucursales')
@section('subtitulo', '1 de ' . $maxSucursales . ($maxSucursales === 1 ? ' sucursal' : ' sucursales') . ' de tu plan')

@section('acciones')
    <button class="btn btn-p" x-data @click="$dispatch('nueva-sucursal')"><x-icon name="plus" size="15" stroke="2.4" /> Nueva sucursal</button>
@endsection

@section('contenido')
<div x-data="{ modal: false }" @nueva-sucursal.window="modal = true">
    <div class="g-3">
        <div class="suc fade-in">
            <div class="suc-hd">
                <span class="stat-ico" style="background:var(--acl);color:var(--accent);width:36px;height:36px;border-radius:9px;"><x-icon name="map-pin" size="17" /></span>
                <div style="min-width:0;">
                    <div style="font-weight:700;">{{ $principal['nombre'] }}</div>
                    <div style="font-size:12px;color:var(--fg3);">Sede principal</div>
                </div>
                <span class="badge b-ok" style="margin-left:auto;"><span class="dot"></span>Activa</span>
            </div>
            <div class="kv"><x-icon name="phone" size="14" style="color:var(--fg3)" /> <span>{{ $principal['telefono'] ?: 'Sin teléfono' }}</span></div>
            <div class="kv"><x-icon name="mail" size="14" style="color:var(--fg3)" /> <span style="overflow:hidden;text-overflow:ellipsis;">{{ $principal['email'] ?: 'Sin correo' }}</span></div>
            <div class="kv"><x-icon name="calendar" size="14" style="color:var(--fg3)" /> <span>{{ $principal['desde'] ? 'Desde el ' . $principal['desde'] : 'Fecha de alta no disponible' }}</span></div>
            <div class="suc-ft"><a href="{{ route('admin.modulo', 'personalizar') }}" class="btn btn-g btn-sm"><x-icon name="pen" size="13" /> Editar datos</a></div>
        </div>

        <button type="button" class="suc suc-nueva fade-in" style="animation-delay:.06s" @click="modal = true">
            <span class="suc-plus"><x-icon name="plus" size="20" /></span>
            <span style="font-weight:600;color:var(--fg);">Agregar sucursal</span>
            <span style="font-size:12px;color:var(--fg3);">Horarios, zona de delivery y cajero por sede</span>
        </button>
    </div>

    <div class="modal-bg" x-show="modal" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @keydown.escape.window="modal = false" @mousedown.self="modal = false">
        <div class="modal" style="max-width:460px;">
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="map-pin" size="18" /></div>
                <div>
                    <div class="modal-ttl">Nueva sucursal</div>
                    <div class="modal-sub">Gestión multi-local</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = false" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="nota" style="margin-bottom:0;">
                    <x-icon name="sparkles" size="16" />
                    <div>
                        El registro de sucursales estará disponible pronto.
                        @if ($maxSucursales <= 1)
                            Tu plan actual incluye 1 sucursal; para abrir más necesitarás el plan Negocio o Pro.
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-ft">
                <a href="{{ route('admin.modulo', 'planes') }}" class="btn btn-g">Ver planes</a>
                <button type="button" class="btn btn-p" @click="modal = false">Entendido</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .suc{border:1px solid var(--border);border-radius:10px;background:#fff;box-shadow:var(--shadow);overflow:hidden;transition:transform .2s var(--ease),box-shadow .2s var(--ease);}
        .suc:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(26,35,50,.08);}
        .suc-hd{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);}
        .suc .kv{gap:8px;color:var(--fg2);}
        .suc-ft{padding:10px 16px;background:var(--surface3);border-top:1px solid var(--border);}
        .suc-nueva{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;min-height:230px;border-style:dashed;border-width:1.5px;box-shadow:none;cursor:pointer;font-family:inherit;padding:20px;text-align:center;}
        .suc-nueva:hover{border-color:var(--accent);background:#FAFCFF;}
        .suc-plus{width:44px;height:44px;border-radius:50%;background:var(--acl);color:var(--accent);display:flex;align-items:center;justify-content:center;margin-bottom:4px;}
    </style>
@endpush
