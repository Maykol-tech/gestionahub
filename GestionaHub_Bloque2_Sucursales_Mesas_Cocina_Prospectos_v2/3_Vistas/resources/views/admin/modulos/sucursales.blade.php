@extends('layouts.app')

@section('titulo', 'Sucursales')
@section('subtitulo', $usadas . ' de ' . $maxSucursales . ($maxSucursales === 1 ? ' sucursal' : ' sucursales') . ' de tu plan')

@section('acciones')
    <button class="btn btn-p" type="button" @click="$dispatch('nueva-sucursal')"><x-icon name="plus" size="15" stroke="2.4" /> Nueva sucursal</button>
@endsection

@section('contenido')
@php
    $puedeAgregar = $usadas < $maxSucursales;
    $urlGuardar = route('admin.sucursales.guardar');
@endphp
<div x-data="{
        modal: false,
        modo: 'crear',
        url: '{{ $urlGuardar }}',
        form: { nombre: '', direccion: '', telefono: '', email: '', horario: '', activo: true },
        abrirNueva() { this.modo = 'crear'; this.url = '{{ $urlGuardar }}'; this.form = { nombre: '', direccion: '', telefono: '', email: '', horario: '', activo: true }; this.modal = true; },
        abrirEditar(s) { this.modo = 'editar'; this.url = s.url; this.form = { nombre: s.nombre, direccion: s.direccion || '', telefono: s.telefono || '', email: s.email || '', horario: s.horario || '', activo: !!s.activo }; this.modal = true; }
     }"
     @nueva-sucursal.window="abrirNueva()"
     class="fade-in">

    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="g-3">
        {{-- Sede principal --}}
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

        {{-- Sucursales del negocio --}}
        @foreach ($sucursales as $s)
            <div class="suc fade-in">
                <div class="suc-hd">
                    <span class="stat-ico" style="background:var(--acl);color:var(--accent);width:36px;height:36px;border-radius:9px;"><x-icon name="map-pin" size="17" /></span>
                    <div style="min-width:0;">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;">{{ $s->nombre }}</div>
                        <div style="font-size:12px;color:var(--fg3);">Sucursal</div>
                    </div>
                    @if ($s->activo)
                        <span class="badge b-ok" style="margin-left:auto;"><span class="dot"></span>Activa</span>
                    @else
                        <span class="badge b-n" style="margin-left:auto;">Inactiva</span>
                    @endif
                </div>
                <div class="kv"><x-icon name="phone" size="14" style="color:var(--fg3)" /> <span>{{ $s->telefono ?: 'Sin teléfono' }}</span></div>
                <div class="kv"><x-icon name="map-pin" size="14" style="color:var(--fg3)" /> <span>{{ $s->direccion ?: 'Sin dirección' }}</span></div>
                <div class="kv"><x-icon name="calendar" size="14" style="color:var(--fg3)" /> <span>{{ $s->horario ?: 'Sin horario' }}</span></div>
                <div class="suc-ft" style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-g btn-sm"
                            data-s="{{ json_encode(['url' => route('admin.sucursales.actualizar', $s->id), 'nombre' => $s->nombre, 'direccion' => $s->direccion, 'telefono' => $s->telefono, 'email' => $s->email, 'horario' => $s->horario, 'activo' => (bool) $s->activo]) }}"
                            @click="abrirEditar(JSON.parse($el.dataset.s))"><x-icon name="pen" size="13" /> Editar</button>
                    <form method="POST" action="{{ route('admin.sucursales.estado', $s->id) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-g btn-sm" type="submit">{{ $s->activo ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.sucursales.eliminar', $s->id) }}" onsubmit="return confirm('¿Eliminar la sucursal «{{ $s->nombre }}»?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-g btn-sm" type="submit" style="color:#B91C1C;">Eliminar</button>
                    </form>
                </div>
            </div>
        @endforeach

        @if ($puedeAgregar)
            <button type="button" class="suc suc-nueva fade-in" style="animation-delay:.06s" @click="abrirNueva()">
                <span class="suc-plus"><x-icon name="plus" size="20" /></span>
                <span style="font-weight:600;color:var(--fg);">Agregar sucursal</span>
                <span style="font-size:12px;color:var(--fg3);">Dirección, horario y contacto por sede</span>
            </button>
        @else
            <div class="suc suc-nueva" style="cursor:default;">
                <span class="suc-plus" style="background:#F1F5F9;color:var(--fg3);"><x-icon name="sparkles" size="20" /></span>
                <span style="font-weight:600;color:var(--fg);">Límite del plan alcanzado</span>
                <a href="{{ route('admin.modulo', 'planes') }}" class="btn btn-g btn-sm">Ver planes</a>
            </div>
        @endif
    </div>

    {{-- Formulario crear / editar --}}
    <div class="modal-bg" x-show="modal" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @keydown.escape.window="modal = false" @mousedown.self="modal = false">
        <form class="modal" style="max-width:460px;" method="POST" :action="url">
            @csrf
            <input type="hidden" name="_method" :value="modo === 'editar' ? 'PUT' : 'POST'">
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="map-pin" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="modo === 'editar' ? 'Editar sucursal' : 'Nueva sucursal'"></div>
                    <div class="modal-sub">Gestión multi-local</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = false" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd" style="display:grid;gap:10px;">
                <div><label class="lbl" for="suc-nombre">Nombre</label><input class="inp" id="suc-nombre" name="nombre" maxlength="120" required x-model="form.nombre"></div>
                <div><label class="lbl" for="suc-direccion">Dirección</label><input class="inp" id="suc-direccion" name="direccion" maxlength="200" x-model="form.direccion"></div>
                <div><label class="lbl" for="suc-telefono">Teléfono</label><input class="inp" id="suc-telefono" name="telefono" maxlength="20" x-model="form.telefono"></div>
                <div><label class="lbl" for="suc-email">Correo</label><input class="inp" id="suc-email" name="email" type="email" maxlength="120" x-model="form.email"></div>
                <div><label class="lbl" for="suc-horario">Horario</label><input class="inp" id="suc-horario" name="horario" maxlength="120" placeholder="Lun a Sáb 9:00 a 21:00" x-model="form.horario"></div>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;">
                    <input type="checkbox" name="activo" value="1" x-model="form.activo"> Sucursal activa
                </label>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="modal = false">Cancelar</button>
                <button type="submit" class="btn btn-p" x-text="modo === 'editar' ? 'Guardar cambios' : 'Crear sucursal'"></button>
            </div>
        </form>
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
