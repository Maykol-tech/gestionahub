@extends('layouts.app')

@section('titulo', 'Superadmin')
@section('subtitulo', 'Negocios, suscripciones y planes de la plataforma')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .tabs { display:flex; gap:6px; margin-bottom:14px; flex-wrap:wrap; }
        .tab-btn { border:1px solid var(--border); background:#fff; border-radius:99px; padding:7px 16px; font-size:13px; font-weight:600; color:var(--fg2); cursor:pointer; transition: background .2s var(--ease), color .2s var(--ease), border-color .2s var(--ease); }
        .tab-btn:hover { border-color:var(--brand, #2B6EF6); color:var(--fg); }
        .tab-btn.on { background:var(--brand, #2B6EF6); border-color:var(--brand, #2B6EF6); color:#fff; }
        .chip { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; white-space:nowrap; }
        .acciones { display:flex; gap:6px; justify-content:flex-end; align-items:center; flex-wrap:wrap; }
        .campos-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .campos-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
        @media (max-width: 640px) { .campos-2, .campos-3 { grid-template-columns:1fr; } }
        .sa-kpis { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .sa-kpi { background:var(--surface-2, #F6F8FB); border:1px solid var(--border); border-radius:14px; padding:16px; transition: transform .2s var(--ease), box-shadow .2s var(--ease); }
        .sa-kpi:hover { transform:translateY(-2px); box-shadow:0 10px 26px rgba(26,35,50,.09); }
        .sk-lbl { font-size:11px; font-weight:600; color:var(--fg3); }
        .sk-val { font-size:26px; font-weight:800; margin:6px 0; font-variant-numeric:tabular-nums; }
        .sk-val small { font-size:14px; color:var(--fg3); font-weight:600; }
        .sk-sub { font-size:11px; color:var(--fg3); }
        .sa-mrr { background:linear-gradient(130deg, var(--accent, #4B7CF5) 0%, #1E3A8A 75%, #1A2332 100%); border:0; color:#fff; }
        .sa-mrr .sk-lbl, .sa-mrr .sk-sub { color:rgba(255,255,255,.8); }
        .sa-grid { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:16px; align-items:start; }
        .sa-side { display:grid; gap:14px; }
        .dist-fila { margin-bottom:12px; }
        .dist-top { display:flex; justify-content:space-between; gap:8px; font-size:13px; }
        .dist-top small { color:var(--fg3); font-size:11px; }
        .dist-bar { height:5px; border-radius:99px; background:#E2E8F0; margin-top:6px; overflow:hidden; }
        .dist-bar span { display:block; height:100%; border-radius:99px; background:var(--accent, #4B7CF5); }
        .sa-link { display:flex; align-items:center; gap:8px; padding:9px 0; border-top:1px solid var(--border); font-size:13px; font-weight:600; color:var(--fg); text-decoration:none; }
        .sa-link:first-of-type { border-top:0; }
        .sa-link:hover { color:var(--accent); }
        .sa-badge { margin-left:auto; background:var(--cta, #F47B20); color:#fff; border-radius:99px; font-size:11px; padding:1px 8px; }
        .plan-pill { display:inline-block; font-size:11px; font-weight:700; padding:2px 9px; border-radius:6px; background:#94A3B8; color:#fff; }
        .pp-negocio { background:#6366F1; } .pp-pro { background:var(--accent, #4B7CF5); } .pp-emprendedor { background:#94A3B8; } .pp-sinplan { background:#E2E8F0; color:#475569; }
        @media (max-width: 1200px) { .sa-kpis { grid-template-columns:repeat(3, minmax(0,1fr)); } .sa-grid { grid-template-columns:1fr; } }
        @media (max-width: 640px) { .sa-kpis { grid-template-columns:1fr 1fr; } }
    </style>
@endpush

@section('contenido')
<div x-data="superadmin(@js($negocios), @js($planes), @js($estados))" @keydown.escape.window="modal = null">

    <div class="sa-kpis">
        <div class="sa-kpi sa-mrr fade-in">
            <div class="sk-lbl">Ingreso mensual (MRR)</div>
            <div class="sk-val">S/ {{ number_format($stats['ingresos_mensuales'], 2) }}</div>
            <div class="sk-sub">De {{ $stats['activos'] }} {{ $stats['activos'] === 1 ? 'negocio activo' : 'negocios activos' }}</div>
        </div>
        <div class="sa-kpi fade-in" style="animation-delay:.05s">
            <div class="sk-lbl">Negocios activos</div>
            <div class="sk-val">{{ $stats['activos'] }}<small> / {{ $stats['total'] }}</small></div>
            <div class="sk-sub" style="color:var(--green)">+{{ $stats['nuevos_mes'] }} este mes</div>
        </div>
        <div class="sa-kpi fade-in" style="animation-delay:.1s">
            <div class="sk-lbl">Suspendidos</div>
            <div class="sk-val" style="{{ $stats['suspendidos'] ? 'color:#B91C1C' : '' }}">{{ $stats['suspendidos'] }}</div>
            <div class="sk-sub" style="color:{{ $stats['por_vencer'] ? '#B91C1C' : 'var(--fg3)' }}">{{ $stats['por_vencer'] }} vencen en 7 días</div>
        </div>
        <div class="sa-kpi fade-in" style="animation-delay:.15s">
            <div class="sk-lbl">Pedidos totales hoy</div>
            <div class="sk-val">{{ number_format($stats['pedidos_hoy']) }}</div>
            <div class="sk-sub">En todos los negocios</div>
        </div>
        <a class="sa-kpi fade-in" href="{{ route('superadmin.suscripciones') }}" style="animation-delay:.2s;text-decoration:none;color:inherit;">
            <div class="sk-lbl">Pagos por revisar</div>
            <div class="sk-val" style="color:var(--cta, #F47B20)">S/ {{ number_format($stats['pagos_revision_monto'], 2) }}</div>
            <div class="sk-sub" style="color:var(--cta, #F47B20)">{{ $stats['pagos_revision'] }} {{ $stats['pagos_revision'] === 1 ? 'pago pendiente' : 'pagos pendientes' }}</div>
        </a>
    </div>

    <div class="sa-grid">
    <div style="min-width:0;">
    <div class="tabs" role="tablist">
        <button type="button" class="tab-btn" :class="{ on: tab === 'negocios' }" @click="tab = 'negocios'" role="tab">Negocios</button>
        <button type="button" class="tab-btn" :class="{ on: tab === 'planes' }" @click="tab = 'planes'" role="tab">Planes</button>
    </div>

    {{-- NEGOCIOS --}}
    <div class="panel fade-in" x-show="tab === 'negocios'" x-cloak>
        <div class="panel-hd">
            <div class="search" style="flex:1;min-width:180px;max-width:320px;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar negocio o correo…" x-model="buscar" aria-label="Buscar negocio">
            </div>
            <select class="inp" style="max-width:180px;" x-model="filtroEstado" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach ($estados as $clave => $nombre)
                    <option value="{{ $clave }}">{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="tbl-wrap" x-show="filtrados.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Negocio</th>
                        <th>Plan</th>
                        <th class="num">Sucursales</th>
                        <th>Vence</th>
                        <th>Estado</th>
                        <th style="width:270px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(n, i) in filtrados" :key="n.id">
                        <tr class="row-in" :style="'--d:' + (i * 30) + 'ms'">
                            <td>
                                <div style="font-weight:600;" x-text="n.nombre"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="n.email + ' · alta ' + n.alta"></div>
                            </td>
                            <td><span class="plan-pill" :class="'pp-' + (n.plan || '').toLowerCase().replace(/[^a-z]/g, '')" x-text="n.plan"></span></td>
                            <td class="num" x-text="n.sucursales"></td>
                            <td x-text="n.vence_label"></td>
                            <td>
                                <span class="chip" :style="chipEstado(n.estado)" x-text="estados[n.estado]"></span>
                            </td>
                            <td>
                                <div class="acciones">
                                    <button class="btn btn-g btn-sm" type="button" @click="abrirExtender(n)">Extender</button>
                                    <button class="btn btn-g btn-sm" type="button" x-show="n.estado !== 'activo'" x-cloak @click="cambiarEstado(n, 'activo')">Activar</button>
                                    <button class="btn btn-g btn-sm" type="button" x-show="n.estado === 'activo'" x-cloak @click="cambiarEstado(n, 'suspendido')">Suspender</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="empty" x-show="!filtrados.length" x-cloak>
            <div style="font-weight:600;color:var(--fg2);">Ningún negocio coincide con tu búsqueda</div>
        </div>
    </div>

    {{-- PLANES --}}
    <div class="panel fade-in" x-show="tab === 'planes'" x-cloak>
        <div class="panel-hd">
            <span style="font-weight:700;">Planes de suscripción</span>
            <button class="btn btn-p" type="button" style="margin-left:auto;" @click="abrirPlan()">Nuevo plan</button>
        </div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th class="num">Precio mensual</th>
                        <th class="num">Sucursales</th>
                        <th class="num">Negocios</th>
                        <th>Estado</th>
                        <th style="width:90px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(p, i) in planes" :key="p.id">
                        <tr class="row-in" :style="'--d:' + (i * 30) + 'ms'">
                            <td>
                                <div style="font-weight:600;" x-text="p.nombre"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="p.slug"></div>
                            </td>
                            <td class="num" x-text="'S/ ' + Number(p.precio_mensual).toFixed(2)"></td>
                            <td class="num" x-text="p.max_sucursales"></td>
                            <td class="num" x-text="p.negocios"></td>
                            <td><span class="chip" :style="p.activo ? 'background:#DCFCE7;color:#15803D' : 'background:#E2E8F0;color:#475569'" x-text="p.activo ? 'Activo' : 'Oculto'"></span></td>
                            <td><button class="btn btn-g btn-sm" type="button" @click="abrirPlan(p)">Editar</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    </div>
    <aside class="sa-side">
        <div class="panel fade-in" style="padding:16px;">
            <div class="panel-ttl" style="margin-bottom:12px;">Distribución de planes</div>
            @php($maxNeg = max(1, $distribucion->max('negocios')))
            @forelse ($distribucion as $d)
                <div class="dist-fila">
                    <div class="dist-top"><span>{{ $d['nombre'] }} S/{{ number_format($d['precio'], 0) }}</span><small>{{ $d['negocios'] }} {{ $d['negocios'] === 1 ? 'negocio' : 'negocios' }} · S/{{ number_format($d['monto'], 0) }}</small></div>
                    <div class="dist-bar"><span style="width:{{ round($d['negocios'] * 100 / $maxNeg) }}%"></span></div>
                </div>
            @empty
                <div style="font-size:13px;color:var(--fg3);">Aún no hay planes.</div>
            @endforelse
        </div>
        <div class="panel fade-in" style="padding:16px;animation-delay:.05s">
            <div class="panel-ttl" style="margin-bottom:10px;">Accesos rápidos</div>
            <a class="sa-link" href="{{ route('superadmin.suscripciones') }}"><x-icon name="card" size="15" /> Pagos de suscripción @if ($stats['pagos_revision'])<span class="sa-badge">{{ $stats['pagos_revision'] }}</span>@endif</a>
            <a class="sa-link" href="{{ route('superadmin.leads') }}"><x-icon name="users" size="15" /> Leads de la landing</a>
            <a class="sa-link" href="{{ route('superadmin.landing') }}"><x-icon name="pen" size="15" /> Editor de la landing</a>
        </div>
    </aside>
    </div>

    {{-- Ventana: extender suscripcion --}}
    <div class="modal-bg" x-show="modal === 'extender'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="modal = null">
        <form class="modal" style="max-width:460px;" @submit.prevent="extender()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="activity" size="18" /></div>
                <div>
                    <div class="modal-ttl">Extender suscripción</div>
                    <div class="modal-sub" x-text="sel ? sel.nombre : ''"></div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="s-dias">Días a agregar *</label>
                    <input class="inp" id="s-dias" type="number" min="1" max="365" step="1" x-model.number="ext.dias">
                </div>
                <div class="field">
                    <label class="lbl" for="s-plan">Plan</label>
                    <select class="inp" id="s-plan" x-model="ext.plan_id">
                        <option value="">Mantener el plan actual</option>
                        <template x-for="p in planes" :key="p.id"><option :value="p.id" x-text="p.nombre + ' · S/ ' + Number(p.precio_mensual).toFixed(2)"></option></template>
                    </select>
                </div>
                <p style="font-size:13px;color:var(--fg2);margin:0;">Si el negocio está suspendido, al extender se vuelve a activar.</p>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando">Extender</button>
            </div>
        </form>
    </div>

    {{-- Ventana: plan --}}
    <div class="modal-bg" x-show="modal === 'plan'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="modal = null">
        <form class="modal" style="max-width:520px;" @submit.prevent="guardarPlan()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="settings" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="editandoPlan ? 'Editar plan' : 'Nuevo plan'"></div>
                    <div class="modal-sub">Los cambios aplican a los cobros nuevos</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="pl-nombre">Nombre *</label>
                        <input class="inp" id="pl-nombre" maxlength="80" x-model="pl.nombre" :class="{ err: errores.nombre }">
                        <div class="ferr" x-show="errores.nombre" x-text="errores.nombre ? errores.nombre[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="pl-slug">Identificador *</label>
                        <input class="inp" id="pl-slug" maxlength="60" x-model="pl.slug" :class="{ err: errores.slug }">
                        <div class="ferr" x-show="errores.slug" x-text="errores.slug ? errores.slug[0] : ''"></div>
                    </div>
                </div>
                <div class="campos-3">
                    <div class="field">
                        <label class="lbl" for="pl-precio">Precio mensual (S/) *</label>
                        <input class="inp" id="pl-precio" type="number" min="0" step="0.01" x-model.number="pl.precio_mensual" :class="{ err: errores.precio_mensual }">
                        <div class="ferr" x-show="errores.precio_mensual" x-text="errores.precio_mensual ? errores.precio_mensual[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="pl-suc">Sucursales *</label>
                        <input class="inp" id="pl-suc" type="number" min="1" max="500" step="1" x-model.number="pl.max_sucursales" :class="{ err: errores.max_sucursales }">
                        <div class="ferr" x-show="errores.max_sucursales" x-text="errores.max_sucursales ? errores.max_sucursales[0] : ''"></div>
                    </div>
                    <div class="field" style="display:flex;align-items:end;">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;"><input type="checkbox" x-model="pl.activo"> Visible</label>
                    </div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando">Guardar plan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.superadmin = window.superadmin || function (negocios, planes, estados) {
        const urlNegocios = @js(url('superadmin/negocios'));
        const urlPlanes = @js(url('superadmin/planes'));
        const chips = {
            activo: 'background:#DCFCE7;color:#15803D',
            suspendido: 'background:#FEE2E2;color:#B91C1C',
            cancelado: 'background:#E2E8F0;color:#475569',
        };
        const planVacio = () => ({ nombre: '', slug: '', precio_mensual: 0, max_sucursales: 1, activo: true });

        return {
            negocios, planes, estados,
            tab: 'negocios', buscar: '', filtroEstado: '',
            modal: null, sel: null, ext: { dias: 30, plan_id: '' },
            editandoPlan: null, pl: planVacio(), errores: {}, guardando: false,

            chipEstado(e) { return chips[e] || chips.cancelado; },

            get filtrados() {
                const q = gh.norm(this.buscar);
                return this.negocios.filter(n =>
                    (!this.filtroEstado || n.estado === this.filtroEstado) &&
                    (!q || gh.norm(n.nombre).includes(q) || gh.norm(n.email).includes(q))
                );
            },

            async cambiarEstado(n, estado) {
                if (estado === 'suspendido' && !confirm('¿Suspender a ' + n.nombre + '? Perderá acceso al panel y a la tienda.')) return;
                const r = await gh.api(urlNegocios + '/' + n.id + '/estado', { method: 'PATCH', body: { estado } });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo cambiar el estado.', 'error'); }
            },

            abrirExtender(n) {
                this.sel = n; this.ext = { dias: 30, plan_id: n.plan_id || '' }; this.modal = 'extender';
            },
            async extender() {
                this.guardando = true;
                const r = await gh.api(urlNegocios + '/' + this.sel.id + '/extender', { method: 'POST', body: this.ext });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.modal = null; gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo extender la suscripción.', 'error'); }
            },

            abrirPlan(p = null) {
                this.errores = {};
                this.editandoPlan = p ? p.id : null;
                this.pl = p ? { nombre: p.nombre, slug: p.slug, precio_mensual: p.precio_mensual, max_sucursales: p.max_sucursales, activo: p.activo } : planVacio();
                this.modal = 'plan';
            },
            async guardarPlan() {
                this.guardando = true; this.errores = {};
                const url = this.editandoPlan ? urlPlanes + '/' + this.editandoPlan : urlPlanes;
                const r = await gh.api(url, { method: this.editandoPlan ? 'PUT' : 'POST', body: this.pl });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.modal = null; gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar el plan.', 'error'); }
            },
        };
    };
    gh.ready(() => Alpine.data('superadmin', window.superadmin));
</script>
@endpush
@endsection
