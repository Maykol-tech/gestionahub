@extends('layouts.app')

@section('titulo', 'Editor de la landing')
@section('subtitulo', 'Cambia textos, secciones, preguntas, testimonios, logos y blog sin tocar código')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .ed-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; }
        .ed-item { border:1px solid var(--border); border-radius:12px; padding:12px; margin-bottom:10px; background:#fff; transition:box-shadow .2s, transform .2s, opacity .2s; animation:subirE .25s ease both; }
        .ed-item:hover { box-shadow:0 6px 16px rgba(15,23,42,.06); }
        .ed-item.oculto { opacity:.55; }
        .ed-item.arr { opacity:.4; }
        .ed-bar { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
        .ed-bar .asa { cursor:grab; color:var(--fg3); font-size:16px; user-select:none; }
        .ed-bar .t { font-weight:700; font-size:13px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ed-mini { border:1px solid var(--border); background:#fff; border-radius:8px; padding:4px 8px; font:inherit; font-size:12px; cursor:pointer; }
        .ed-mini:hover { background:var(--surface2); }
        .ed-mini.rojo { color:#B91C1C; }
        .sw { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:var(--fg2); cursor:pointer; }
        .sw input { width:auto; }
        .ed-pie { display:flex; gap:8px; align-items:center; justify-content:space-between; padding:12px 16px; border-top:1px solid var(--border); flex-wrap:wrap; }
        .blog-prev { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-top:12px; }
        .blog-prev a { border:1px solid var(--border); border-radius:10px; padding:10px; font-size:13px; text-decoration:none; color:var(--fg); }
        @keyframes subirE { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }
    </style>
@endpush

@section('contenido')
<div x-data="editorLanding(@js($cms), @js(route('superadmin.landing.cms')))">
    @if ($ok)<div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#15803D;font-weight:600;">{{ $ok }}</div>@endif
    @if ($errors->any())<div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#B91C1C;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="nota fade-in">
        <x-icon name="globe" size="16" />
        <div>Los cambios se ven al instante en <a href="{{ url('/landing') }}" target="_blank" style="font-weight:700;">tu landing</a>.
            @if ($leadsNuevos) Tienes <a href="{{ route('superadmin.leads') }}" style="font-weight:700;">{{ $leadsNuevos }} {{ $leadsNuevos === 1 ? 'lead sin atender' : 'leads sin atender' }}</a>.@endif
        </div>
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="tabs">
            @foreach (['textos' => 'Textos y portada', 'secciones' => 'Secciones', 'faqs' => 'Preguntas', 'testimonios' => 'Testimonios', 'logos' => 'Logos', 'blog' => 'Blog', 'seo' => 'SEO y analítica'] as $k => $v)
                <button type="button" class="tab" :class="{ on: tab === '{{ $k }}' }" @click="tab = '{{ $k }}'">{{ $v }}</button>
            @endforeach
        </div>

        {{-- TEXTOS --}}
        <form x-show="tab === 'textos'" method="POST" action="{{ route('superadmin.landing.guardar') }}" style="padding:16px;">
            @csrf
            <div class="sec-ttl">Portada</div>
            <label class="lbl" for="hero_titulo">Título</label>
            <input class="inp" id="hero_titulo" name="hero_titulo" maxlength="160" value="{{ old('hero_titulo', $c['hero']['titulo']) }}" required>
            <label class="lbl" for="hero_subtitulo">Subtítulo</label>
            <textarea class="inp" id="hero_subtitulo" name="hero_subtitulo" rows="2" maxlength="400" required>{{ old('hero_subtitulo', $c['hero']['subtitulo']) }}</textarea>
            <label class="lbl" for="hero_cta">Texto del botón</label>
            <input class="inp" id="hero_cta" name="hero_cta" maxlength="60" value="{{ old('hero_cta', $c['hero']['cta']) }}" required>

            <div class="sec-ttl" style="margin-top:18px;">Beneficios principales</div>
            <div class="ed-grid">
                @foreach ($c['beneficios']['items'] as $i => $b)
                    <div class="ed-item">
                        <label class="lbl">Título {{ $i + 1 }}</label>
                        <input class="inp" name="beneficio[{{ $i }}][titulo]" maxlength="80" value="{{ $b['titulo'] }}" required>
                        <label class="lbl">Texto {{ $i + 1 }}</label>
                        <textarea class="inp" name="beneficio[{{ $i }}][texto]" rows="3" maxlength="250" required>{{ $b['texto'] }}</textarea>
                    </div>
                @endforeach
            </div>

            <div class="sec-ttl" style="margin-top:18px;">Pie de página</div>
            <div class="g-11">
                <div><label class="lbl" for="pie_texto">Texto del pie</label><input class="inp" id="pie_texto" name="pie_texto" maxlength="200" value="{{ $c['pie']['texto'] }}" required></div>
                <div><label class="lbl" for="pie_email">Correo de contacto</label><input class="inp" id="pie_email" name="pie_email" type="email" maxlength="150" value="{{ $c['pie']['email'] }}"></div>
            </div>

            {{-- GEST-101: cifras y datos reales de la empresa --}}
            <div class="sec-ttl" style="margin-top:18px;">Cifras de la portada</div>
            <label class="sw"><input type="checkbox" name="cifras_mostrar" value="1" @checked($c['cifras']['mostrar'] ?? false)> Mostrar las cifras en la landing (actívalo solo con datos reales)</label>
            <div class="ed-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-top:8px;">
                @foreach ($c['cifras']['items'] as $i => $cf)
                    <div class="ed-item">
                        <label class="lbl">Número {{ $i + 1 }}</label>
                        <div style="display:flex;gap:6px;"><input class="inp" type="number" min="0" name="cifra[{{ $i }}][valor]" value="{{ $cf['valor'] }}"><input class="inp" style="max-width:80px;" name="cifra[{{ $i }}][sufijo]" maxlength="8" value="{{ $cf['sufijo'] }}" placeholder="+ % k+"></div>
                        <label class="lbl">Texto</label>
                        <input class="inp" name="cifra[{{ $i }}][texto]" maxlength="60" value="{{ $cf['texto'] }}">
                    </div>
                @endforeach
            </div>

            <div class="sec-ttl" style="margin-top:18px;">Datos de la empresa</div>
            <div class="g-11">
                <div><label class="lbl" for="razon_social">Razón social</label><input class="inp" id="razon_social" name="razon_social" maxlength="150" value="{{ old('razon_social', $c['empresa']['razon_social']) }}" placeholder="Intelecta Lab S.A.C."></div>
                <div><label class="lbl" for="ruc">RUC</label><input class="inp" id="ruc" name="ruc" inputmode="numeric" maxlength="11" value="{{ old('ruc', $c['empresa']['ruc']) }}" placeholder="11 dígitos"></div>
                <div><label class="lbl" for="whatsapp">WhatsApp de ventas</label><input class="inp" id="whatsapp" name="whatsapp" maxlength="16" value="{{ old('whatsapp', $c['empresa']['whatsapp']) }}" placeholder="51987654321"></div>
                <div><label class="lbl" for="video_url">Video de demostración (enlace de YouTube)</label><input class="inp" id="video_url" name="video_url" type="url" maxlength="255" value="{{ old('video_url', $c['empresa']['video_url']) }}" placeholder="https://youtube.com/…"></div>
                <div><label class="lbl" for="terminos_url">Enlace a los términos y condiciones</label><input class="inp" id="terminos_url" name="terminos_url" type="url" maxlength="255" value="{{ old('terminos_url', $c['empresa']['terminos_url']) }}" placeholder="https://…"></div>
                <div><label class="lbl" for="privacidad_url">Enlace a la política de privacidad</label><input class="inp" id="privacidad_url" name="privacidad_url" type="url" maxlength="255" value="{{ old('privacidad_url', $c['empresa']['privacidad_url']) }}" placeholder="https://…"></div>
            </div>
            @foreach (['razon_social', 'ruc', 'whatsapp', 'video_url', 'terminos_url', 'privacidad_url'] as $campo)
                @error($campo)<div style="color:var(--red,#DC2626);font-size:12px;margin-top:6px;">{{ $message }}</div>@enderror
            @endforeach

            <div class="sec-ttl" style="margin-top:18px;">Popup de salida</div>
            <div class="g-11">
                <label class="sw"><input type="checkbox" name="popup_activo" value="1" @checked($c['popup']['activo'])> Mostrar popup cuando el visitante se va</label>
                <div><label class="lbl" for="popup_segundos">Segundos antes de mostrarlo</label><input class="inp" id="popup_segundos" name="popup_segundos" type="number" min="5" max="120" value="{{ $c['popup']['segundos'] }}"></div>
                <div><label class="lbl" for="popup_titulo">Título</label><input class="inp" id="popup_titulo" name="popup_titulo" maxlength="120" value="{{ $c['popup']['titulo'] }}"></div>
                <div><label class="lbl" for="popup_texto">Texto</label><input class="inp" id="popup_texto" name="popup_texto" maxlength="250" value="{{ $c['popup']['texto'] }}"></div>
                <div><label class="lbl" for="popup_oferta">Código de oferta (se guarda en el lead)</label><input class="inp" id="popup_oferta" name="popup_oferta" maxlength="40" value="{{ $c['popup']['oferta'] ?? 'POPUP50' }}"></div>
            </div>
            {{-- La analítica se edita en su pestaña; aquí se reenvía para no perderla --}}
            <input type="hidden" name="gtm_id" value="{{ $c['analitica']['gtm_id'] }}">
            @if ($c['analitica']['utm_activo'])<input type="hidden" name="utm_activo" value="1">@endif
            <button class="btn btn-p" type="submit" style="margin-top:16px;">Guardar textos</button>
        </form>

        {{-- SECCIONES --}}
        <div x-show="tab === 'secciones'" x-cloak style="padding:16px;">
            <p style="font-size:13px;color:var(--fg3);margin-top:0;">Arrastra para cambiar el orden. Apaga las que no quieras mostrar. Si dejas el título vacío se usa el de siempre.</p>
            <template x-for="(s, i) in secciones" :key="s.clave">
                <div class="ed-item" :class="{ oculto: ! s.visible, arr: arrastrando === i }" draggable="true"
                     @dragstart="arrastrando = i" @dragend="arrastrando = null" @dragover.prevent="reordenar('secciones', i)">
                    <div class="ed-bar">
                        <span class="asa" title="Arrastrar">⋮⋮</span>
                        <span class="t" x-text="s.nombre"></span>
                        <label class="sw"><input type="checkbox" x-model="s.visible"> Visible</label>
                    </div>
                    <div class="g-11">
                        <input class="inp" x-model="s.titulo" placeholder="Título (opcional)" maxlength="160">
                        <input class="inp" x-model="s.subtitulo" placeholder="Subtítulo (opcional)" maxlength="255">
                    </div>
                </div>
            </template>
            <button class="btn btn-p" type="button" @click="guardar('secciones')" :disabled="guardando">Guardar secciones</button>
        </div>

        {{-- PREGUNTAS --}}
        <div x-show="tab === 'faqs'" x-cloak style="padding:16px;">
            <template x-for="(f, i) in faqs" :key="f._k">
                <div class="ed-item" :class="{ oculto: ! f.visible, arr: arrastrando === i }" draggable="true"
                     @dragstart="arrastrando = i" @dragend="arrastrando = null" @dragover.prevent="reordenar('faqs', i)">
                    <div class="ed-bar">
                        <span class="asa">⋮⋮</span><span class="t" x-text="f.pregunta || 'Nueva pregunta'"></span>
                        <label class="sw"><input type="checkbox" x-model="f.visible"> Visible</label>
                        <button type="button" class="ed-mini rojo" @click="quitar('faqs', i)">Quitar</button>
                    </div>
                    <input class="inp" x-model="f.pregunta" placeholder="Pregunta" maxlength="255">
                    <textarea class="inp" x-model="f.respuesta" rows="3" placeholder="Respuesta" maxlength="2000" style="margin-top:6px;"></textarea>
                </div>
            </template>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-g" type="button" @click="agregar('faqs', { pregunta: '', respuesta: '' })">+ Agregar pregunta</button>
                <button class="btn btn-p" type="button" @click="guardar('faqs')" :disabled="guardando">Guardar preguntas</button>
            </div>
        </div>

        {{-- TESTIMONIOS --}}
        <div x-show="tab === 'testimonios'" x-cloak style="padding:16px;">
            <template x-for="(t, i) in testimonios" :key="t._k">
                <div class="ed-item" :class="{ oculto: ! t.visible, arr: arrastrando === i }" draggable="true"
                     @dragstart="arrastrando = i" @dragend="arrastrando = null" @dragover.prevent="reordenar('testimonios', i)">
                    <div class="ed-bar">
                        <span class="asa">⋮⋮</span><span class="t" x-text="t.nombre || 'Nuevo testimonio'"></span>
                        <select class="ed-mini" x-model.number="t.estrellas"><template x-for="n in [5,4,3]"><option :value="n" x-text="'★'.repeat(n)" :selected="t.estrellas == n"></option></template></select>
                        <label class="sw"><input type="checkbox" x-model="t.visible"> Visible</label>
                        <button type="button" class="ed-mini rojo" @click="quitar('testimonios', i)">Quitar</button>
                    </div>
                    <div class="g-11">
                        <input class="inp" x-model="t.nombre" placeholder="Nombre" maxlength="120">
                        <input class="inp" x-model="t.negocio" placeholder="Negocio · ciudad" maxlength="160">
                    </div>
                    <textarea class="inp" x-model="t.texto" rows="2" placeholder="Lo que dijo" maxlength="600" style="margin-top:6px;"></textarea>
                    <input class="inp" x-model="t.foto_url" placeholder="Foto (enlace https, opcional)" maxlength="255" style="margin-top:6px;">
                </div>
            </template>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-g" type="button" @click="agregar('testimonios', { nombre: '', negocio: '', texto: '', foto_url: '', estrellas: 5 })">+ Agregar testimonio</button>
                <button class="btn btn-p" type="button" @click="guardar('testimonios')" :disabled="guardando">Guardar testimonios</button>
            </div>
        </div>

        {{-- LOGOS --}}
        <div x-show="tab === 'logos'" x-cloak style="padding:16px;">
            <p style="font-size:13px;color:var(--fg3);margin-top:0;">Logos de negocios que usan GestionaHub. Se muestran en una franja que se mueve sola. Si no pones imagen, se muestra el nombre.</p>
            <template x-for="(l, i) in logos" :key="l._k">
                <div class="ed-item" :class="{ oculto: ! l.visible, arr: arrastrando === i }" draggable="true"
                     @dragstart="arrastrando = i" @dragend="arrastrando = null" @dragover.prevent="reordenar('logos', i)">
                    <div class="ed-bar">
                        <span class="asa">⋮⋮</span>
                        <template x-if="l.imagen_url"><img :src="l.imagen_url" alt="" style="height:24px;max-width:80px;object-fit:contain;"></template>
                        <span class="t" x-text="l.nombre || 'Nuevo logo'"></span>
                        <label class="sw"><input type="checkbox" x-model="l.visible"> Visible</label>
                        <button type="button" class="ed-mini rojo" @click="quitar('logos', i)">Quitar</button>
                    </div>
                    <div class="g-11">
                        <input class="inp" x-model="l.nombre" placeholder="Nombre del negocio" maxlength="120">
                        <input class="inp" x-model="l.imagen_url" placeholder="Imagen del logo (enlace https)" maxlength="255">
                    </div>
                    <input class="inp" x-model="l.enlace" placeholder="Enlace al negocio (opcional)" maxlength="255" style="margin-top:6px;">
                </div>
            </template>
            <template x-if="! logos.length"><div style="color:var(--fg3);font-size:13px;margin-bottom:10px;">Aún no hay logos. La franja no se muestra hasta que agregues uno.</div></template>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-g" type="button" @click="agregar('logos', { nombre: '', imagen_url: '', enlace: '' })">+ Agregar logo</button>
                <button class="btn btn-p" type="button" @click="guardar('logos')" :disabled="guardando">Guardar logos</button>
            </div>
        </div>

        {{-- BLOG --}}
        <div x-show="tab === 'blog'" x-cloak style="padding:16px;">
            <p style="font-size:13px;color:var(--fg3);margin-top:0;">Pega la dirección de tu blog WordPress y mostramos sus últimos artículos en la landing. Se actualiza solo cada hora.</p>
            <div class="g-11">
                <div><label class="lbl">Dirección del blog</label><input class="inp" x-model="blog.url" placeholder="https://blog.gestionahub.pe" maxlength="255"></div>
                <div><label class="lbl">Título de la sección</label><input class="inp" x-model="blog.titulo" maxlength="120"></div>
                <div><label class="lbl">Cantidad de artículos</label><select class="inp" x-model.number="blog.cantidad"><template x-for="n in [3,4,6]"><option :value="n" x-text="n" :selected="blog.cantidad == n"></option></template></select></div>
            </div>
            <button class="btn btn-p" type="button" style="margin-top:12px;" @click="guardarBlog()" :disabled="guardando">Guardar y probar</button>
            <div class="blog-prev">
                <template x-for="e in entradas"><a :href="e.enlace" target="_blank" rel="noopener"><small x-text="e.fecha" style="color:var(--fg3);"></small><div style="font-weight:700;" x-text="e.titulo"></div></a></template>
            </div>
        </div>

        {{-- SEO Y ANALÍTICA --}}
        <div x-show="tab === 'seo'" x-cloak style="padding:16px;">
            <div class="sec-ttl">SEO</div>
            <p style="font-size:13px;color:var(--fg2);"><strong>Título:</strong> {{ $c['seo']['titulo'] ?? '(sin generar)' }}<br><strong>Descripción:</strong> {{ $c['seo']['descripcion'] ?? '(sin generar)' }}</p>
            <form method="POST" action="{{ route('superadmin.landing.seo') }}">@csrf<button class="btn btn-g" type="submit">Generar SEO con IA</button></form>

            <div class="sec-ttl" style="margin-top:18px;">Google Tag Manager y UTM</div>
            <form method="POST" action="{{ route('superadmin.landing.guardar') }}">
                @csrf
                <input type="hidden" name="hero_titulo" value="{{ $c['hero']['titulo'] }}"><input type="hidden" name="hero_subtitulo" value="{{ $c['hero']['subtitulo'] }}"><input type="hidden" name="hero_cta" value="{{ $c['hero']['cta'] }}">
                @foreach ($c['beneficios']['items'] as $i => $b)<input type="hidden" name="beneficio[{{ $i }}][titulo]" value="{{ $b['titulo'] }}"><input type="hidden" name="beneficio[{{ $i }}][texto]" value="{{ $b['texto'] }}">@endforeach
                <input type="hidden" name="pie_texto" value="{{ $c['pie']['texto'] }}"><input type="hidden" name="pie_email" value="{{ $c['pie']['email'] }}">
                @if ($c['popup']['activo'])<input type="hidden" name="popup_activo" value="1">@endif
                <input type="hidden" name="popup_titulo" value="{{ $c['popup']['titulo'] }}"><input type="hidden" name="popup_texto" value="{{ $c['popup']['texto'] }}"><input type="hidden" name="popup_segundos" value="{{ $c['popup']['segundos'] }}"><input type="hidden" name="popup_oferta" value="{{ $c['popup']['oferta'] ?? 'POPUP50' }}">
                <div class="g-11">
                    <div><label class="lbl" for="gtm_id">ID de Google Tag Manager</label><input class="inp" id="gtm_id" name="gtm_id" maxlength="20" value="{{ $c['analitica']['gtm_id'] }}" placeholder="GTM-XXXXXX"></div>
                    <label class="sw" style="align-self:end;margin-bottom:10px;"><input type="checkbox" name="utm_activo" value="1" @checked($c['analitica']['utm_activo'])> Guardar UTM de campañas en los leads (30 días)</label>
                </div>
                <p style="font-size:12px;color:var(--fg3);">Eventos que llegan a GTM: <code>registro_inicio</code>, <code>registro_completo</code>, <code>lead_enviado</code>, <code>plan_elegido</code>, <code>popup_visto</code>, <code>blog_click</code>, <code>checkout_inicio</code> y <code>pago_reportado</code>.</p>
                <button class="btn btn-p" type="submit">Guardar analítica</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.editorLanding = window.editorLanding || function (cms, url) {
    let k = 0;
    const conClave = (lista) => (lista || []).map(x => ({ ...x, visible: x.visible === undefined ? true : !! Number(x.visible) || x.visible === true, _k: ++k }));
    return {
        tab: 'textos', guardando: false, arrastrando: null,
        secciones: (cms.secciones || []).map(s => ({ ...s, titulo: s.titulo || '', subtitulo: s.subtitulo || '' })),
        faqs: conClave(cms.faqs), testimonios: conClave(cms.testimonios), logos: conClave(cms.logos),
        blog: { url: cms.blog.url || '', titulo: cms.blog.titulo || '', cantidad: Number(cms.blog.cantidad || 3) }, entradas: [],
        agregar(tipo, fila) { this[tipo].push({ ...fila, visible: true, _k: ++k }); },
        quitar(tipo, i) { this[tipo].splice(i, 1); },
        reordenar(tipo, i) {
            if (this.arrastrando === null || this.arrastrando === i) return;
            const lista = this[tipo]; const [x] = lista.splice(this.arrastrando, 1); lista.splice(i, 0, x); this.arrastrando = i;
        },
        async enviar(cuerpo) {
            this.guardando = true;
            const r = await gh.api(url, { method: 'POST', body: cuerpo });
            this.guardando = false;
            if (! r.ok) {
                const errores = r.data.errors ? Object.values(r.data.errors).flat() : [];
                Alpine.store('toasts').push(errores[0] || r.data.message || 'No se pudo guardar', 'error');
                return null;
            }
            Alpine.store('toasts').push(r.data.message || 'Guardado', 'ok');
            return r.data;
        },
        guardar(tipo) {
            const items = this[tipo].map(({ _k, nombre: n, ...resto }) => tipo === 'secciones' ? { clave: resto.clave, visible: !! resto.visible, titulo: resto.titulo, subtitulo: resto.subtitulo } : { ...resto, nombre: n, visible: !! resto.visible });
            return this.enviar({ tipo, items: items.map(it => { if (it.nombre === undefined) delete it.nombre; return it; }) });
        },
        async guardarBlog() {
            const d = await this.enviar({ tipo: 'blog', ...this.blog });
            if (d) this.entradas = d.entradas || [];
        },
    };
};
</script>
@endpush
