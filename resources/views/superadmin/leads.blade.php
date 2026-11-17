@extends('layouts.app')

@section('titulo', 'Leads')
@section('subtitulo', 'Personas interesadas en GestionaHub: arrástralas por el embudo hasta cerrarlas')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .ld-filtros { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .ld-filtros input, .ld-filtros select { width:auto; min-width:150px; }
        .tablero { display:grid; grid-template-columns:repeat(5, minmax(220px, 1fr)); gap:12px; overflow-x:auto; padding-bottom:6px; }
        .col { background:var(--surface2); border:1px solid var(--border); border-radius:14px; padding:10px; min-height:260px; transition:background .2s, border-color .2s; }
        .col.sobre { background:var(--acl); border-color:var(--accent); }
        .col-hd { display:flex; align-items:center; justify-content:space-between; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--fg2); margin:2px 4px 10px; }
        .col-hd .n { background:#fff; border-radius:99px; padding:1px 8px; font-size:11px; color:var(--fg3); }
        .col-hd i { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:6px; }
        .card-l { background:#fff; border:1px solid var(--border); border-radius:12px; padding:10px 12px; margin-bottom:8px; cursor:grab; box-shadow:0 1px 2px rgba(15,23,42,.04); transition:transform .15s, box-shadow .15s; animation:subirL .3s ease both; }
        .card-l:hover { transform:translateY(-2px); box-shadow:0 8px 18px rgba(15,23,42,.08); }
        .card-l.arrastrando { opacity:.5; }
        .card-l strong { display:block; font-size:14px; }
        .card-l small { color:var(--fg3); font-size:11px; }
        .tag { display:inline-block; font-size:10px; font-weight:700; border-radius:99px; padding:2px 8px; background:var(--surface2); color:var(--fg2); margin:4px 4px 0 0; }
        .tag.utm { background:#EEF3FF; color:#3056D3; }
        @keyframes subirL { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
        .det-bg { position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; justify-content:flex-end; z-index:90; }
        .det { background:#fff; width:min(420px, 100%); height:100%; overflow:auto; padding:22px; animation:entrarD .25s ease both; }
        @keyframes entrarD { from { transform:translateX(30px); opacity:0; } to { transform:none; opacity:1; } }
        .det dl { display:grid; grid-template-columns:110px 1fr; gap:6px 10px; font-size:13px; }
        .det dt { color:var(--fg3); }
        .etapas { display:flex; gap:6px; flex-wrap:wrap; margin:12px 0; }
        .etapas button { border:1px solid var(--border); background:#fff; border-radius:99px; padding:6px 12px; font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
        .etapas button.on { background:var(--accent); border-color:var(--accent); color:#fff; }
        .barra-f { display:flex; align-items:center; gap:8px; font-size:13px; margin-bottom:6px; }
        .barra-f span:first-child { width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--fg2); }
        @media (prefers-reduced-motion: reduce) { .card-l, .det { animation:none; } }
    </style>
@endpush

@section('contenido')
@php($colores = ['nuevo' => '#3B82F6', 'contactado' => '#F59E0B', 'demo' => '#8B5CF6', 'ganado' => '#22C55E', 'perdido' => '#94A3B8'])
<div x-data="leadsTablero(@js($leads), @js(url('superadmin/leads')))" @keydown.escape.window="abierto = null">
    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Leads</div><div class="stat-val">{{ $resumen['total'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Sin atender</div><div class="stat-val" style="{{ $resumen['nuevos'] ? 'color:#2563EB' : '' }}">{{ $resumen['nuevos'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Últimos 7 días</div><div class="stat-val">{{ $resumen['semana'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Ganados</div><div class="stat-val" style="color:var(--green);">{{ $resumen['ganados'] }}</div><div style="font-size:12px;color:var(--fg3);">{{ $resumen['conversion'] }}% de conversión</div></div>
    </div>

    <div class="panel fade-in" style="margin-bottom:14px;">
        <form class="panel-hd ld-filtros" method="GET">
            <input class="inp" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar nombre, correo o teléfono">
            <select class="inp" name="fuente"><option value="">Todos los orígenes</option>@foreach ($fuentes as $f)<option value="{{ $f }}" @selected(($filtros['fuente'] ?? '') === $f)>{{ ucfirst($f) }}</option>@endforeach</select>
            <select class="inp" name="campana"><option value="">Todas las campañas</option>@foreach ($campanas as $c)<option value="{{ $c }}" @selected(($filtros['campana'] ?? '') === $c)>{{ $c }}</option>@endforeach</select>
            <input class="inp" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde">
            <button class="btn btn-p btn-sm" type="submit">Filtrar</button>
            <a class="btn btn-g btn-sm" href="{{ route('superadmin.leads.exportar', $filtros) }}">Exportar CSV</a>
        </form>
        @if ($porFuente->isNotEmpty())
            <div style="padding:4px 16px 14px;">
                <div style="font-size:12px;font-weight:700;color:var(--fg3);margin-bottom:8px;">DE DÓNDE VIENEN</div>
                @foreach ($porFuente as $f => $n)
                    <div class="barra-f"><span>{{ $f }}</span><div class="hbar" style="flex:1;margin:0;"><span style="width:{{ round($n * 100 / max(1, $resumen['total'])) }}%"></span></div><b>{{ $n }}</b></div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="tablero">
        @foreach ($estados as $clave => $nombre)
            <div class="col" :class="{ sobre: sobre === '{{ $clave }}' }"
                 @dragover.prevent="sobre = '{{ $clave }}'" @dragleave="sobre = null" @drop.prevent="soltar('{{ $clave }}')">
                <div class="col-hd"><span><i style="background:{{ $colores[$clave] }}"></i>{{ $nombre }}</span><span class="n" x-text="de('{{ $clave }}').length"></span></div>
                <template x-for="l in de('{{ $clave }}')" :key="l.id">
                    <div class="card-l" draggable="true" @dragstart="arrastrado = l.id; $el.classList.add('arrastrando')" @dragend="$el.classList.remove('arrastrando')" @click="abrir(l)">
                        <strong x-text="l.nombre"></strong>
                        <small x-text="(l.telefono || l.email || '') + ' · ' + l.hace"></small>
                        <div>
                            <span class="tag" x-text="l.fuente"></span>
                            <template x-if="l.utm_source"><span class="tag utm" x-text="l.utm_source + (l.utm_campaign ? ' / ' + l.utm_campaign : '')"></span></template>
                            <template x-if="l.oferta"><span class="tag" x-text="'🎁 ' + l.oferta"></span></template>
                        </div>
                    </div>
                </template>
                <template x-if="! de('{{ $clave }}').length"><div style="font-size:12px;color:var(--fg3);text-align:center;padding:20px 6px;">Arrastra aquí</div></template>
            </div>
        @endforeach
    </div>

    <template x-if="abierto">
        <div class="det-bg" @click.self="abierto = null">
            <div class="det">
                <div style="display:flex;justify-content:space-between;align-items:start;gap:10px;">
                    <div><h3 style="margin:0;font-size:18px;" x-text="abierto.nombre"></h3><small style="color:var(--fg3);" x-text="abierto.fecha"></small></div>
                    <button class="btn btn-g btn-sm" type="button" @click="abierto = null">Cerrar</button>
                </div>
                <div class="etapas">
                    @foreach ($estados as $clave => $nombre)
                        <button type="button" :class="{ on: abierto.etapa === '{{ $clave }}' }" @click="mover(abierto.id, '{{ $clave }}')">{{ $nombre }}</button>
                    @endforeach
                </div>
                <dl>
                    <dt>Correo</dt><dd x-text="abierto.email || '—'"></dd>
                    <dt>Teléfono</dt><dd x-text="abierto.telefono || '—'"></dd>
                    <dt>Origen</dt><dd x-text="abierto.fuente"></dd>
                    <dt>utm_source</dt><dd x-text="abierto.utm_source || '—'"></dd>
                    <dt>utm_medium</dt><dd x-text="abierto.utm_medium || '—'"></dd>
                    <dt>utm_campaign</dt><dd x-text="abierto.utm_campaign || '—'"></dd>
                    <dt>Mensaje</dt><dd x-text="abierto.mensaje || '—'" style="white-space:pre-wrap;"></dd>
                </dl>
                <div style="display:flex;gap:8px;margin:14px 0;flex-wrap:wrap;">
                    <template x-if="abierto.wa"><a class="btn btn-wa btn-sm" :href="abierto.wa + '?text=' + encodeURIComponent('Hola ' + abierto.nombre + ', te escribo de GestionaHub por tu consulta.')" target="_blank" rel="noopener" @click="if (abierto.etapa === 'nuevo') mover(abierto.id, 'contactado')">WhatsApp</a></template>
                    <template x-if="abierto.email"><a class="btn btn-g btn-sm" :href="'mailto:' + abierto.email">Correo</a></template>
                </div>
                <label class="lbl" for="ld-notas">Notas internas</label>
                <textarea id="ld-notas" class="inp" rows="6" x-model="abierto.notas" placeholder="Qué conversaron, cuándo volver a llamar..."></textarea>
                <button class="btn btn-p btn-sm" type="button" style="margin-top:8px;" @click="guardarNotas()" :disabled="guardando">Guardar notas</button>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
window.leadsTablero = window.leadsTablero || function (leads, base) {
    return {
        leads, arrastrado: null, sobre: null, abierto: null, guardando: false,
        de(etapa) { return this.leads.filter(l => l.etapa === etapa); },
        abrir(l) { this.abierto = { ...l }; },
        async enviar(id, cuerpo) {
            const r = await gh.api(base + '/' + id, { method: 'PATCH', body: cuerpo });
            if (! r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar', 'error'); return false; }
            const i = this.leads.findIndex(l => l.id === id);
            if (r.data.lead && i > -1) { this.leads[i] = r.data.lead; if (this.abierto && this.abierto.id === id) this.abierto = { ...r.data.lead }; }
            return true;
        },
        async soltar(etapa) {
            this.sobre = null;
            const l = this.leads.find(x => x.id === this.arrastrado);
            if (! l || l.etapa === etapa) return;
            const antes = l.etapa; l.etapa = etapa;
            const r = await this.enviar(l.id, { estado: etapa });
            if (! r) { l.etapa = antes; return; }
            Alpine.store('toasts').push('Movido a ' + etapa, 'ok');
        },
        async mover(id, etapa) {
            const r = await this.enviar(id, { estado: etapa });
            if (r) Alpine.store('toasts').push('Etapa actualizada', 'ok');
        },
        async guardarNotas() {
            this.guardando = true;
            const r = await this.enviar(this.abierto.id, { notas: this.abierto.notas || '' });
            this.guardando = false;
            if (r) Alpine.store('toasts').push('Notas guardadas', 'ok');
        },
    };
};
</script>
@endpush
