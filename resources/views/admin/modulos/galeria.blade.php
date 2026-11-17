@extends('layouts.app')

@section('titulo', 'Galería de trabajos')
@section('subtitulo', 'Sube tus fotos y muéstralas en tu página web')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .gal-drop { border:2px dashed var(--bd); border-radius:14px; padding:26px 16px; text-align:center; cursor:pointer; transition: border-color .2s, background .2s, transform .2s; }
        .gal-drop:hover, .gal-drop.on { border-color:var(--accent); background:var(--acl); }
        .gal-drop.on { transform: scale(1.01); }
        .gal-drop svg { display:block; margin:0 auto; color:var(--accent); }
        .gal-drop b { display:block; font-size:15px; margin:8px 0 2px; }
        .gal-top { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; margin-top:12px; }
        .gal-top .field { margin:0; min-width:180px; }
        .gal-cats { display:flex; gap:6px; flex-wrap:wrap; margin:14px 0 10px; }
        .gal-cats button { border:1px solid var(--bd); background:var(--card, #fff); border-radius:999px; padding:5px 12px; font:inherit; font-size:13px; cursor:pointer; }
        .gal-cats button.on { background:var(--accent); color:#fff; border-color:var(--accent); }
        .gal-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(190px, 1fr)); gap:12px; }
        .gal-item { position:relative; border:1px solid var(--bd); border-radius:12px; overflow:hidden; background:var(--card, #fff); transition: transform .2s, box-shadow .2s, opacity .2s; cursor:grab; }
        .gal-item:hover { transform: translateY(-2px); box-shadow:0 10px 24px rgba(15,23,42,.10); }
        .gal-item.oculto img { filter: grayscale(1); opacity:.5; }
        .gal-item.arrastrando { opacity:.35; }
        .gal-item.sobre { outline:2px solid var(--accent); outline-offset:2px; }
        .gal-item img { width:100%; height:150px; object-fit:cover; display:block; background:var(--bg); pointer-events:none; }
        .gal-info { padding:8px 10px; }
        .gal-info b { display:block; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .gal-info small { color:var(--fg3); font-size:11px; }
        .gal-acc { display:flex; gap:4px; padding:0 8px 8px; }
        .gal-acc .btn { flex:1; padding:5px 6px; font-size:12px; }
        .gal-badge { position:absolute; top:8px; left:8px; font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px; background:rgba(15,23,42,.75); color:#fff; }
        .gal-uso { height:6px; border-radius:99px; background:var(--bg); overflow:hidden; margin-top:6px; width:180px; }
        .gal-uso i { display:block; height:100%; background:var(--accent); }
        .spin { width:16px; height:16px; border:2px solid var(--acl); border-top-color:var(--accent); border-radius:50%; animation: gal-giro .7s linear infinite; }
        @keyframes gal-giro { to { transform: rotate(360deg); } }
        .gal-subiendo { display:flex; align-items:center; gap:10px; margin-top:10px; font-size:13px; color:var(--fg2); }
    </style>
@endpush

@section('contenido')
<div x-data="ghGaleria(@js(['items' => $items, 'limite' => $limite, 'urls' => [
        'guardar' => route('admin.galeria.guardar'), 'ordenar' => route('admin.galeria.ordenar'), 'base' => url('admin/galeria'),
    ]]))" @keydown.escape.window="editando = null">

    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Fotos</div><div class="stat-val" x-text="items.length + ' / ' + limite"></div>
            <div class="gal-uso"><i :style="'width:' + Math.min(100, items.length / limite * 100) + '%'"></i></div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Publicadas</div><div class="stat-val" x-text="items.filter(i => i.activo).length"></div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Categorías</div><div class="stat-val" x-text="categorias.length"></div></div>
    </div>

    <div class="panel fade-in" style="padding:16px;margin-bottom:14px;">
        <label class="gal-drop" :class="{ on: sobreZona }" for="gal-fotos"
               @dragover.prevent="sobreZona = true" @dragleave.prevent="sobreZona = false" @drop.prevent="sobreZona = false; subir($event.dataTransfer.files)">
            <x-icon name="image" size="30" />
            <b>Arrastra tus fotos aquí o haz clic para elegirlas</b>
            <span style="font-size:12px;color:var(--fg3);">JPG, PNG o WEBP, hasta 8 MB cada una. Se optimizan automáticamente.</span>
            <input id="gal-fotos" type="file" accept="image/jpeg,image/png,image/webp" multiple hidden @change="subir($event.target.files); $event.target.value = ''">
        </label>
        <div class="gal-top">
            <div class="field">
                <label class="lbl" for="gal-cat">Categoría para las nuevas fotos</label>
                <input class="inp" id="gal-cat" list="gal-cats-list" maxlength="60" x-model="categoriaNueva" placeholder="Ej.: Cortes, Tortas, Eventos">
                <datalist id="gal-cats-list"><template x-for="c in categorias" :key="c"><option :value="c"></option></template></datalist>
            </div>
            <span style="font-size:12px;color:var(--fg3);">Arrastra las fotos de abajo para cambiar el orden en tu web.</span>
            @if ($slug)
                <a class="btn btn-g btn-sm" style="margin-left:auto;" href="{{ route('publico.galeria', $slug) }}" target="_blank" rel="noopener">Ver galería pública</a>
            @endif
        </div>
        <div class="gal-subiendo" x-show="subiendo" x-cloak><span class="spin"></span> Subiendo y optimizando <span x-text="subiendo"></span> foto(s)…</div>
        <div class="ferr" x-text="error"></div>
    </div>

    <div class="gal-cats" x-show="categorias.length" x-cloak>
        <button type="button" :class="{ on: filtro === '' }" @click="filtro = ''">Todas</button>
        <template x-for="c in categorias" :key="c"><button type="button" :class="{ on: filtro === c }" @click="filtro = c" x-text="c"></button></template>
    </div>

    <div class="gal-grid" x-show="visibles.length" x-cloak>
        <template x-for="it in visibles" :key="it.id">
            <div class="gal-item fade-in" :class="{ oculto: !it.activo, arrastrando: arrastrado === it.id, sobre: sobre === it.id && arrastrado !== it.id }"
                 draggable="true" @dragstart="arrastrado = it.id" @dragend="arrastrado = null; sobre = null"
                 @dragover.prevent="sobre = it.id" @drop.prevent="soltar(it.id)">
                <span class="gal-badge" x-show="!it.activo">Oculta</span>
                <img :src="it.miniatura_url" :alt="it.titulo" loading="lazy">
                <div class="gal-info"><b x-text="it.titulo"></b><small x-text="it.categoria || 'Sin categoría'"></small></div>
                <div class="gal-acc">
                    <button type="button" class="btn btn-g" @click="editar(it)">Editar</button>
                    <button type="button" class="btn btn-g" @click="alternar(it)" x-text="it.activo ? 'Ocultar' : 'Publicar'"></button>
                    <button type="button" class="btn btn-g" @click="eliminar(it)" aria-label="Eliminar foto"><x-icon name="x" size="13" /></button>
                </div>
            </div>
        </template>
    </div>
    <div class="panel empty" x-show="!visibles.length" x-cloak>
        <x-icon name="image" size="34" />
        <div style="font-weight:600;color:var(--fg2);" x-text="items.length ? 'No hay fotos en esta categoría' : 'Aún no hay fotos en tu galería'"></div>
        <div style="font-size:13px;color:var(--fg3);">Sube fotos de tus trabajos: aparecen en tu página web con filtro por categoría.</div>
    </div>

    {{-- Editar foto --}}
    <div class="modal-bg" x-show="editando" x-cloak x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from" @mousedown.self="editando = null">
        <form class="modal" @submit.prevent="guardarEdicion()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="image" size="18" /></div>
                <div><div class="modal-ttl">Editar foto</div><div class="modal-sub">Así se verá en tu web</div></div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="editando = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <img :src="editando?.miniatura_url" alt="" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;margin-bottom:12px;">
                <div class="field"><label class="lbl" for="ge-titulo">Título *</label><input class="inp" id="ge-titulo" maxlength="120" x-model="form.titulo" :class="{ err: errores.titulo }"><div class="ferr" x-text="errores.titulo ? errores.titulo[0] : ''"></div></div>
                <div class="field"><label class="lbl" for="ge-cat">Categoría</label><input class="inp" id="ge-cat" list="gal-cats-list" maxlength="60" x-model="form.categoria"></div>
                <div class="field"><label class="lbl" for="ge-desc">Descripción</label><input class="inp" id="ge-desc" maxlength="255" x-model="form.descripcion"></div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="editando = null">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Guardar'"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.ghGaleria = window.ghGaleria || function (cfg) {
        return {
            items: cfg.items, limite: cfg.limite, filtro: '', categoriaNueva: '', subiendo: 0, error: '',
            sobreZona: false, arrastrado: null, sobre: null, editando: null, form: {}, errores: {}, guardando: false,

            get categorias() { return [...new Set(this.items.map(i => i.categoria).filter(Boolean))].sort(); },
            get visibles() { return this.filtro ? this.items.filter(i => i.categoria === this.filtro) : this.items; },

            async subir(lista) {
                const fotos = [...(lista || [])].filter(f => /^image\/(jpeg|png|webp)$/.test(f.type));
                this.error = '';
                if (!fotos.length) { this.error = 'Elige fotos JPG, PNG o WEBP.'; return; }
                if (this.items.length + fotos.length > this.limite) { this.error = 'Tu plan permite ' + this.limite + ' fotos. Te quedan ' + Math.max(0, this.limite - this.items.length) + '.'; return; }
                const grande = fotos.find(f => f.size > 8 * 1024 * 1024);
                if (grande) { this.error = '«' + grande.name + '» pesa más de 8 MB.'; return; }
                // De a 5 para no superar el límite de subida del servidor
                for (let i = 0; i < fotos.length; i += 5) {
                    const fd = new FormData();
                    fotos.slice(i, i + 5).forEach(f => fd.append('fotos[]', f));
                    if (this.categoriaNueva.trim()) fd.append('categoria', this.categoriaNueva.trim());
                    this.subiendo = fotos.slice(i, i + 5).length;
                    const r = await gh.api(cfg.urls.guardar, { method: 'POST', body: fd });
                    this.subiendo = 0;
                    if (!r.ok) { this.error = (r.data.errors ? Object.values(r.data.errors)[0][0] : r.data.message) || 'No se pudo subir.'; return; }
                    this.items.push(...r.data.items);
                    Alpine.store('toasts').push(r.data.message);
                }
            },
            async soltar(destino) {
                const origen = this.arrastrado; this.arrastrado = null; this.sobre = null;
                if (!origen || origen === destino) return;
                const a = this.items.findIndex(i => i.id === origen), b = this.items.findIndex(i => i.id === destino);
                const [mov] = this.items.splice(a, 1); this.items.splice(b, 0, mov);
                const r = await gh.api(cfg.urls.ordenar, { method: 'POST', body: { ids: this.items.map(i => i.id) } });
                Alpine.store('toasts').push(r.ok ? 'Orden guardado.' : 'No se pudo guardar el orden.', r.ok ? 'ok' : 'error');
            },
            editar(it) { this.editando = it; this.errores = {}; this.form = { titulo: it.titulo, categoria: it.categoria || '', descripcion: it.descripcion || '' }; this.$nextTick(() => document.getElementById('ge-titulo')?.focus()); },
            async guardarEdicion() {
                this.guardando = true;
                const r = await gh.api(cfg.urls.base + '/' + this.editando.id, { method: 'PUT', body: this.form });
                this.guardando = false;
                if (r.ok) { Object.assign(this.editando, r.data.item); this.editando = null; Alpine.store('toasts').push(r.data.message); }
                else if (r.status === 422) this.errores = r.data.errors || {};
                else Alpine.store('toasts').push(r.data.message || 'No se pudo guardar.', 'error');
            },
            async alternar(it) {
                const r = await gh.api(cfg.urls.base + '/' + it.id + '/alternar', { method: 'POST' });
                if (r.ok) { it.activo = r.data.activo; Alpine.store('toasts').push(r.data.message); }
            },
            async eliminar(it) {
                if (!confirm('¿Eliminar «' + it.titulo + '»? Se borra también de tu web.')) return;
                const r = await gh.api(cfg.urls.base + '/' + it.id, { method: 'DELETE' });
                if (r.ok) { this.items = this.items.filter(i => i.id !== it.id); Alpine.store('toasts').push(r.data.message); }
            },
        };
    };
</script>
@endpush
@endsection
