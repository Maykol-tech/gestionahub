@extends('layouts.app')

@section('titulo', 'Configura tu negocio')
@section('subtitulo', 'Cuatro pasos y tu panel queda listo para tu rubro')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .ob { max-width:860px; margin:0 auto; }
        .ob-pasos { display:flex; gap:8px; margin-bottom:18px; }
        .ob-paso { flex:1; display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--fg3); }
        .ob-paso i { width:28px; height:28px; border-radius:50%; display:grid; place-items:center; font-style:normal; background:var(--bg); border:1px solid var(--bd); transition: all .25s; flex-shrink:0; }
        .ob-paso.on { color:var(--fg); } .ob-paso.on i { background:var(--accent); color:#fff; border-color:var(--accent); transform: scale(1.08); }
        .ob-paso.ok i { background:var(--greenl); color:var(--green); border-color:transparent; }
        .ob-paso::after { content:''; flex:1; height:2px; background:var(--bd); border-radius:2px; }
        .ob-paso:last-child::after { display:none; }
        .ob-card { padding:24px; }
        .ob-card h2 { font-size:20px; margin:0 0 4px; } .ob-card > p { color:var(--fg3); margin:0 0 18px; font-size:14px; }
        .rubros { display:grid; grid-template-columns:repeat(auto-fill, minmax(170px, 1fr)); gap:10px; }
        .rubro { border:2px solid var(--bd); border-radius:14px; padding:16px; text-align:left; background:var(--card, #fff); cursor:pointer; font:inherit; transition: border-color .2s, transform .2s, box-shadow .2s; }
        .rubro:hover { transform: translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,.08); }
        .rubro.on { border-color:var(--accent); background:var(--acl); }
        .rubro .ic { font-size:28px; display:block; margin-bottom:6px; }
        .rubro b { display:block; font-size:14px; }
        .rubro small { color:var(--fg3); font-size:12px; }
        .ob-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .ob-logo { display:flex; gap:14px; align-items:center; }
        .ob-logo .prev { width:84px; height:84px; border-radius:16px; border:1px dashed var(--bd); display:grid; place-items:center; overflow:hidden; background:var(--bg); color:var(--fg3); font-size:12px; text-align:center; }
        .ob-logo .prev img { width:100%; height:100%; object-fit:contain; }
        .ob-colores { display:flex; gap:16px; flex-wrap:wrap; margin-top:14px; }
        .ob-colores label { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; }
        .ob-colores input[type=color] { width:46px; height:38px; padding:2px; border:1px solid var(--bd); border-radius:8px; background:none; }
        .ob-muestra { margin-top:16px; border-radius:14px; overflow:hidden; border:1px solid var(--bd); }
        .ob-muestra .hd { padding:18px; color:#fff; display:flex; align-items:center; gap:10px; font-weight:700; transition: background .3s; }
        .ob-muestra .hd img { width:34px; height:34px; border-radius:8px; object-fit:contain; background:#fff; }
        .ob-muestra .bd { padding:14px 18px; display:flex; gap:8px; align-items:center; }
        .ob-muestra .bd span { padding:8px 14px; border-radius:10px; color:#fff; font-weight:700; font-size:13px; transition: background .3s; }
        .mods { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:8px; }
        .mod { display:flex; gap:8px; align-items:center; border:1px solid var(--bd); border-radius:10px; padding:10px 12px; cursor:pointer; font-size:13px; transition: background .2s, border-color .2s; }
        .mod.on { border-color:var(--accent); background:var(--acl); }
        .ob-ft { display:flex; justify-content:space-between; align-items:center; margin-top:22px; gap:10px; }
        .ob-ej { display:flex; gap:8px; align-items:flex-start; margin-top:16px; padding:12px; border-radius:12px; background:var(--bg); font-size:13px; }
        .ob-anim { animation: ob-in .3s ease both; }
        @keyframes ob-in { from { opacity:0; transform: translateX(14px); } to { opacity:1; transform:none; } }
        @media (max-width: 640px) { .ob-grid { grid-template-columns:1fr; } .ob-paso span { display:none; } }
    </style>
@endpush

@section('contenido')
<div class="ob" x-data="ghBienvenida(@js($cfg))">
    <div class="ob-pasos">
        <template x-for="(t, i) in titulos" :key="i">
            <div class="ob-paso" :class="{ on: paso === i, ok: paso > i }"><i x-text="paso > i ? '✓' : i + 1"></i><span x-text="t"></span></div>
        </template>
    </div>

    <div class="panel ob-card">
        {{-- Paso 1: rubro --}}
        <div x-show="paso === 0" class="ob-anim">
            <h2>¿A qué se dedica tu negocio?</h2>
            <p>Con esto preparamos tu menú, tus categorías y los textos de tu página.</p>
            <div class="rubros">
                <template x-for="r in rubros" :key="r.clave">
                    <button type="button" class="rubro" :class="{ on: d.rubro === r.clave }" @click="elegirRubro(r)">
                        <span class="ic" x-text="r.icono"></span><b x-text="r.nombre"></b>
                        <small x-text="r.categorias.slice(0, 3).join(' · ')"></small>
                    </button>
                </template>
            </div>
        </div>

        {{-- Paso 2: datos --}}
        <div x-show="paso === 1" x-cloak class="ob-anim">
            <h2>Datos de tu negocio</h2>
            <p>Aparecen en tu página, tus comprobantes y tus mensajes.</p>
            <div class="ob-grid">
                <div class="field"><label class="lbl" for="ob-nombre">Nombre del negocio *</label><input class="inp" id="ob-nombre" maxlength="150" x-model="d.nombre_negocio" :class="{ err: err.nombre_negocio }"><div class="ferr" x-text="e('nombre_negocio')"></div></div>
                <div class="field"><label class="lbl" for="ob-tel">WhatsApp o teléfono</label><input class="inp" id="ob-tel" maxlength="20" x-model="d.telefono" placeholder="987 654 321" :class="{ err: err.telefono }"><div class="ferr" x-text="e('telefono')"></div></div>
                <div class="field"><label class="lbl" for="ob-dir">Dirección</label><input class="inp" id="ob-dir" maxlength="255" x-model="d.direccion" placeholder="Av. Principal 123, Lima"></div>
                <div class="field"><label class="lbl" for="ob-ruc">RUC (opcional)</label><input class="inp" id="ob-ruc" maxlength="11" inputmode="numeric" x-model="d.ruc" :class="{ err: err.ruc }"><div class="ferr" x-text="e('ruc')"></div></div>
            </div>
        </div>

        {{-- Paso 3: logo y colores --}}
        <div x-show="paso === 2" x-cloak class="ob-anim">
            <h2>Logo y colores</h2>
            <p>Puedes cambiarlos cuando quieras en Personalizar.</p>
            <div class="ob-logo">
                <div class="prev"><template x-if="logoPrev"><img :src="logoPrev" alt="Logo"></template><span x-show="!logoPrev">Sin logo</span></div>
                <div>
                    <label class="btn btn-g" for="ob-logo">Subir logo</label>
                    <input id="ob-logo" type="file" accept="image/png,image/jpeg,image/webp" hidden @change="elegirLogo($event)">
                    <div style="font-size:12px;color:var(--fg3);margin-top:6px;">PNG, JPG o WEBP, hasta 4 MB.</div>
                    <div class="ferr" x-text="e('logo')"></div>
                </div>
            </div>
            <div class="ob-colores">
                <label><input type="color" x-model="d.color_primario"> Color principal</label>
                <label><input type="color" x-model="d.color_secundario"> Color de botones</label>
            </div>
            <div class="ob-muestra">
                <div class="hd" :style="'background:' + d.color_primario"><template x-if="logoPrev"><img :src="logoPrev" alt=""></template><span x-text="d.nombre_negocio || 'Tu negocio'"></span></div>
                <div class="bd"><span :style="'background:' + d.color_secundario">Hacer pedido</span><small style="color:var(--fg3);">Así se verá tu marca</small></div>
            </div>
        </div>

        {{-- Paso 4: modulos --}}
        <div x-show="paso === 3" x-cloak class="ob-anim">
            <h2>¿Qué vas a usar?</h2>
            <p>Marcamos lo recomendado para tu rubro. Lo que no marques se oculta del menú (puedes activarlo luego).</p>
            <div class="mods">
                <template x-for="(nombre, clave) in modulos" :key="clave">
                    <label class="mod" :class="{ on: d.modulos.includes(clave) }">
                        <input type="checkbox" :value="clave" x-model="d.modulos"> <span x-text="nombre"></span>
                    </label>
                </template>
            </div>
            <label class="ob-ej">
                <input type="checkbox" x-model="d.ejemplos">
                <span><b>Cargar ejemplos de mi rubro</b><br><span style="color:var(--fg3);" x-text="'Categorías (' + (rubroActual ? rubroActual.categorias.join(', ') : '') + ')' + (rubroActual && rubroActual.servicios.length ? ', servicios' : '') + ' y textos para tu página. No se borra nada de lo que ya tengas.'"></span></span>
            </label>
        </div>

        <div class="ob-ft">
            <button type="button" class="btn btn-g" x-show="paso > 0" @click="paso--">Atrás</button>
            <a x-show="paso === 0" href="{{ route('admin.dashboard') }}" class="btn btn-g">Hacerlo después</a>
            <button type="button" class="btn btn-p" x-show="paso < 3" @click="siguiente()" :disabled="paso === 0 && !d.rubro">Siguiente</button>
            <button type="button" class="btn btn-p" x-show="paso === 3" x-cloak @click="guardar()" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Terminar'"></button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.ghBienvenida = window.ghBienvenida || function (cfg) {
        return {
            rubros: cfg.rubros, modulos: cfg.modulos, d: cfg.datos, paso: 0, err: {}, guardando: false,
            logo: null, logoPrev: cfg.datos.logo_url || null,
            titulos: ['Rubro', 'Datos', 'Logo y colores', 'Módulos'],
            get rubroActual() { return this.rubros.find(r => r.clave === this.d.rubro); },
            e(c) { return this.err[c] ? this.err[c][0] : ''; },
            elegirRubro(r) {
                const cambio = this.d.rubro !== r.clave;
                this.d.rubro = r.clave;
                if (cambio) { this.d.modulos = [...r.modulos]; this.d.color_primario = r.color; this.d.color_secundario = r.color2; }
                setTimeout(() => { this.paso = 1; }, 180);
            },
            elegirLogo(ev) {
                const f = ev.target.files[0]; if (!f) return;
                if (f.size > 4 * 1024 * 1024) { this.err = { logo: ['El logo puede pesar hasta 4 MB.'] }; return; }
                this.logo = f; this.logoPrev = URL.createObjectURL(f); this.err = {};
            },
            siguiente() {
                this.err = {};
                if (this.paso === 1) {
                    if (!this.d.nombre_negocio.trim()) this.err.nombre_negocio = ['Escribe el nombre de tu negocio.'];
                    if (this.d.ruc && !/^\d{11}$/.test(this.d.ruc)) this.err.ruc = ['El RUC tiene 11 dígitos.'];
                    if (Object.keys(this.err).length) return;
                }
                this.paso++;
            },
            async guardar() {
                const fd = new FormData();
                ['rubro', 'nombre_negocio', 'telefono', 'direccion', 'ruc', 'color_primario', 'color_secundario'].forEach(k => fd.append(k, this.d[k] || ''));
                this.d.modulos.forEach(m => fd.append('modulos[]', m));
                fd.append('ejemplos', this.d.ejemplos ? 1 : 0);
                if (this.logo) fd.append('logo', this.logo);
                this.guardando = true;
                const r = await gh.api(cfg.urls.guardar, { method: 'POST', body: fd });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); setTimeout(() => { location.href = r.data.redirect; }, 600); return; }
                if (r.status === 422 && r.data.errors) {
                    this.err = r.data.errors;
                    const k = Object.keys(this.err)[0];
                    this.paso = k === 'rubro' ? 0 : (['logo', 'color_primario', 'color_secundario'].includes(k) ? 2 : (k.startsWith('modulos') ? 3 : 1));
                } else Alpine.store('toasts').push(r.data.message || 'No se pudo guardar.', 'error');
            },
        };
    };
</script>
@endpush
@endsection
