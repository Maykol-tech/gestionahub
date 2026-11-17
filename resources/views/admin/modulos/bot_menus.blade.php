@extends('layouts.app')

@section('titulo', 'Menús del bot')
@section('subtitulo', 'Opciones numeradas que el bot envía por WhatsApp')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .bm { display:grid; grid-template-columns:minmax(0, 1.4fr) minmax(280px, 1fr); gap:14px; align-items:start; margin-bottom:14px; }
        .bm-op { display:grid; grid-template-columns:34px minmax(0, 1fr) auto; gap:10px; align-items:start; padding:12px; border:1px solid var(--border); border-radius:12px; margin-bottom:8px; background:var(--surface); animation:opIn .25s ease both; }
        @keyframes opIn { from { opacity:0; transform:translateY(-6px); } }
        .bm-num { width:34px; height:34px; border-radius:10px; background:var(--acl); color:var(--accent); font-weight:800; display:grid; place-items:center; }
        .bm-op .inp { margin-bottom:6px; }
        .bm-op textarea.inp { min-height:56px; resize:vertical; }
        .bm-btns { display:flex; flex-direction:column; gap:4px; }
        .bm-btns button { width:30px; height:28px; border-radius:8px; border:1px solid var(--border); background:var(--surface2); color:var(--fg2); cursor:pointer; font-size:13px; }
        .bm-btns button:disabled { opacity:.35; cursor:default; }
        .bm-btns .x:hover { color:#B91C1C; border-color:#FCA5A5; }
        .bm-add { width:100%; padding:11px; border:1.5px dashed var(--border); border-radius:12px; background:none; color:var(--accent); font:inherit; font-weight:700; font-size:13px; cursor:pointer; transition:background .15s, border-color .15s; }
        .bm-add:hover { background:var(--acl); border-color:var(--accent); }
        .wa { background:#E5DDD5; border-radius:16px; padding:14px; min-height:300px; position:sticky; top:12px; background-image:radial-gradient(rgba(0,0,0,.04) 1px, transparent 1px); background-size:14px 14px; }
        .wa-cab { display:flex; align-items:center; gap:8px; margin:-14px -14px 12px; padding:10px 14px; background:#075E54; color:#fff; border-radius:16px 16px 0 0; font-weight:700; font-size:13px; }
        .wa-av { width:28px; height:28px; border-radius:50%; background:#25D366; display:grid; place-items:center; font-size:14px; }
        .burb { max-width:88%; padding:8px 10px 6px; border-radius:10px; font-size:13px; line-height:1.45; margin-bottom:8px; white-space:pre-wrap; word-wrap:break-word; box-shadow:0 1px 1px rgba(0,0,0,.1); color:#111; animation:opIn .25s ease both; }
        .burb.cli { background:#DCF8C6; margin-left:auto; }
        .burb.bot { background:#fff; }
        .burb small { display:block; text-align:right; font-size:10px; color:#667781; margin-top:2px; }
        .wa-prueba { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; }
        .wa-prueba button { border:none; background:#fff; border-radius:99px; padding:5px 11px; font:inherit; font-size:12px; font-weight:700; color:#075E54; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,.12); }
        .bm-lista { display:grid; gap:10px; }
        .bm-item { padding:14px 16px; display:flex; gap:14px; align-items:flex-start; }
        .bm-item.off { opacity:.55; }
        .bm-tag { font-family:ui-monospace, Menlo, monospace; font-size:12px; font-weight:700; padding:3px 9px; border-radius:8px; background:var(--surface2); border:1px solid var(--border); }
        .bm-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .bm-chips span { font-size:12px; padding:2px 9px; border-radius:99px; background:var(--surface2); color:var(--fg2); }
        .bm-acc { margin-left:auto; display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
        @media (max-width: 900px) { .bm { grid-template-columns:1fr; } .wa { position:static; } .bm-item { flex-wrap:wrap; } .bm-acc { width:100%; justify-content:flex-start; margin-left:0; } }
    </style>
@endpush

@section('contenido')
@php
    $vacio = ['disparador' => '', 'titulo' => 'Hola 👋 ¿En qué te ayudamos? Responde con un número:', 'opciones' => [['texto' => 'Ver precios', 'respuesta' => ''], ['texto' => 'Horarios de atención', 'respuesta' => ''], ['texto' => 'Hablar con una persona', 'respuesta' => '']]];
    $previo = old('disparador') !== null ? ['disparador' => old('disparador'), 'titulo' => old('titulo'), 'opciones' => is_array(old('opciones')) ? array_values(old('opciones')) : $vacio['opciones']] : $vacio;
@endphp
<div x-data="editorMenu(@js($previo), @js($vacio), @js(old('_editar')))">
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#15803D;font-weight:600;">✓ {{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#B91C1C;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="bm">
        <form method="POST" :action="editar ? '{{ url('admin/bot/menus') }}/' + editar : '{{ route('admin.bot.menus.guardar') }}'" class="panel fade-in" style="padding:18px;">
            @csrf
            <template x-if="editar"><input type="hidden" name="_method" value="PUT"></template>
            <input type="hidden" name="_editar" :value="editar || ''">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                <b x-text="editar ? 'Editar menú' : 'Nuevo menú'"></b>
                <button type="button" class="btn btn-g btn-sm" style="margin-left:auto;" x-show="editar" x-cloak @click="nuevo()">Cancelar edición</button>
            </div>

            <label class="lbl" for="disparador">Palabra que abre el menú</label>
            <input class="inp" id="disparador" name="disparador" maxlength="80" x-model="f.disparador" placeholder="Ej.: menu, hola, info" required>
            <div class="sub" style="font-size:12px;color:var(--fg3);margin:4px 0 12px;">Cuando el cliente escriba esta palabra, el bot le responde con el menú.</div>

            <label class="lbl" for="titulo">Mensaje de bienvenida</label>
            <textarea class="inp" id="titulo" name="titulo" rows="2" maxlength="500" x-model="f.titulo" required></textarea>

            <div class="lbl" style="margin-top:12px;">Opciones <span style="font-weight:400;color:var(--fg3);">(el número se pone solo)</span></div>
            <template x-for="(o, i) in f.opciones" :key="o._k">
                <div class="bm-op">
                    <div class="bm-num" x-text="i + 1"></div>
                    <div>
                        <input class="inp" :name="'opciones[' + i + '][texto]'" x-model="o.texto" maxlength="120" placeholder="Lo que ve el cliente. Ej.: Ver precios" :aria-label="'Texto de la opción ' + (i + 1)">
                        <textarea class="inp" :name="'opciones[' + i + '][respuesta]'" x-model="o.respuesta" maxlength="1000" placeholder="Lo que responde el bot si elige esta opción" :aria-label="'Respuesta de la opción ' + (i + 1)"></textarea>
                    </div>
                    <div class="bm-btns">
                        <button type="button" @click="mover(i, -1)" :disabled="i === 0" aria-label="Subir">↑</button>
                        <button type="button" @click="mover(i, 1)" :disabled="i === f.opciones.length - 1" aria-label="Bajar">↓</button>
                        <button type="button" class="x" @click="quitar(i)" :disabled="f.opciones.length <= 2" aria-label="Quitar">✕</button>
                    </div>
                </div>
            </template>
            <button type="button" class="bm-add" @click="agregar()" x-show="f.opciones.length < 9">+ Agregar opción</button>

            <button class="btn btn-p" type="submit" style="margin-top:14px;" x-text="editar ? 'Guardar cambios' : 'Guardar menú'"></button>
        </form>

        <div class="wa fade-in" style="animation-delay:.05s;" aria-label="Vista previa en WhatsApp">
            <div class="wa-cab"><span class="wa-av">🤖</span> Vista previa</div>
            <div class="burb cli" x-text="f.disparador || 'menu'"></div>
            <div class="burb bot"><span x-text="mensaje"></span><small>ahora</small></div>
            <template x-if="elegida !== null && f.opciones[elegida]">
                <div>
                    <div class="burb cli" x-text="elegida + 1"></div>
                    <div class="burb bot"><span x-text="f.opciones[elegida].respuesta || '(escribe la respuesta de esta opción)'"></span><small>ahora</small></div>
                </div>
            </template>
            <div class="wa-prueba">
                <span style="font-size:11px;color:#54656F;align-self:center;">Prueba:</span>
                <template x-for="(o, i) in f.opciones" :key="'p' + o._k"><button type="button" @click="elegida = i" x-text="i + 1"></button></template>
            </div>
        </div>
    </div>

    <div class="bm-lista">
        @forelse ($menus as $i => $m)
            <div class="panel bm-item fade-in {{ $m->activo ? '' : 'off' }}" style="animation-delay:{{ min($i, 8) * .04 }}s">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><span class="bm-tag">{{ $m->disparador }}</span> <span style="font-size:12px;color:{{ $m->activo ? '#15803D' : 'var(--fg3)' }};font-weight:600;">{{ $m->activo ? 'Activo' : 'Pausado' }}</span></div>
                    <div style="font-size:13px;color:var(--fg2);margin-top:6px;">{{ \Illuminate\Support\Str::limit($m->titulo, 120) }}</div>
                    <div class="bm-chips">@foreach ($m->opciones as $o)<span>{{ $o['numero'] }}. {{ $o['texto'] }}</span>@endforeach</div>
                </div>
                <div class="bm-acc">
                    <button type="button" class="btn btn-g btn-sm" @click="cargar(@js(['id' => $m->id, 'disparador' => $m->disparador, 'titulo' => $m->titulo, 'opciones' => array_map(fn ($o) => ['texto' => $o['texto'], 'respuesta' => $o['respuesta']], $m->opciones)]))">Editar</button>
                    <form method="POST" action="{{ route('admin.bot.menus.alternar', $m->id) }}">@csrf <button class="btn btn-g btn-sm" type="submit">{{ $m->activo ? 'Pausar' : 'Activar' }}</button></form>
                    <form method="POST" action="{{ route('admin.bot.menus.eliminar', $m->id) }}" onsubmit="return confirm('¿Eliminar el menú «{{ addslashes($m->disparador) }}»?')">@csrf @method('DELETE') <button class="btn btn-g btn-sm" type="submit" style="color:#B91C1C;">Eliminar</button></form>
                </div>
            </div>
        @empty
            <div class="panel vacio fade-in"><div class="vacio-ico"><x-icon name="bot" size="22" /></div><div class="vacio-ttl">Aún no hay menús</div><div class="vacio-txt">Crea el primero arriba. Mira a la derecha cómo lo verá tu cliente en WhatsApp.</div></div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    gh.ready(() => Alpine.data('editorMenu', (inicial, vacio, editando) => {
        let k = 0;
        const conClave = (m) => ({ ...m, opciones: m.opciones.map(o => ({ texto: o.texto || '', respuesta: o.respuesta || '', _k: ++k })) });
        return {
            f: conClave(inicial), editar: editando ? Number(editando) : null, elegida: null,
            get mensaje() { return (this.f.titulo || '') + '\n\n' + this.f.opciones.map((o, i) => (i + 1) + '. ' + (o.texto || '…')).join('\n'); },
            agregar() { if (this.f.opciones.length < 9) this.f.opciones.push({ texto: '', respuesta: '', _k: ++k }); },
            quitar(i) { if (this.f.opciones.length > 2) { this.f.opciones.splice(i, 1); this.elegida = null; } },
            mover(i, d) { const o = this.f.opciones; const j = i + d; if (j < 0 || j >= o.length) return; [o[i], o[j]] = [o[j], o[i]]; this.elegida = null; },
            cargar(m) { this.editar = m.id; this.f = conClave(m); this.elegida = null; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            nuevo() { this.editar = null; this.f = conClave(vacio); this.elegida = null; },
        };
    }));
</script>
@endpush
