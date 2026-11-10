@extends('layouts.app')

@section('titulo', 'Personalizar')
@section('subtitulo', 'Datos del negocio y colores del panel')

@section('contenido')
<div x-data="personalizarPage(@js($negocio), @js($porDefecto))" class="g-21">
    <form class="panel fade-in" @submit.prevent="guardar()" novalidate>
        <div class="panel-hd"><div class="panel-ttl">Información del negocio</div></div>
        <div style="padding:18px 16px 4px;">
            <div class="field">
                <label class="lbl">Nombre del negocio <span class="req">*</span></label>
                <input class="inp" :class="{ err: errores.nombre_negocio }" x-model="form.nombre_negocio" maxlength="150" placeholder="Ej: Restaurante El Clásico">
                <div class="ferr" x-show="errores.nombre_negocio" x-text="errores.nombre_negocio?.[0]"></div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label class="lbl">Teléfono</label>
                    <input class="inp" :class="{ err: errores.telefono }" x-model="form.telefono" maxlength="20" placeholder="987 654 321" inputmode="tel">
                    <div class="ferr" x-show="errores.telefono" x-text="errores.telefono?.[0]"></div>
                </div>
                <div class="field">
                    <label class="lbl">RUC</label>
                    <input class="inp" :class="{ err: errores.ruc }" x-model="form.ruc" maxlength="11" placeholder="20123456789" inputmode="numeric">
                    <div class="ferr" x-show="errores.ruc" x-text="errores.ruc?.[0]"></div>
                </div>
            </div>
            <div class="field">
                <label class="lbl">Correo de la cuenta</label>
                <input class="inp" :value="form.email" disabled>
                <div class="hint">El correo de acceso no se puede cambiar desde aquí.</div>
            </div>

            <div class="form-sec" style="margin-top:8px;">Colores</div>
            <div class="field-row">
                @foreach (['color_menu' => ['Color del menú lateral', ['#2B6EF6', '#1E3A8A', '#0F766E', '#334155', '#7C3AED']], 'color_accion' => ['Color de los botones', ['#F47B20', '#2B6EF6', '#16A34A', '#DC2626', '#1A2332']]] as $campo => [$etiqueta, $muestras])
                    <div class="field">
                        <label class="lbl">{{ $etiqueta }}</label>
                        <div class="color-row">
                            <label class="color-pick" :style="'background:' + form.{{ $campo }}">
                                <input type="color" x-model="form.{{ $campo }}" @input="aplicar()">
                            </label>
                            <input class="inp" :class="{ err: errores.{{ $campo }} }" x-model="form.{{ $campo }}" @change="aplicar()" maxlength="7" style="font-family:ui-monospace,monospace;text-transform:uppercase;">
                        </div>
                        <div class="swatches">
                            @foreach ($muestras as $m)
                                <button type="button" class="sw" style="background:{{ $m }}" :class="{ on: form.{{ $campo }}.toUpperCase() === '{{ $m }}' }" @click="form.{{ $campo }} = '{{ $m }}'; aplicar()" title="{{ $m }}"></button>
                            @endforeach
                        </div>
                        <div class="ferr" x-show="errores.{{ $campo }}" x-text="errores.{{ $campo }}?.[0]"></div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="modal-ft" style="border-radius:0 0 10px 10px;">
            <button type="button" class="btn btn-g" style="margin-right:auto;" @click="restaurar()">Restaurar colores</button>
            <button type="button" class="btn btn-g" @click="descartar()">Descartar</button>
            <button type="submit" class="btn btn-p" :disabled="guardando">
                <x-icon name="loader" size="15" class="spin" x-show="guardando" />
                <x-icon name="check" size="15" stroke="2.4" x-show="!guardando" />
                <span x-text="guardando ? 'Guardando¦' : 'Guardar cambios'"></span>
            </button>
        </div>
    </form>

    <div class="fade-in" style="animation-delay:.08s">
        <div class="sec-ttl">Vista previa</div>
        <div class="prev">
            <div class="prev-nav" :style="'background:' + form.color_menu">
                <div class="prev-brand" x-text="form.nombre_negocio || 'Tu negocio'"></div>
                <div class="prev-item on"></div><div class="prev-item"></div><div class="prev-item"></div><div class="prev-item"></div>
            </div>
            <div class="prev-main">
                <div class="prev-top">
                    <span class="prev-line" style="width:40%"></span>
                    <span class="prev-btn" :style="'background:' + form.color_accion">Nuevo</span>
                </div>
                <div class="prev-card"></div><div class="prev-card" style="width:80%"></div><div class="prev-card" style="width:60%"></div>
            </div>
        </div>
        <div class="hint" style="margin-top:8px;">Los cambios de color se ven en todo el panel al instante. Se guardan al presionar «Guardar cambios».</div>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .color-row{display:flex;gap:8px;align-items:center;}
        .color-pick{width:38px;height:36px;border-radius:8px;border:1px solid var(--border);flex-shrink:0;cursor:pointer;position:relative;overflow:hidden;}
        .color-pick input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
        .swatches{display:flex;gap:6px;margin-top:8px;}
        .sw{width:22px;height:22px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px var(--border);cursor:pointer;transition:transform .15s var(--ease),box-shadow .15s;}
        .sw:hover{transform:scale(1.1);}
        .sw.on{box-shadow:0 0 0 2px var(--fg);}
        .prev{display:flex;height:240px;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow);background:#fff;}
        .prev-nav{width:34%;padding:14px 10px;display:flex;flex-direction:column;gap:8px;transition:background .25s;}
        .prev-brand{color:#fff;font-weight:700;font-size:12px;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .prev-item{height:8px;border-radius:4px;background:rgba(255,255,255,.25);}
        .prev-item.on{background:rgba(255,255,255,.6);}
        .prev-main{flex:1;padding:14px;display:flex;flex-direction:column;gap:10px;}
        .prev-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;}
        .prev-line{height:8px;border-radius:4px;background:var(--surface2);}
        .prev-btn{color:#fff;font-size:10px;font-weight:600;padding:4px 10px;border-radius:6px;transition:background .25s;}
        .prev-card{height:34px;border-radius:8px;border:1px solid var(--border);background:var(--surface3);}
    </style>
@endpush

@push('scripts')
<script>
    gh.ready(() => {
        Alpine.data('personalizarPage', (negocio, porDefecto) => ({
            form: { ...negocio }, original: { ...negocio }, porDefecto, errores: {}, guardando: false,
            hex(v) { return /^#[0-9a-f]{6}$/i.test(v || ''); },
            aplicar(f = this.form) {
                const raiz = document.documentElement.style;
                if (this.hex(f.color_menu)) raiz.setProperty('--nav-bg', f.color_menu);
                if (this.hex(f.color_accion)) raiz.setProperty('--cta', f.color_accion);
            },
            restaurar() { Object.assign(this.form, this.porDefecto); this.aplicar(); },
            descartar() { this.form = { ...this.original }; this.errores = {}; this.aplicar(); },
            destroy() { this.aplicar(this.original); },
            async guardar() {
                this.guardando = true; this.errores = {};
                const r = await gh.api('{{ route('admin.personalizar.guardar') }}', { method: 'POST', body: this.form });
                this.guardando = false;
                if (r.status === 422) { this.errores = r.data.errors || {}; return; }
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar', 'error'); return; }
                this.form.color_menu = this.form.color_menu.toUpperCase();
                this.form.color_accion = this.form.color_accion.toUpperCase();
                this.original = { ...this.form };
                document.querySelectorAll('.nav-un').forEach(el => el.textContent = r.data.data.negocio);
                document.querySelectorAll('.top-av, .nav-av').forEach(el => { el.textContent = r.data.data.iniciales; el.title = r.data.data.negocio; });
                Alpine.store('toasts').push(r.data.message);
            },
        }));
    });
</script>
@endpush
