@extends('layouts.app')

@section('titulo', 'Publicidad')
@section('subtitulo', 'Campañas, gastos reales e impacto en ventas')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .estado-chip { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; white-space:nowrap; }
        .canal-chip { display:inline-block; font-size:11px; font-weight:600; padding:2px 8px; border-radius:6px; background:var(--surface-2, #F1F5F9); color:var(--fg2); }
        .barra { height:6px; border-radius:99px; background:#E2E8F0; overflow:hidden; margin-top:6px; min-width:110px; }
        .barra > span { display:block; height:100%; border-radius:99px; background:var(--brand, #2B6EF6); transition: width .6s var(--ease); }
        .barra.alerta > span { background:#B91C1C; }
        .acciones { display:flex; gap:6px; justify-content:flex-end; align-items:center; }
        .campos-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .campos-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
        .metricas-modal { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
        .metricas-modal div { background:var(--surface-2, #F6F8FB); border-radius:10px; padding:10px; text-align:center; }
        .metricas-modal b { display:block; font-size:16px; font-variant-numeric: tabular-nums; }
        .metricas-modal small { font-size:11px; color:var(--fg3); }
        .gasto-fila { display:flex; align-items:center; gap:10px; padding:8px 0; border-top:1px dashed var(--border); font-size:13px; }
        @media (max-width: 640px) { .campos-2, .campos-3, .metricas-modal { grid-template-columns:1fr; } }
    </style>
@endpush

@section('contenido')
<div x-data="publicidad(@js($campanas))" @keydown.escape.window="cerrar()">

    {{-- Indicadores --}}
    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Gasto real</div><div class="stat-val">S/ {{ number_format($stats['gastado'], 2) }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Ventas atribuidas</div><div class="stat-val">S/ {{ number_format($stats['ventas'], 2) }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">ROAS</div><div class="stat-val">{{ $stats['roas'] !== null ? number_format($stats['roas'], 2) . 'x' : '”' }}</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Alcance / conversiones</div><div class="stat-val">{{ number_format($stats['alcance']) }} / {{ number_format($stats['conversiones']) }}</div></div>
    </div>

    <div class="panel fade-in" style="animation-delay:.12s">
        <div class="panel-hd">
            <div class="search" style="flex:1;min-width:180px;max-width:320px;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar campaña¦" x-model="buscar" aria-label="Buscar campaña">
            </div>
            <button class="btn btn-p" type="button" style="margin-left:auto;" @click="abrirNueva()">
                <x-icon name="plus" size="15" stroke="2.4" /> Nueva campaña
            </button>
        </div>

        <div class="tbl-wrap" x-show="filtradas.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Campaña</th>
                        <th>Estado</th>
                        <th style="min-width:150px;">Gasto / presupuesto</th>
                        <th class="num">Alcance</th>
                        <th class="num">Clics</th>
                        <th class="num">Conv.</th>
                        <th class="num">ROAS</th>
                        <th style="width:190px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(c, i) in filtradas" :key="c.id">
                        <tr class="row-in" :style="'--d:' + (i * 35) + 'ms'">
                            <td>
                                <div style="font-weight:600;" x-text="c.nombre"></div>
                                <span class="canal-chip" x-text="c.canal_label + ' Â· ' + c.objetivo_label"></span>
                            </td>
                            <td>
                                <span class="estado-chip" :style="'background:' + estados[c.estado].fondo + ';color:' + estados[c.estado].color" x-text="estados[c.estado].nombre"></span>
                            </td>
                            <td>
                                <div style="font-size:13px;"><span x-text="money(c.gastado)"></span> <span style="color:var(--fg3);" x-text="'/ ' + money(c.presupuesto)"></span></div>
                                <div class="barra" :class="{ alerta: c.gastado > c.presupuesto }"><span :style="'width:' + c.progreso + '%'"></span></div>
                            </td>
                            <td class="num" x-text="c.alcance !== null ? formato(c.alcance) : '”'"></td>
                            <td class="num" x-text="c.clics !== null ? formato(c.clics) : '”'"></td>
                            <td class="num" x-text="c.conversiones !== null ? formato(c.conversiones) : '”'"></td>
                            <td class="num">
                                <strong x-text="c.roas !== null ? c.roas.toFixed(2) + 'x' : '”'"></strong>
                            </td>
                            <td>
                                <div class="acciones">
                                    <button class="btn btn-g btn-sm" type="button" @click="abrirGastos(c)">Gastos</button>
                                    <button class="btn btn-g btn-sm" type="button" @click="abrirEditar(c)">Editar</button>
                                    <button class="icon-btn sm" type="button" @click="eliminarCampana(c)" aria-label="Eliminar campaña"><x-icon name="x" size="14" /></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="empty" x-show="!filtradas.length" x-cloak>
            <x-icon name="activity" size="34" />
            <div style="font-weight:600;color:var(--fg2);" x-text="campanas.length ? 'Ninguna campaña coincide con tu búsqueda' : 'Aún no hay campañas'"></div>
            <div style="font-size:13px;color:var(--fg3);max-width:420px;text-align:center;">Crea una campaña, registra sus gastos y anota las ventas que genera para ver su retorno.</div>
        </div>
    </div>

    {{-- Ventana: crear o editar campaña --}}
    <div class="modal-bg" x-show="modal === 'campana'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" style="max-width:600px;" @submit.prevent="guardarCampana()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="activity" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="editando ? 'Editar campaña' : 'Nueva campaña'"></div>
                    <div class="modal-sub">El presupuesto se compara con los gastos que registres</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="p-nombre">Nombre de la campaña *</label>
                    <input class="inp" id="p-nombre" maxlength="120" x-model="f.nombre" :class="{ err: errores.nombre }">
                    <div class="ferr" x-show="errores.nombre" x-text="errores.nombre ? errores.nombre[0] : ''"></div>
                </div>
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="p-canal">Canal *</label>
                        <select class="inp" id="p-canal" x-model="f.canal">
                            @foreach ($canales as $clave => $nombre)
                                <option value="{{ $clave }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="lbl" for="p-objetivo">Objetivo *</label>
                        <select class="inp" id="p-objetivo" x-model="f.objetivo">
                            @foreach ($objetivos as $clave => $nombre)
                                <option value="{{ $clave }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="campos-3">
                    <div class="field">
                        <label class="lbl" for="p-presupuesto">Presupuesto (S/) *</label>
                        <input class="inp" id="p-presupuesto" type="number" min="0" step="0.01" x-model.number="f.inversion" :class="{ err: errores.inversion }">
                        <div class="ferr" x-show="errores.inversion" x-text="errores.inversion ? errores.inversion[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="p-inicio">Inicio *</label>
                        <input class="inp" id="p-inicio" type="date" x-model="f.fecha_inicio" :class="{ err: errores.fecha_inicio }">
                        <div class="ferr" x-show="errores.fecha_inicio" x-text="errores.fecha_inicio ? errores.fecha_inicio[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="p-fin">Fin</label>
                        <input class="inp" id="p-fin" type="date" x-model="f.fecha_fin" :class="{ err: errores.fecha_fin }">
                        <div class="ferr" x-show="errores.fecha_fin" x-text="errores.fecha_fin ? errores.fecha_fin[0] : ''"></div>
                    </div>
                </div>

                <p style="font-size:12px;font-weight:700;color:var(--fg2);margin:6px 0 8px;">Impacto (opcional, cópialo de la plataforma)</p>
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="p-alcance">Alcance (personas)</label>
                        <input class="inp" id="p-alcance" type="number" min="0" step="1" x-model.number="f.alcance">
                    </div>
                    <div class="field">
                        <label class="lbl" for="p-clics">Clics</label>
                        <input class="inp" id="p-clics" type="number" min="0" step="1" x-model.number="f.clics" :class="{ err: errores.clics }">
                        <div class="ferr" x-show="errores.clics" x-text="errores.clics ? errores.clics[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="p-conv">Conversiones (pedidos o citas)</label>
                        <input class="inp" id="p-conv" type="number" min="0" step="1" x-model.number="f.conversiones">
                    </div>
                    <div class="field">
                        <label class="lbl" for="p-ventas">Ventas atribuidas (S/)</label>
                        <input class="inp" id="p-ventas" type="number" min="0" step="0.01" x-model.number="f.ventas_atribuidas">
                    </div>
                </div>

                <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:6px 0 10px;">
                    <input type="checkbox" x-model="f.activo"> Campaña activa
                </label>
                <div class="field">
                    <label class="lbl" for="p-notas">Notas</label>
                    <textarea class="inp" id="p-notas" rows="2" maxlength="500" x-model="f.notas"></textarea>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando¦' : 'Guardar campaña'"></button>
            </div>
        </form>
    </div>

    {{-- Ventana: gastos de una campaña --}}
    <div class="modal-bg" x-show="modal === 'gastos'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <div class="modal" style="max-width:580px;">
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="activity" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="sel ? sel.nombre : ''"></div>
                    <div class="modal-sub" x-text="sel ? 'Presupuesto ' + money(sel.presupuesto) + ' Â· ' + sel.estado : ''"></div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd" x-show="sel">
                <div class="metricas-modal">
                    <div><b x-text="sel ? money(sel.gastado) : ''"></b><small>Gastado</small></div>
                    <div><b x-text="sel && sel.roas !== null ? sel.roas.toFixed(2) + 'x' : '”'"></b><small>ROAS</small></div>
                    <div><b x-text="sel && sel.cpa !== null ? money(sel.cpa) : '”'"></b><small>Costo por conversión</small></div>
                    <div><b x-text="sel && sel.ctr !== null ? sel.ctr + '%' : '”'"></b><small>CTR</small></div>
                    <div><b x-text="sel && sel.cpc !== null ? money(sel.cpc) : '”'"></b><small>Costo por clic</small></div>
                    <div><b x-text="sel && sel.ventas_atribuidas !== null ? money(sel.ventas_atribuidas) : '”'"></b><small>Ventas atribuidas</small></div>
                </div>

                <form @submit.prevent="registrarGasto()" novalidate>
                    <div class="campos-3" style="align-items:end;">
                        <div class="field">
                            <label class="lbl" for="g-fecha">Fecha *</label>
                            <input class="inp" id="g-fecha" type="date" x-model="gasto.fecha" :class="{ err: errGasto.fecha }">
                        </div>
                        <div class="field">
                            <label class="lbl" for="g-monto">Monto (S/) *</label>
                            <input class="inp" id="g-monto" type="number" min="0.01" step="0.01" x-model.number="gasto.monto" :class="{ err: errGasto.monto }">
                            <div class="ferr" x-show="errGasto.monto" x-text="errGasto.monto ? errGasto.monto[0] : ''"></div>
                        </div>
                        <button type="submit" class="btn btn-p" :disabled="guardando">Registrar gasto</button>
                    </div>
                    <div class="field">
                        <input class="inp" maxlength="120" placeholder="Concepto (opcional), por ejemplo: anuncio de fin de semana" x-model="gasto.concepto" aria-label="Concepto del gasto">
                    </div>
                </form>

                <div style="margin-top:14px;">
                    <template x-for="g in (sel ? sel.gastos : [])" :key="g.id">
                        <div class="gasto-fila">
                            <span style="color:var(--fg3);width:84px;" x-text="g.fecha"></span>
                            <span style="flex:1;" x-text="g.concepto || 'Gasto'"></span>
                            <strong x-text="money(g.monto)"></strong>
                            <button class="icon-btn sm" type="button" @click="eliminarGasto(g)" aria-label="Eliminar gasto"><x-icon name="x" size="14" /></button>
                        </div>
                    </template>
                    <div style="font-size:13px;color:var(--fg3);padding:8px 0;" x-show="sel && !sel.gastos.length" x-cloak>Aún no hay gastos registrados en esta campaña.</div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-p" @click="cerrar()">Listo</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.publicidad = window.publicidad || function (campanas) {
        const estados = {
            activa: { nombre: 'Activa', color: '#15803D', fondo: '#DCFCE7' },
            programada: { nombre: 'Programada', color: '#1D4ED8', fondo: '#DBEAFE' },
            pausada: { nombre: 'Pausada', color: '#475569', fondo: '#E2E8F0' },
            finalizada: { nombre: 'Finalizada', color: '#7C2D12', fondo: '#FFEDD5' },
        };
        const urlCampanas = @js(url('admin/publicidad/campanas'));
        const urlGastos = @js(url('admin/publicidad/gastos'));
        const hoy = () => { const d = new Date(); return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };
        const vacia = () => ({
            nombre: '', canal: 'facebook', objetivo: 'ventas', inversion: 0, fecha_inicio: hoy(), fecha_fin: '',
            alcance: null, clics: null, conversiones: null, ventas_atribuidas: null, activo: true, notas: '',
        });
        const redondear = (n) => Math.round(n * 100) / 100;

        return {
            campanas, estados,
            buscar: '', modal: null, editando: null, sel: null, cambios: false,
            f: vacia(), errores: {}, guardando: false,
            gasto: { fecha: hoy(), monto: '', concepto: '' }, errGasto: {},

            get filtradas() {
                const q = gh.norm(this.buscar);
                return q ? this.campanas.filter(c => gh.norm(c.nombre).includes(q) || gh.norm(c.canal_label).includes(q)) : this.campanas;
            },
            formato(n) { return Number(n || 0).toLocaleString('es-PE'); },
            money(n) { return 'S/ ' + Number(n || 0).toFixed(2); },

            // Mismo calculo que App\Support\Publicidad::metricas, para actualizar la ventana sin recargar.
            recalcular(c) {
                const g = redondear(c.gastos.reduce((a, x) => a + Number(x.monto), 0));
                const ventas = Number(c.ventas_atribuidas) || 0;
                c.gastado = g;
                c.progreso = c.presupuesto > 0 ? Math.min(100, Math.round(g / c.presupuesto * 100)) : 0;
                c.ctr = c.alcance > 0 ? redondear((c.clics || 0) / c.alcance * 100) : null;
                c.cpc = c.clics > 0 && g > 0 ? redondear(g / c.clics) : null;
                c.cpa = c.conversiones > 0 && g > 0 ? redondear(g / c.conversiones) : null;
                c.roas = g > 0 ? redondear(ventas / g) : null;
            },

            abrirNueva() { this.editando = null; this.errores = {}; this.f = vacia(); this.modal = 'campana'; },
            abrirEditar(c) {
                this.editando = c.id; this.errores = {};
                this.f = {
                    nombre: c.nombre, canal: c.canal, objetivo: c.objetivo, inversion: c.presupuesto,
                    fecha_inicio: c.inicio, fecha_fin: c.fin || '', alcance: c.alcance, clics: c.clics,
                    conversiones: c.conversiones, ventas_atribuidas: c.ventas_atribuidas, activo: c.activo, notas: c.notas || '',
                };
                this.modal = 'campana';
            },
            abrirGastos(c) {
                this.sel = c; this.errGasto = {}; this.gasto = { fecha: hoy(), monto: '', concepto: '' }; this.modal = 'gastos';
            },
            cerrar() {
                const recargar = this.cambios;
                this.modal = null; this.cambios = false;
                if (recargar) gh.ir(location.pathname, false);
            },

            async guardarCampana() {
                this.guardando = true; this.errores = {};
                const url = this.editando ? urlCampanas + '/' + this.editando : urlCampanas;
                const r = await gh.api(url, { method: this.editando ? 'PUT' : 'POST', body: this.f });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.modal = null; gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar la campaña.', 'error'); }
            },

            async registrarGasto() {
                this.guardando = true; this.errGasto = {};
                const r = await gh.api(urlCampanas + '/' + this.sel.id + '/gastos', { method: 'POST', body: this.gasto });
                this.guardando = false;
                if (r.ok) {
                    this.sel.gastos.unshift(r.data.gasto);
                    this.recalcular(this.sel);
                    this.gasto = { fecha: hoy(), monto: '', concepto: '' };
                    this.cambios = true;
                    Alpine.store('toasts').push(r.data.message, r.data.message.includes('superaste') ? 'error' : 'success');
                } else if (r.status === 422 && r.data.errors) { this.errGasto = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo registrar el gasto.', 'error'); }
            },

            async eliminarGasto(g) {
                if (!confirm('Â¿Eliminar este gasto de ' + this.money(g.monto) + '?')) return;
                const r = await gh.api(urlGastos + '/' + g.id, { method: 'DELETE' });
                if (r.ok) {
                    this.sel.gastos = this.sel.gastos.filter(x => x.id !== g.id);
                    this.recalcular(this.sel);
                    this.cambios = true;
                    Alpine.store('toasts').push(r.data.message);
                } else { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar el gasto.', 'error'); }
            },

            async eliminarCampana(c) {
                if (!confirm('Â¿Eliminar la campaña Â«' + c.nombre + 'Â» y todos sus gastos?')) return;
                const r = await gh.api(urlCampanas + '/' + c.id, { method: 'DELETE' });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar la campaña.', 'error'); }
            },
        };
    };
    gh.ready(() => Alpine.data('publicidad', window.publicidad));
</script>
@endpush
@endsection
