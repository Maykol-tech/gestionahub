@extends('layouts.app')

@section('titulo', 'Membresías')
@section('subtitulo', 'Planes que tus clientes pagan cada semana, mes o año')

@section('acciones')
    @if ($urlPlanes)
        <a class="btn btn-g" href="{{ $urlPlanes }}" target="_blank" rel="noopener"><x-icon name="globe" size="15" /> Ver página de planes</a>
    @endif
    <button class="btn btn-p" x-data @click="$dispatch('nuevo-plan')"><x-icon name="plus" size="15" stroke="2.4" /> Nuevo plan</button>
@endsection

@section('contenido')
<div x-data="membresias(@js($planes), @js($suscripciones), @js($clientes))" @nuevo-plan.window="abrirPlan(null)">

    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Suscriptores activos</div><div class="stat-val">{{ $stats['activas'] }}</div><div class="stat-ch neutral">{{ $stats['vencidas'] }} vencidos · {{ $stats['canceladas'] }} cancelados</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Ingreso recurrente</div><div class="stat-val">S/ {{ number_format($stats['recurrente'], 2) }}</div><div class="stat-ch neutral">al mes, con los activos</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Cobrado este mes</div><div class="stat-val">S/ {{ number_format($stats['cobrado_mes'], 2) }}</div><div class="stat-ch neutral">pagos de membresías</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Pagos por revisar</div><div class="stat-val">{{ $stats['por_revisar'] }}</div><div class="stat-ch {{ $stats['por_revisar'] ? 'dn' : 'neutral' }}">{{ $stats['por_revisar'] ? 'Capturas enviadas por clientes' : 'Nada pendiente' }}</div></div>
    </div>

    <div class="panel fade-in">
        <div class="tabs">
            <button class="tab" :class="{ on: vista === 'subs' }" @click="vista = 'subs'">Suscriptores <span class="n" x-text="subs.length"></span></button>
            <button class="tab" :class="{ on: vista === 'planes' }" @click="vista = 'planes'">Planes <span class="n" x-text="planes.length"></span></button>
        </div>

        {{-- Suscriptores --}}
        <div x-show="vista === 'subs'">
            <div class="panel-hd">
                <div class="chips">
                    <template x-for="f in filtros" :key="f[0]">
                        <button class="chip" :class="{ on: filtro === f[0] }" @click="filtro = f[0]"><span x-text="f[1]"></span><span class="n" x-text="contar(f[0])"></span></button>
                    </template>
                </div>
                <button class="btn btn-p btn-sm" style="margin-left:auto;" @click="abrirInscribir()" :disabled="!planes.some(p => p.activo)"><x-icon name="user-plus" size="14" /> Inscribir cliente</button>
            </div>
            <div class="tbl-wrap" x-show="filtradas.length" x-cloak>
                <table class="tbl">
                    <thead><tr><th>Cliente</th><th>Plan</th><th>Estado</th><th>Próximo cobro</th><th>Cobro pendiente</th><th style="text-align:right;"></th></tr></thead>
                    <tbody>
                        <template x-for="s in filtradas" :key="s.id">
                            <tr :class="{ 'row-new': s.id === resaltado }">
                                <td><div style="font-weight:600;" x-text="s.cliente"></div><div style="font-size:11px;color:var(--fg3);" x-text="(s.telefono || 'Sin teléfono') + ' · desde ' + s.desde"></div></td>
                                <td><div x-text="s.plan"></div><div style="font-size:11px;color:var(--fg3);" x-text="gh.soles(s.precio) + ' · ' + s.periodo"></div></td>
                                <td><span class="badge" :class="{ activa: 'b-ok', pendiente: 'b-a', vencida: 'b-r', cancelada: 'b-n' }[s.estado]"><span class="dot"></span><span x-text="estados[s.estado]"></span></span></td>
                                <td style="color:var(--fg2);" x-text="s.estado === 'cancelada' ? '—' : s.proximo"></td>
                                <td>
                                    <template x-if="s.cobro">
                                        <div>
                                            <b x-text="gh.soles(s.cobro.monto)"></b>
                                            <span class="badge" style="margin-left:4px;" :class="s.cobro.estado === 'por_revisar' ? 'b-g' : (s.cobro.estado === 'vencido' ? 'b-r' : 'b-a')" x-text="{ pendiente: 'vence ' + s.cobro.vence, por_revisar: 'captura enviada', vencido: 'vencido' }[s.cobro.estado]"></span>
                                        </div>
                                    </template>
                                    <span x-show="!s.cobro" style="color:var(--fg3);">—</span>
                                </td>
                                <td>
                                    <div class="acc">
                                        <a class="btn btn-g btn-sm" x-show="s.cobro && s.cobro.captura" :href="s.cobro ? urlCaptura(s.cobro) : '#'" target="_blank" rel="noopener">Ver captura</a>
                                        <button class="btn btn-p btn-sm" x-show="s.cobro" @click="abrirPago(s)">Registrar pago</button>
                                        <button class="btn btn-g btn-sm" x-show="s.cobro && s.cobro.estado === 'por_revisar'" @click="rechazar(s)">Rechazar</button>
                                        <a class="btn btn-wa btn-sm" x-show="s.cobro && s.cobro.aviso" :href="s.cobro ? s.cobro.aviso : '#'" target="_blank" rel="noopener" title="Recordar el pago por WhatsApp"><x-icon name="message" size="13" /></a>
                                        <button class="icon-btn sm" @click="copiar(s.enlace)" title="Copiar el enlace del cliente"><x-icon name="layers" size="14" /></button>
                                        <button class="icon-btn sm" x-show="s.estado !== 'cancelada'" @click="cancelar(s)" title="Cancelar suscripción"><x-icon name="x" size="14" /></button>
                                        <button class="btn btn-g btn-sm" x-show="s.estado === 'cancelada'" @click="reactivar(s)">Reactivar</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="empty" x-show="!filtradas.length" x-cloak>
                <x-icon name="users" size="32" />
                <div style="font-weight:600;color:var(--fg2);" x-text="subs.length ? 'Nadie en este filtro' : 'Aún no tienes suscriptores'"></div>
                <div style="font-size:13px;color:var(--fg3);max-width:440px;text-align:center;" x-show="!subs.length">Crea un plan, comparte tu página de planes o inscribe a un cliente. Cada periodo se crea su cobro solo y se le avisa por WhatsApp.</div>
            </div>
        </div>

        {{-- Planes --}}
        <div x-show="vista === 'planes'" x-cloak>
            <div class="planes-grid">
                <template x-for="p in planes" :key="p.id">
                    <div class="plan-c" :class="{ off: !p.activo }">
                        <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                            <div><b x-text="p.nombre"></b><div class="plan-p"><span x-text="gh.soles(p.precio)"></span> <small x-text="'/ ' + periodos[p.periodicidad].toLowerCase()"></small></div></div>
                            <span class="badge" :class="p.activo ? 'b-ok' : 'b-n'" x-text="p.activo ? 'Activo' : 'Pausado'"></span>
                        </div>
                        <div style="font-size:13px;color:var(--fg2);margin:6px 0;" x-text="p.descripcion"></div>
                        <ul><template x-for="b in p.beneficios" :key="b"><li x-text="b"></li></template></ul>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
                            <span style="font-size:12px;color:var(--fg3);" x-text="p.suscriptores + (p.suscriptores === 1 ? ' suscriptor activo' : ' suscriptores activos')"></span>
                            <button class="btn btn-g btn-sm" @click="abrirPlan(p)">Editar</button>
                        </div>
                    </div>
                </template>
                <button class="plan-c plan-nuevo" @click="abrirPlan(null)"><x-icon name="plus" size="20" /> Nuevo plan</button>
            </div>
        </div>
    </div>

    {{-- Modal plan --}}
    <div class="modal-bg" x-show="modal === 'plan'" x-cloak @keydown.escape.window="modal = null" @mousedown.self="modal = null">
        <form class="modal" @submit.prevent="guardarPlan()">
            <div class="modal-hd">
                <div><div class="modal-ttl" x-text="plan.id ? 'Editar plan' : 'Nuevo plan'"></div><div class="modal-sub">Ej.: Menú semanal, Membresía del gimnasio, Lavado mensual</div></div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field"><label class="lbl">Nombre *</label><input class="inp" x-model="plan.nombre" maxlength="80" :class="{ err: errores.nombre }"><div class="ferr" x-text="(errores.nombre || [])[0]"></div></div>
                <div class="grid2">
                    <div class="field"><label class="lbl">Precio (S/) *</label><input class="inp" type="number" step="0.10" min="0" x-model="plan.precio" :class="{ err: errores.precio }"><div class="ferr" x-text="(errores.precio || [])[0]"></div></div>
                    <div class="field"><label class="lbl">Se cobra</label><select class="inp" x-model="plan.periodicidad"><template x-for="(v, k) in periodos" :key="k"><option :value="k" x-text="v"></option></template></select></div>
                </div>
                <div class="field"><label class="lbl">Descripción corta</label><input class="inp" x-model="plan.descripcion" maxlength="255"></div>
                <div class="field">
                    <label class="lbl">Beneficios <span style="font-weight:400;color:var(--fg3);">(uno por línea)</span></label>
                    <textarea class="inp" rows="4" x-model="plan.beneficiosTxt" placeholder="5 almuerzos a la semana&#10;Delivery gratis&#10;Postre los viernes"></textarea>
                    <div class="ferr" x-text="(errores.beneficios || [])[0]"></div>
                </div>
                <label x-show="plan.id" style="display:flex;gap:8px;align-items:center;font-size:13px;"><input type="checkbox" x-model="plan.activo"> Plan activo (visible en tu página de planes)</label>
            </div>
            <div class="modal-ft"><button type="button" class="btn btn-g" @click="modal = null">Cancelar</button><button class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Guardar plan'"></button></div>
        </form>
    </div>

    {{-- Modal inscribir --}}
    <div class="modal-bg" x-show="modal === 'inscribir'" x-cloak @keydown.escape.window="modal = null" @mousedown.self="modal = null">
        <form class="modal" style="max-width:440px;" @submit.prevent="inscribir()">
            <div class="modal-hd"><div><div class="modal-ttl">Inscribir cliente</div><div class="modal-sub">Se crea su primer cobro por pagar</div></div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button></div>
            <div class="modal-bd">
                <div class="field"><label class="lbl">Cliente</label>
                    <input class="inp" x-model="buscarCli" placeholder="Busca por nombre o celular" style="margin-bottom:6px;">
                    <select class="inp" x-model.number="ins.cliente_id" size="5" :class="{ err: errores.cliente_id }">
                        <template x-for="c in clientesFiltrados" :key="c.id"><option :value="c.id" x-text="c.nombre + (c.telefono ? ' · ' + c.telefono : '')"></option></template>
                    </select>
                    <div class="ferr" x-text="(errores.cliente_id || [])[0]"></div>
                </div>
                <div class="field"><label class="lbl">Plan</label>
                    <select class="inp" x-model.number="ins.plan_id"><template x-for="p in planes.filter(x => x.activo)" :key="p.id"><option :value="p.id" x-text="p.nombre + ' · ' + gh.soles(p.precio) + ' ' + periodos[p.periodicidad].toLowerCase()"></option></template></select>
                </div>
            </div>
            <div class="modal-ft"><button type="button" class="btn btn-g" @click="modal = null">Cancelar</button><button class="btn btn-p" :disabled="guardando">Inscribir</button></div>
        </form>
    </div>

    {{-- Modal registrar pago --}}
    <div class="modal-bg" x-show="modal === 'pago'" x-cloak @keydown.escape.window="modal = null" @mousedown.self="modal = null">
        <form class="modal" style="max-width:420px;" @submit.prevent="pagar()">
            <div class="modal-hd"><div><div class="modal-ttl">Registrar pago</div><div class="modal-sub" x-text="sel ? sel.cliente + ' · ' + sel.plan + ' · ' + gh.soles(sel.cobro.monto) : ''"></div></div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button></div>
            <div class="modal-bd">
                <div class="field"><label class="lbl">¿Cómo pagó?</label>
                    <select class="inp" x-model="pago.metodo">@foreach ($metodos as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="field" style="margin-bottom:0;"><label class="lbl">N.º de operación (opcional)</label><input class="inp" x-model="pago.referencia" maxlength="60"></div>
            </div>
            <div class="modal-ft"><button type="button" class="btn btn-g" @click="modal = null">Cancelar</button><button class="btn btn-p" :disabled="guardando">Confirmar pago</button></div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tabs{display:flex;gap:4px;padding:0 12px;border-bottom:1px solid var(--border);overflow-x:auto;}
    .tab{padding:12px 10px;font-size:13px;font-weight:600;color:var(--fg3);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;font-family:inherit;white-space:nowrap;}
    .tab.on{color:var(--accent);border-bottom-color:var(--accent);}
    .tab .n{font-size:11px;font-weight:600;color:var(--fg3);background:var(--surface2);border-radius:99px;padding:1px 7px;margin-left:4px;}
    .tab.on .n{background:var(--acl);color:var(--accent);}
    .acc { display:flex; gap:6px; justify-content:flex-end; align-items:center; flex-wrap:wrap; }
    .planes-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:12px; padding:16px; }
    .plan-c { border:1px solid var(--border); border-radius:14px; padding:14px; background:var(--surface); text-align:left; font:inherit; color:inherit; }
    .plan-c.off { opacity:.6; }
    .plan-c ul { margin:6px 0 0; padding-left:18px; font-size:13px; color:var(--fg2); }
    .plan-p { font-size:22px; font-weight:800; margin-top:2px; } .plan-p small { font-size:12px; font-weight:600; color:var(--fg3); }
    .plan-nuevo { display:flex; align-items:center; justify-content:center; gap:8px; border-style:dashed; color:var(--accent); font-weight:700; cursor:pointer; min-height:140px; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
</style>
@endpush

@push('scripts')
<script>
    window.membresias = function (planes, subs, clientes) {
        const base = @js(url('admin/membresias'));
        return {
            planes, subs, clientes, vista: 'subs', filtro: 'todas', modal: null, guardando: false, errores: {}, resaltado: null,
            estados: @js(\App\Models\SuscripcionCliente::ESTADOS), periodos: @js($periodos),
            filtros: [['todas', 'Todas'], ['activa', 'Activas'], ['pendiente', 'Por pagar'], ['vencida', 'Vencidas'], ['revisar', 'Captura enviada'], ['cancelada', 'Canceladas']],
            plan: {}, ins: {}, pago: {}, sel: null, buscarCli: '',
            init() { if (!this.planes.length) this.vista = 'planes'; },
            cumple(s, f) { return f === 'todas' || (f === 'revisar' ? s.cobro && s.cobro.estado === 'por_revisar' : s.estado === f); },
            contar(f) { return this.subs.filter(s => this.cumple(s, f)).length; },
            get filtradas() { return this.subs.filter(s => this.cumple(s, this.filtro)); },
            get clientesFiltrados() { const q = gh.norm(this.buscarCli.trim()); return this.clientes.filter(c => !q || gh.norm(c.nombre).includes(q) || (c.telefono || '').includes(q)).slice(0, 80); },
            urlCaptura(c) { return base + '/cobros/' + c.id + '/captura'; },
            abrirPlan(p) {
                this.errores = {};
                this.plan = p ? { ...p, beneficiosTxt: p.beneficios.join('\n') } : { id: null, nombre: '', precio: '', periodicidad: 'mensual', descripcion: '', beneficiosTxt: '', activo: true };
                this.modal = 'plan';
            },
            abrirInscribir() { this.errores = {}; this.buscarCli = ''; const p = this.planes.find(x => x.activo); this.ins = { cliente_id: '', plan_id: p ? p.id : '' }; this.modal = 'inscribir'; },
            abrirPago(s) { this.sel = s; this.pago = { metodo: 'yape', referencia: s.cobro.referencia || '' }; this.modal = 'pago'; },
            async enviar(url, metodo, body, ok) {
                this.guardando = true; this.errores = {};
                const r = await gh.api(url, { method: metodo, body });
                this.guardando = false;
                if (r.status === 422) { this.errores = r.data.errors || {}; if (!r.data.errors) Alpine.store('toasts').push(r.data.message, 'error'); return; }
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar', 'error'); return; }
                Alpine.store('toasts').push(r.data.message);
                this.modal = null;
                gh.ir(location.pathname, false);
            },
            guardarPlan() {
                const b = { ...this.plan, beneficios: (this.plan.beneficiosTxt || '').split('\n') };
                this.enviar(this.plan.id ? base + '/planes/' + this.plan.id : base + '/planes', this.plan.id ? 'PUT' : 'POST', b);
            },
            inscribir() { this.enviar(base + '/inscribir', 'POST', this.ins); },
            pagar() { this.enviar(base + '/cobros/' + this.sel.cobro.id + '/pagar', 'POST', this.pago); },
            rechazar(s) { if (confirm('¿Rechazar la captura de ' + s.cliente + '?')) this.enviar(base + '/cobros/' + s.cobro.id + '/rechazar', 'POST', {}); },
            cancelar(s) { if (confirm('¿Cancelar la suscripción de ' + s.cliente + '? No se generarán más cobros.')) this.enviar(base + '/' + s.id + '/cancelar', 'POST', {}); },
            reactivar(s) { this.enviar(base + '/' + s.id + '/reactivar', 'POST', {}); },
            copiar(t) { navigator.clipboard?.writeText(t).then(() => Alpine.store('toasts').push('Enlace copiado. Envíaselo al cliente para que pague.')); },
        };
    };
    gh.ready(() => Alpine.data('membresias', window.membresias));
</script>
@endpush
