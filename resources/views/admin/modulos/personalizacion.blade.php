@extends('layouts.app')

@section('titulo', 'Diseño y textos de mi web')
@section('subtitulo', 'Plantilla, logo, colores, textos y beneficios en un solo lugar, con vista previa en vivo')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .pz { display:grid; grid-template-columns: 400px 1fr; gap:16px; align-items:start; }
        .pz-panel { padding:18px; }
        .pz-panel h3 { font-size:14px; margin:0 0 10px; text-transform:uppercase; letter-spacing:.05em; color:var(--fg3); }
        .pz-sec + .pz-sec { border-top:1px solid var(--bd); margin-top:16px; padding-top:16px; }
        .pz-benef + .pz-benef { margin-top:4px; padding-top:10px; border-top:1px dashed var(--bd); }
        .pl-cards { display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; }
        .pl-card { border:2px solid var(--bd); border-radius:12px; padding:0; overflow:hidden; cursor:pointer; background:var(--card, #fff); text-align:left; font:inherit; transition: border-color .2s, transform .2s; }
        .pl-card:hover { transform: translateY(-2px); }
        .pl-card.on { border-color:var(--accent); }
        .pl-card .mini { height:62px; position:relative; }
        .pl-card .mini i { position:absolute; left:8px; right:8px; height:6px; border-radius:3px; background:rgba(255,255,255,.85); }
        .pl-card b { display:block; font-size:12px; padding:6px 8px 0; }
        .pl-card small { display:block; font-size:10px; color:var(--fg3); padding:0 8px 8px; }
        .pl-acc { display:flex; gap:8px; margin-top:10px; align-items:center; flex-wrap:wrap; }
        .colores { display:flex; gap:14px; flex-wrap:wrap; }
        .colores label { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; }
        .colores input[type=color] { width:44px; height:36px; padding:2px; border:1px solid var(--bd); border-radius:8px; background:none; }
        .archivo { display:flex; align-items:center; gap:10px; }
        .archivo .prev { width:52px; height:52px; border-radius:12px; border:1px dashed var(--bd); display:grid; place-items:center; overflow:hidden; background:var(--bg); font-size:10px; color:var(--fg3); }
        .archivo .prev img { width:100%; height:100%; object-fit:contain; }
        .pz-vista { position:sticky; top:12px; }
        .pz-vista .barra { display:flex; align-items:center; gap:8px; padding:10px 12px; border-bottom:1px solid var(--bd); }
        .pz-vista .barra .pts { display:flex; gap:5px; } .pz-vista .barra .pts i { width:10px; height:10px; border-radius:50%; background:var(--bd); }
        .pz-vista .barra .url { flex:1; font-size:12px; color:var(--fg3); background:var(--bg); border-radius:8px; padding:5px 10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .pz-vista iframe { width:100%; height:calc(100vh - 200px); min-height:520px; border:0; display:block; background:#fff; transition: opacity .2s; }
        .pz-vista iframe.cargando { opacity:.5; }
        .disp { display:flex; gap:4px; } .disp button { border:1px solid var(--bd); background:none; border-radius:8px; padding:4px 8px; font:inherit; font-size:12px; cursor:pointer; } .disp button.on { background:var(--acl); border-color:var(--accent); }
        .pz-vista.movil iframe { width:390px; margin:0 auto; border-left:1px solid var(--bd); border-right:1px solid var(--bd); }
        .pz-guardar { position:sticky; bottom:0; background:var(--card, #fff); padding-top:12px; margin-top:16px; display:flex; gap:8px; }
        @media (max-width: 1100px) { .pz { grid-template-columns:1fr; } .pz-vista { position:static; } }
    </style>
@endpush

@section('contenido')
<div class="pz" x-data="ghPersonalizar(@js($cfg))" x-init="iniciar()">
    <div class="panel pz-panel fade-in">
        {{-- GEST-83: plantillas por rubro --}}
        <div class="pz-sec">
            <h3>Plantilla</h3>
            <div class="field">
                <label class="lbl" for="pz-rubro">Rubro</label>
                <select class="inp" id="pz-rubro" x-model="rubro" @change="previsualizar()">
                    <template x-for="r in rubros" :key="r.clave"><option :value="r.clave" x-text="r.icono + ' ' + r.nombre" :selected="r.clave === rubro"></option></template>
                </select>
            </div>
            <div class="pl-cards">
                <template x-for="p in plantillas[rubro]" :key="p.clave">
                    <button type="button" class="pl-card" :class="{ on: probando === p.clave }" @click="probar(p)" :title="p.descripcion">
                        <div class="mini" :style="p.clave === 'moderna' ? 'background:linear-gradient(135deg,' + p.color_primario + ',' + p.color_secundario + ')' : (p.clave === 'minimal' ? 'background:#fff;border-bottom:2px solid ' + p.color_primario : 'background:' + p.color_primario)">
                            <i :style="'top:16px;width:' + (p.clave === 'clasica' ? '70%;left:15%' : '55%') + (p.clave === 'minimal' ? ';background:' + p.color_primario : '')"></i>
                            <i :style="'top:28px;width:40%;' + (p.clave === 'clasica' ? 'left:30%;' : '') + 'opacity:.6' + (p.clave === 'minimal' ? ';background:#94A3B8' : '')"></i>
                            <i :style="'top:42px;width:22%;height:10px;background:' + p.color_secundario + (p.clave === 'clasica' ? ';left:39%' : '')"></i>
                        </div>
                        <b x-text="p.nombre"></b><small x-text="p.fuente"></small>
                    </button>
                </template>
            </div>
            <div class="pl-acc" x-show="probando && (probando !== d.plantilla || rubro !== rubroGuardado)" x-cloak>
                <span style="font-size:12px;color:var(--fg3);flex:1;">Estás viendo la plantilla a la derecha. ¿Te gusta?</span>
                <button type="button" class="btn btn-p btn-sm" @click="aplicar()" :disabled="aplicando" x-text="aplicando ? 'Aplicando…' : 'Aplicar plantilla'"></button>
            </div>
            <div style="font-size:12px;color:var(--fg3);margin-top:8px;">Aplicar una plantilla cambia el diseño y los colores. Tus textos, fotos y productos se mantienen.</div>
        </div>

        {{-- GEST-84: personalizar --}}
        <div class="pz-sec">
            <h3>Marca</h3>
            <div class="archivo" style="margin-bottom:12px;">
                <div class="prev"><template x-if="logoPrev"><img :src="logoPrev" alt="Logo"></template><span x-show="!logoPrev">Logo</span></div>
                <label class="btn btn-g btn-sm" for="pz-logo">Cambiar logo</label>
                <input id="pz-logo" type="file" accept="image/png,image/jpeg,image/webp" hidden @change="archivo($event, 'logo')">
                <button type="button" class="btn btn-g btn-sm" x-show="logoPrev" @click="logo = null; logoPrev = null; quitarLogo = true">Quitar</button>
            </div>
            <div class="archivo" style="margin-bottom:12px;">
                <div class="prev" style="width:36px;height:36px;"><template x-if="faviconPrev"><img :src="faviconPrev" alt="Ícono"></template><span x-show="!faviconPrev">ico</span></div>
                <label class="btn btn-g btn-sm" for="pz-fav">Ícono de la pestaña (favicon)</label>
                <input id="pz-fav" type="file" accept="image/png,image/jpeg,image/webp" hidden @change="archivo($event, 'favicon')">
            </div>
            <div class="colores">
                <label><input type="color" x-model="d.color_primario" @input.debounce.250ms="previsualizar()"> Principal</label>
                <label><input type="color" x-model="d.color_secundario" @input.debounce.250ms="previsualizar()"> Botones</label>
            </div>
            <div class="field" style="margin-top:12px;">
                <label class="lbl" for="pz-fuente">Tipografía</label>
                <select class="inp" id="pz-fuente" x-model="d.fuente" @change="previsualizar()">
                    <template x-for="f in fuentes" :key="f"><option :value="f" x-text="f" :selected="f === d.fuente"></option></template>
                </select>
            </div>
        </div>

        <div class="pz-sec">
            <h3>Textos</h3>
            <div class="field"><label class="lbl" for="pz-titulo">Título de la portada *</label><input class="inp" id="pz-titulo" maxlength="120" x-model="d.titulo" @input.debounce.500ms="previsualizar()" :class="{ err: err.titulo }"><div class="ferr" x-text="e('titulo')"></div></div>
            <div class="field"><label class="lbl" for="pz-sub">Frase debajo del título *</label><textarea class="inp" id="pz-sub" rows="2" maxlength="255" x-model="d.subtitulo" @input.debounce.500ms="previsualizar()" :class="{ err: err.subtitulo }"></textarea><div class="ferr" x-text="e('subtitulo')"></div></div>
            <div class="field"><label class="lbl" for="pz-boton">Texto del botón *</label><input class="inp" id="pz-boton" maxlength="40" x-model="d.boton" @input.debounce.500ms="previsualizar()" :class="{ err: err.boton }"><div class="ferr" x-text="e('boton')"></div></div>
            <div class="field"><label class="lbl" for="pz-pie">Texto del pie de página</label><input class="inp" id="pz-pie" maxlength="200" x-model="d.pie_texto" @input.debounce.500ms="previsualizar()" placeholder="© 2026 Mi negocio. Todos los derechos reservados."></div>
        </div>

        <div class="pz-sec">
            <h3>Beneficios</h3>
            <p style="font-size:12px;color:var(--fg3);margin:-4px 0 10px;">Las 3 cajitas que salen debajo de la portada de tu web.</p>
            <template x-for="(b, i) in d.beneficios" :key="i">
                <div class="pz-benef">
                    <div class="field"><label class="lbl" :for="'pz-bt' + i" x-text="'Beneficio ' + (i + 1)"></label>
                        <input class="inp" :id="'pz-bt' + i" maxlength="80" x-model="b.titulo" @input.debounce.500ms="previsualizar()" placeholder="Ej: Atención rápida" :class="{ err: err['beneficios.' + i + '.titulo'] }">
                        <div class="ferr" x-text="e('beneficios.' + i + '.titulo')"></div></div>
                    <div class="field"><textarea class="inp" rows="2" maxlength="200" x-model="b.texto" @input.debounce.500ms="previsualizar()" placeholder="Una frase corta que lo explique" :class="{ err: err['beneficios.' + i + '.texto'] }" :aria-label="'Texto del beneficio ' + (i + 1)"></textarea>
                        <div class="ferr" x-text="e('beneficios.' + i + '.texto')"></div></div>
                </div>
            </template>
        </div>

        <div class="pz-guardar">
            <button type="button" class="btn btn-p" style="flex:1;" @click="guardar()" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Guardar diseño'"></button>
        </div>
    </div>

    <div class="panel pz-vista fade-in" :class="{ movil }" style="padding:0;overflow:hidden;">
        <div class="barra">
            <div class="pts"><i></i><i></i><i></i></div>
            <div class="url" x-text="urls.publica || 'Vista previa'"></div>
            <div class="disp"><button type="button" :class="{ on: !movil }" @click="movil = false">Escritorio</button><button type="button" :class="{ on: movil }" @click="movil = true">Celular</button></div>
            <a class="btn btn-g btn-sm" x-show="urls.publica" :href="urls.publica" target="_blank" rel="noopener">Abrir web</a>
        </div>
        <iframe x-ref="vista" title="Vista previa de tu web" :class="{ cargando }" @load="cargando = false"></iframe>
    </div>
</div>

@push('scripts')
<script>
    window.ghPersonalizar = function (cfg) {
        return {
            rubro: cfg.rubro, rubroGuardado: cfg.rubro, rubros: cfg.rubros, plantillas: cfg.plantillas, fuentes: cfg.fuentes,
            d: cfg.d, urls: cfg.urls, probando: cfg.d.plantilla, err: {}, guardando: false, aplicando: false, cargando: false, movil: false,
            logo: null, logoPrev: cfg.d.logo_url, favicon: null, faviconPrev: cfg.d.favicon_url, quitarLogo: false,
            e(c) { return this.err[c] ? this.err[c][0] : ''; },
            iniciar() { this.previsualizar(); },
            previsualizar(extra = {}) {
                const q = new URLSearchParams({
                    rubro: this.rubro, plantilla: this.probando || this.d.plantilla,
                    color_primario: this.d.color_primario, color_secundario: this.d.color_secundario, fuente: this.d.fuente,
                    titulo: this.d.titulo, subtitulo: this.d.subtitulo, boton: this.d.boton, pie_texto: this.d.pie_texto || '',
                    beneficios: JSON.stringify(this.d.beneficios || []), ...extra,
                });
                if (this.rubro !== this.rubroGuardado) { q.delete('titulo'); q.delete('subtitulo'); q.delete('boton'); q.delete('beneficios'); }
                this.cargando = true;
                this.$refs.vista.src = this.urls.vista + '?' + q.toString();
            },
            probar(p) {
                this.probando = p.clave;
                this.previsualizar({ color_primario: p.color_primario, color_secundario: p.color_secundario, fuente: p.fuente });
            },
            async aplicar() {
                this.aplicando = true;
                const r = await gh.api(this.urls.aplicar, { method: 'POST', body: { rubro: this.rubro, plantilla: this.probando } });
                this.aplicando = false;
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo aplicar.', 'error'); return; }
                Object.assign(this.d, r.data.d); this.rubroGuardado = this.rubro;
                Alpine.store('toasts').push(r.data.message);
                this.previsualizar();
            },
            archivo(ev, tipo) {
                const f = ev.target.files[0]; if (!f) return;
                const max = tipo === 'logo' ? 4 : 1;
                if (f.size > max * 1024 * 1024) { Alpine.store('toasts').push('El archivo pesa más de ' + max + ' MB.', 'error'); return; }
                this[tipo] = f; this[tipo + 'Prev'] = URL.createObjectURL(f);
                if (tipo === 'logo') this.quitarLogo = false;
            },
            async guardar() {
                const fd = new FormData();
                ['color_primario', 'color_secundario', 'fuente', 'titulo', 'subtitulo', 'boton', 'pie_texto'].forEach(k => fd.append(k, this.d[k] || ''));
                fd.append('plantilla', this.d.plantilla);
                (this.d.beneficios || []).forEach((b, i) => { fd.append('beneficios[' + i + '][titulo]', b.titulo || ''); fd.append('beneficios[' + i + '][texto]', b.texto || ''); });
                if (this.logo) fd.append('logo', this.logo);
                if (this.favicon) fd.append('favicon', this.favicon);
                if (this.quitarLogo) fd.append('quitar_logo', 1);
                this.guardando = true; this.err = {};
                const r = await gh.api(this.urls.guardar, { method: 'POST', body: fd });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.logo = null; this.favicon = null; this.previsualizar(); }
                else if (r.status === 422) { this.err = r.data.errors || {}; Alpine.store('toasts').push(Object.values(this.err)[0][0], 'error'); }
                else Alpine.store('toasts').push(r.data.message || 'No se pudo guardar.', 'error');
            },
        };
    };
</script>
@endpush
@endsection
