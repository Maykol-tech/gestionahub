@extends('layouts.app')

@section('titulo', 'Formas de entrega')
@section('subtitulo', 'Cómo reciben tus clientes los pedidos de la tienda online')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-ent { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; }
        .grid-ent .ancho { grid-column:span 2; }
        @media (max-width: 860px) { .grid-ent { grid-template-columns:1fr 1fr; } }
        @media (max-width: 560px) { .grid-ent { grid-template-columns:1fr; } .grid-ent .ancho { grid-column:auto; } }
        .ent-lista { display:grid; gap:10px; }
        .ent { display:flex; gap:14px; align-items:center; padding:14px 16px; transition:opacity .2s, transform .2s; }
        .ent:hover { transform:translateY(-1px); }
        .ent.off { opacity:.55; }
        .ent-ico { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; font-size:22px; background:var(--bg); flex:none; }
        .ent-txt { flex:1; min-width:0; }
        .ent-txt b { display:block; }
        .ent-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
        .ent-chip { font-size:12px; padding:2px 9px; border-radius:99px; background:var(--bg); border:1px solid var(--border); color:var(--fg2); }
        .ent-acc { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
        .tipos { display:flex; gap:8px; flex-wrap:wrap; }
        .tipo-op { display:flex; align-items:center; gap:8px; border:1.5px solid var(--border); border-radius:12px; padding:9px 12px; cursor:pointer; font-size:13px; font-weight:600; transition:border-color .2s, background .2s; }
        .tipo-op.on { border-color:var(--brand, #2B6EF6); background:color-mix(in srgb, var(--brand, #2B6EF6) 8%, transparent); }
        .tipo-op input { display:none; }
        @media (max-width: 560px) { .ent { flex-wrap:wrap; } .ent-acc { width:100%; justify-content:flex-start; } }
    </style>
@endpush

@section('contenido')
@php
    $iconos = ['delivery' => '🛵', 'recojo' => '🏪', 'agencia' => '📦', 'local' => '🍽️'];
    $ayuda = ['delivery' => 'Lo lleva tu repartidor. Pide dirección.', 'recojo' => 'El cliente pasa a recoger.', 'agencia' => 'Envío a provincia por agencia. Pide dirección.', 'local' => 'Para comer o recibir en tu local.'];
@endphp
<div x-data="{
        edit: null,
        f: { nombre: '', tipo: 'delivery', costo: 0, tiempo_estimado: '', monto_minimo: 0, gratis_desde: '', usa_zonas: false, descripcion: '' },
        editar(e) { this.edit = e.id; this.f = { ...e, gratis_desde: e.gratis_desde ?? '' }; window.scrollTo({ top: 0, behavior: 'smooth' }); },
        nuevo() { this.edit = null; this.f = { nombre: '', tipo: 'delivery', costo: 0, tiempo_estimado: '', monto_minimo: 0, gratis_desde: '', usa_zonas: false, descripcion: '' }; }
    }">
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="panel fade-in" style="padding:18px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
            <b x-text="edit ? 'Editar forma de entrega' : 'Nueva forma de entrega'"></b>
            <button type="button" class="btn btn-g btn-sm" style="margin-left:auto;" x-show="edit" x-cloak @click="nuevo()">Cancelar edición</button>
        </div>
        <form method="POST" :action="edit ? '{{ url('admin/entregas') }}/' + edit : '{{ route('admin.entregas.guardar') }}'">
            @csrf
            <template x-if="edit"><input type="hidden" name="_method" value="PUT"></template>
            <div class="tipos" style="margin-bottom:14px;">
                @foreach ($tipos as $k => $label)
                    <label class="tipo-op" :class="{ on: f.tipo === '{{ $k }}' }" title="{{ $ayuda[$k] ?? '' }}">
                        <input type="radio" name="tipo" value="{{ $k }}" x-model="f.tipo"><span>{{ $iconos[$k] ?? '📦' }}</span>{{ $label }}
                    </label>
                @endforeach
            </div>
            <div class="grid-ent">
                <div class="ancho"><label class="lbl" for="nombre">Nombre que ve el cliente</label><input class="inp" id="nombre" name="nombre" maxlength="80" x-model="f.nombre" placeholder="Ej.: Delivery a domicilio" required></div>
                <div><label class="lbl" for="costo">Costo de envío (S/)</label><input class="inp" id="costo" name="costo" type="number" step="0.01" min="0" x-model="f.costo" required></div>
                <div><label class="lbl" for="tiempo_estimado">Tiempo estimado</label><input class="inp" id="tiempo_estimado" name="tiempo_estimado" maxlength="40" x-model="f.tiempo_estimado" placeholder="30 a 45 min"></div>
                <div><label class="lbl" for="monto_minimo">Pedido mínimo (S/)</label><input class="inp" id="monto_minimo" name="monto_minimo" type="number" step="0.01" min="0" x-model="f.monto_minimo"></div>
                <div><label class="lbl" for="gratis_desde">Envío gratis desde (S/)</label><input class="inp" id="gratis_desde" name="gratis_desde" type="number" step="0.01" min="0" x-model="f.gratis_desde" placeholder="Opcional"></div>
                <div class="ancho"><label class="lbl" for="descripcion">Nota para el cliente</label><input class="inp" id="descripcion" name="descripcion" maxlength="200" x-model="f.descripcion" placeholder="Ej.: Solo Lima Metropolitana"></div>
            </div>
            <label x-show="f.tipo === 'delivery'" style="display:flex;gap:8px;align-items:center;margin-top:12px;font-size:13px;">
                <input type="checkbox" name="usa_zonas" value="1" x-model="f.usa_zonas">
                Cobrar según la zona del cliente (usa las zonas de <a href="{{ url('admin/repartos') }}">Repartos</a>)
            </label>
            <button class="btn btn-p" type="submit" style="margin-top:14px;" x-text="edit ? 'Guardar cambios' : 'Agregar forma de entrega'"></button>
        </form>
    </div>

    <div class="ent-lista">
        @forelse ($formas as $i => $f)
            <div class="panel ent fade-in {{ $f->activa ? '' : 'off' }}" style="animation-delay:{{ min($i, 8) * .04 }}s">
                <div class="ent-ico">{{ $iconos[$f->tipo] ?? '📦' }}</div>
                <div class="ent-txt">
                    <b>{{ $f->nombre }}</b>
                    <span class="sub">{{ $tipos[$f->tipo] ?? $f->tipo }}{{ $f->descripcion ? ' · ' . $f->descripcion : '' }}</span>
                    <div class="ent-chips">
                        <span class="ent-chip">{{ $f->usa_zonas ? 'Costo según zona' : ((float) $f->costo > 0 ? 'S/ ' . number_format($f->costo, 2) : 'Sin costo') }}</span>
                        @if ($f->tiempo_estimado)<span class="ent-chip">⏱ {{ $f->tiempo_estimado }}</span>@endif
                        @if ((float) $f->monto_minimo > 0)<span class="ent-chip">Mínimo S/ {{ number_format($f->monto_minimo, 2) }}</span>@endif
                        @if ($f->gratis_desde)<span class="ent-chip">Gratis desde S/ {{ number_format($f->gratis_desde, 2) }}</span>@endif
                        <span class="ent-chip" style="{{ $f->activa ? 'color:#15803D;' : '' }}">{{ $f->activa ? 'Visible en la tienda' : 'Oculta' }}</span>
                    </div>
                </div>
                <div class="ent-acc">
                    <button type="button" class="btn btn-g btn-sm" @click="editar(@js(['id' => $f->id, 'nombre' => $f->nombre, 'tipo' => $f->tipo, 'costo' => (float) $f->costo, 'tiempo_estimado' => $f->tiempo_estimado, 'monto_minimo' => (float) $f->monto_minimo, 'gratis_desde' => $f->gratis_desde !== null ? (float) $f->gratis_desde : null, 'usa_zonas' => (bool) $f->usa_zonas, 'descripcion' => $f->descripcion]))">Editar</button>
                    <form method="POST" action="{{ route('admin.entregas.alternar', $f->id) }}">@csrf <button class="btn btn-g btn-sm" type="submit">{{ $f->activa ? 'Ocultar' : 'Mostrar' }}</button></form>
                    <form method="POST" action="{{ route('admin.entregas.eliminar', $f->id) }}" onsubmit="return confirm('¿Eliminar esta forma de entrega?')">@csrf @method('DELETE') <button class="btn btn-g btn-sm" type="submit" style="color:#B91C1C;">Eliminar</button></form>
                </div>
            </div>
        @empty
            <div class="panel empty fade-in"><div style="font-size:32px;">🛵</div><div style="font-weight:600;color:var(--fg2);">Aún no hay formas de entrega</div><div class="sub">Agrega al menos una para que tus clientes puedan comprar en la tienda.</div></div>
        @endforelse
    </div>
</div>
@endsection
