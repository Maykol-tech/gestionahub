@extends('layouts.app')

@section('titulo', 'Servicios')
@section('subtitulo', 'Agenda de citas y catálogo de servicios')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .estado-sel { min-width:140px; padding:6px 8px; font-size:12px; font-weight:700; border-radius:99px; }
        .cita-pasada td { background:#FFFBEB; }
        .cita-fecha { font-weight:700; font-variant-numeric: tabular-nums; }
        .cita-hora { font-size:12px; color:var(--fg3); }
        .acciones { display:flex; gap:6px; justify-content:flex-end; align-items:center; }
        .campos-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media (max-width: 640px) { .campos-2 { grid-template-columns:1fr; } }
        .vista-sel { display:inline-flex; gap:3px; padding:3px; background:var(--surface); border:1px solid var(--border); border-radius:10px; }
        .vista-sel button { display:inline-flex; align-items:center; gap:5px; border:0; background:none; padding:6px 12px; border-radius:7px; font:inherit; font-size:13px; font-weight:600; color:var(--fg3); cursor:pointer; }
        .vista-sel button.on { background:var(--accent); color:#fff; }
        .cal-nav { display:flex; align-items:center; gap:8px; padding:12px 16px; flex-wrap:wrap; border-bottom:1px solid var(--border); }
        .cal-nav b { font-size:15px; margin-left:6px; }
        .cal-tot { margin-left:auto; font-size:12px; color:var(--fg3); }
        .cal-wrap { overflow-x:auto; }
        .cal { --alto:64px; display:grid; grid-template-columns:54px repeat(7, minmax(96px, 1fr)); min-width:740px; }
        .cal-esq, .cal-dia { position:sticky; top:0; background:var(--surface); border-bottom:1px solid var(--border); z-index:2; }
        .cal-dia { text-align:center; padding:8px 4px; font-size:12px; color:var(--fg3); border-left:1px solid var(--border); }
        .cal-dia b { display:block; font-size:17px; color:var(--fg); }
        .cal-dia.hoy b { display:flex; width:30px; height:30px; margin:1px auto 0; align-items:center; justify-content:center; border-radius:50%; background:var(--accent); color:#fff; }
        .cal-horas { grid-column:1; }
        .cal-h { height:var(--alto); font-size:11px; color:var(--fg3); text-align:right; padding:2px 8px 0 0; font-variant-numeric:tabular-nums; }
        .cal-col { position:relative; border-left:1px solid var(--border); }
        .cal-col.hoy { background:color-mix(in srgb, var(--accent) 4%, transparent); }
        .cal-slot { display:block; width:100%; height:var(--alto); border:0; border-top:1px solid var(--border); background:none; cursor:pointer; padding:0; }
        .cal-slot:hover { background:color-mix(in srgb, var(--accent) 8%, transparent); }
        .cal-cita { position:absolute; z-index:1; border:0; border-left:3px solid; border-radius:7px; padding:3px 6px; text-align:left; font:inherit; font-size:11px; overflow:hidden; cursor:pointer; line-height:1.3; box-shadow:0 1px 2px rgba(0,0,0,.08); }
        .cal-cita b { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .cal-cita span { display:block; opacity:.85; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .cal-cita:hover { filter:brightness(.97); z-index:3; }
        .cal-off { opacity:.55; text-decoration:line-through; }
        .cal-ayuda { font-size:12px; color:var(--fg3); padding:10px 16px; border-top:1px solid var(--border); }
    </style>
@endpush

@section('contenido')
<div x-data="agenda(@js($citas), @js($servicios))" @crud-servicios.window="servicios = $event.detail.filter(s => s.activo)" @keydown.escape.window="cerrar()">

    {{-- Indicadores --}}
    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Citas hoy</div><div class="stat-val">{{ $stats['hoy'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Pendientes de confirmar</div><div class="stat-val">{{ $stats['pendientes'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Próximas citas</div><div class="stat-val">{{ $stats['proximas'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Ingresos del mes</div><div class="stat-val">S/ {{ number_format($stats['ingresos_mes'], 2) }}</div></div>
    </div>

    {{-- Agenda --}}
    <div class="panel fade-in" style="animation-delay:.12s">
        <div class="panel-hd">
            <div class="search" style="flex:1;min-width:180px;max-width:320px;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar cliente o servicio…" x-model="buscar" aria-label="Buscar cita">
            </div>
            <select class="inp" style="max-width:190px;" x-model="filtroEstado" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <template x-for="(e, k) in estados" :key="k"><option :value="k" x-text="e.nombre"></option></template>
            </select>
            <div class="vista-sel" role="tablist" aria-label="Cómo ver la agenda" style="margin-left:auto;">
                <button type="button" :class="{ on: vista === 'lista' }" @click="ponerVista('lista')">Lista</button>
                <button type="button" :class="{ on: vista === 'semana' }" @click="ponerVista('semana')"><x-icon name="calendar" size="13" /> Semana</button>
            </div>
            <button class="btn btn-p" type="button" @click="servicios.length ? abrirNueva() : $store.toasts.push('Primero crea un servicio en el catálogo de abajo', 'info')" :class="{ 'btn-off': !servicios.length }">
                <x-icon name="plus" size="15" stroke="2.4" /> Nueva cita
            </button>
        </div>

        {{-- Vista de calendario semanal --}}
        <div x-show="vista === 'semana'" x-cloak>
            <div class="cal-nav">
                <button type="button" class="icon-btn sm" @click="moverSemana(-1)" aria-label="Semana anterior"><x-icon name="chev-l" size="15" /></button>
                <button type="button" class="btn btn-g btn-sm" @click="irHoy()">Hoy</button>
                <button type="button" class="icon-btn sm" @click="moverSemana(1)" aria-label="Semana siguiente"><x-icon name="chev-r" size="15" /></button>
                <b x-text="tituloSemana"></b>
                <span class="cal-tot" x-text="citasSemana + (citasSemana === 1 ? ' cita' : ' citas') + ' esta semana'"></span>
            </div>
            <div class="cal-wrap">
                <div class="cal" :style="'--filas:' + horas.length">
                    <div class="cal-esq"></div>
                    <template x-for="d in dias" :key="d.fecha">
                        <div class="cal-dia" :class="{ hoy: d.hoy }"><span x-text="d.nombre"></span><b x-text="d.num"></b></div>
                    </template>
                    <div class="cal-horas">
                        <template x-for="h in horas" :key="h"><div class="cal-h" x-text="h + ':00'"></div></template>
                    </div>
                    <template x-for="d in dias" :key="'col' + d.fecha">
                        <div class="cal-col" :class="{ hoy: d.hoy }">
                            <template x-for="h in horas" :key="h">
                                <button type="button" class="cal-slot" @click="nuevaEn(d.fecha, h)" :title="'Agendar el ' + d.nombre + ' ' + d.num + ' a las ' + h + ':00'"></button>
                            </template>
                            <template x-for="c in citasDelDia(d.fecha)" :key="c.id">
                                <button type="button" class="cal-cita" @click="abrirEditar(c)"
                                        :style="c.estilo + ';background:' + estados[c.estado].fondo + ';color:' + estados[c.estado].color + ';border-color:' + estados[c.estado].color"
                                        :class="{ 'cal-off': ['cancelada', 'no_asistio'].includes(c.estado) }"
                                        :title="c.hora + ' ' + c.cliente + ' · ' + c.servicio + ' (' + estados[c.estado].nombre + ')'">
                                    <b x-text="c.hora + ' ' + c.cliente"></b><span x-text="c.servicio"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            <div class="cal-ayuda">Toca un espacio libre para agendar a esa hora, o una cita para editarla.</div>
        </div>

        <div class="tbl-wrap" x-show="vista === 'lista' && filtradas.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th class="num">Precio</th>
                        <th>Estado</th>
                        <th style="width:130px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(c, i) in filtradas" :key="c.id">
                        <tr class="row-in" :class="{ 'cita-pasada': c.pasada }" :style="'--d:' + (i * 35) + 'ms'">
                            <td>
                                <div class="cita-fecha" x-text="c.fecha"></div>
                                <div class="cita-hora" x-text="c.hora + ' – ' + c.hora_fin + ' · ' + c.duracion_min + ' min'"></div>
                            </td>
                            <td>
                                <div style="font-weight:600;" x-text="c.cliente"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="c.telefono || 'Sin teléfono'"></div>
                            </td>
                            <td x-text="c.servicio"></td>
                            <td class="num" x-text="'S/ ' + Number(c.precio).toFixed(2)"></td>
                            <td>
                                <select class="inp estado-sel"
                                        :style="'background:' + estados[c.estado].fondo + ';color:' + estados[c.estado].color"
                                        :value="c.estado"
                                        @change="cambiarEstado(c, $event.target.value)"
                                        :aria-label="'Estado de la cita de ' + c.cliente">
                                    <template x-for="(e, k) in estados" :key="k">
                                        <option :value="k" :selected="k === c.estado" x-text="e.nombre"></option>
                                    </template>
                                </select>
                            </td>
                            <td>
                                <div class="acciones">
                                    <button class="btn btn-g btn-sm" type="button" @click="abrirEditar(c)">Editar</button>
                                    <button class="icon-btn sm" type="button" @click="eliminar(c)" aria-label="Eliminar cita"><x-icon name="x" size="14" /></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="empty" x-show="vista === 'lista' && !filtradas.length" x-cloak>
            <x-icon name="wrench" size="34" />
            <div style="font-weight:600;color:var(--fg2);" x-text="citas.length ? 'Ninguna cita coincide con tu búsqueda' : 'Aún no hay citas agendadas'"></div>
            <div style="font-size:13px;color:var(--fg3);max-width:420px;text-align:center;">
                La agenda muestra el último mes y los próximos dos meses. Crea una cita con el botón Nueva cita.
            </div>
        </div>
    </div>

    {{-- Catalogo de servicios (mismo CRUD que el resto de modulos) --}}
    <h2 style="font-size:15px;font-weight:700;margin:24px 0 10px;">Catálogo de servicios</h2>
    @include('admin.crud', ['cfg' => $cfgServicios])

    {{-- Ventana: crear o editar cita --}}
    <div class="modal-bg" x-show="modal === 'cita'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" @submit.prevent="guardar()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="plus" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="editando ? 'Editar cita' : 'Nueva cita'"></div>
                    <div class="modal-sub">No se permiten dos citas en el mismo horario</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="c-servicio">Servicio *</label>
                    <select class="inp" id="c-servicio" x-model="form.servicio_id" @change="elegirServicio()" :class="{ err: errores.servicio_id }">
                        <template x-for="s in servicios" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template>
                    </select>
                    <div class="ferr" x-show="errores.servicio_id" x-text="errores.servicio_id ? errores.servicio_id[0] : ''"></div>
                </div>

                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="c-nombre">Cliente *</label>
                        <input class="inp" id="c-nombre" maxlength="120" x-model="form.cliente_nombre" :class="{ err: errores.cliente_nombre }">
                        <div class="ferr" x-show="errores.cliente_nombre" x-text="errores.cliente_nombre ? errores.cliente_nombre[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="c-telefono">Teléfono</label>
                        <input class="inp" id="c-telefono" maxlength="30" x-model="form.cliente_telefono" :class="{ err: errores.cliente_telefono }">
                        <div class="ferr" x-show="errores.cliente_telefono" x-text="errores.cliente_telefono ? errores.cliente_telefono[0] : ''"></div>
                    </div>
                </div>

                <div class="field">
                    <label class="lbl" for="c-inicio">Fecha y hora *</label>
                    <input class="inp" id="c-inicio" type="datetime-local" x-model="form.inicio" :class="{ err: errores.inicio }">
                    <div class="ferr" x-show="errores.inicio" x-text="errores.inicio ? errores.inicio[0] : ''"></div>
                </div>

                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="c-duracion">Duración (minutos) *</label>
                        <input class="inp" id="c-duracion" type="number" min="5" max="1440" step="5" x-model.number="form.duracion_min" :class="{ err: errores.duracion_min }">
                        <div class="ferr" x-show="errores.duracion_min" x-text="errores.duracion_min ? errores.duracion_min[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="c-precio">Precio (S/)</label>
                        <input class="inp" id="c-precio" type="number" min="0" step="0.01" x-model.number="form.precio" :class="{ err: errores.precio }">
                        <div class="ferr" x-show="errores.precio" x-text="errores.precio ? errores.precio[0] : ''"></div>
                    </div>
                </div>

                <div class="field">
                    <label class="lbl" for="c-notas">Notas</label>
                    <textarea class="inp" id="c-notas" rows="2" maxlength="500" x-model="form.notas"></textarea>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Guardar cita'"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.agenda = function (citas, servicios) {
        const estados = {
            pendiente: { nombre: 'Pendiente', color: '#B45309', fondo: '#FEF3C7' },
            confirmada: { nombre: 'Confirmada', color: '#1D4ED8', fondo: '#DBEAFE' },
            completada: { nombre: 'Completada', color: '#15803D', fondo: '#DCFCE7' },
            no_asistio: { nombre: 'No asistió', color: '#B91C1C', fondo: '#FEE2E2' },
            cancelada: { nombre: 'Cancelada', color: '#475569', fondo: '#E2E8F0' },
        };
        const base = @js(route('admin.servicios.citas.guardar'));
        const ymd = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const lunesDe = d => { const x = new Date(d.getFullYear(), d.getMonth(), d.getDate()); x.setDate(x.getDate() - ((x.getDay() + 6) % 7)); return x; };

        // Hora local aproximada para una cita nueva: la siguiente hora en punto.
        const proximaHora = () => {
            const d = new Date();
            d.setMinutes(0, 0, 0);
            d.setHours(d.getHours() + 1);
            return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        };
        const vacio = () => ({
            servicio_id: servicios[0] ? servicios[0].id : '',
            cliente_nombre: '', cliente_telefono: '', inicio: proximaHora(),
            duracion_min: servicios[0] ? servicios[0].duracion_min || 30 : 30,
            precio: servicios[0] ? servicios[0].precio : 0,
            notas: '',
        });

        return {
            citas, servicios, estados,
            buscar: '', filtroEstado: '', modal: null, editando: null,
            form: vacio(), errores: {}, guardando: false,
            vista: (() => { try { return localStorage.getItem('gh-agenda-vista') || 'lista'; } catch (e) { return 'lista'; } })(),
            semana: lunesDe(new Date()),
            altoHora: 64,

            ponerVista(v) { this.vista = v; try { localStorage.setItem('gh-agenda-vista', v); } catch (e) {} },
            moverSemana(n) { const d = new Date(this.semana); d.setDate(d.getDate() + 7 * n); this.semana = d; },
            irHoy() { this.semana = lunesDe(new Date()); },
            get dias() {
                const nombres = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                const hoy = ymd(new Date());
                return nombres.map((nombre, i) => {
                    const d = new Date(this.semana); d.setDate(d.getDate() + i);
                    return { fecha: ymd(d), nombre, num: d.getDate(), hoy: ymd(d) === hoy };
                });
            },
            get tituloSemana() {
                const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
                const a = new Date(this.semana), b = new Date(this.semana); b.setDate(b.getDate() + 6);
                return a.getDate() + (a.getMonth() !== b.getMonth() ? ' ' + meses[a.getMonth()] : '') + ' – ' + b.getDate() + ' ' + meses[b.getMonth()] + ' ' + b.getFullYear();
            },
            get delaSemana() {
                const fechas = this.dias.map(d => d.fecha);
                return this.filtradas.filter(c => fechas.includes(c.inicio_local.slice(0, 10)));
            },
            get citasSemana() { return this.delaSemana.filter(c => c.estado !== 'cancelada').length; },
            get horas() {
                let ini = 8, fin = 20;
                this.delaSemana.forEach(c => {
                    const h = parseInt(c.inicio_local.slice(11, 13), 10);
                    ini = Math.min(ini, h);
                    fin = Math.max(fin, Math.ceil(h + (parseInt(c.inicio_local.slice(14, 16), 10) + c.duracion_min) / 60));
                });
                fin = Math.min(fin, 24);
                return Array.from({ length: fin - ini }, (_, i) => ini + i);
            },
            citasDelDia(fecha) {
                const h0 = this.horas[0];
                const lista = this.delaSemana.filter(c => c.inicio_local.slice(0, 10) === fecha)
                    .map(c => {
                        const ini = parseInt(c.inicio_local.slice(11, 13), 10) * 60 + parseInt(c.inicio_local.slice(14, 16), 10);
                        return { ...c, _ini: ini, _fin: ini + Math.max(15, c.duracion_min) };
                    })
                    .sort((a, b) => a._ini - b._ini);
                // Citas que se cruzan van lado a lado.
                const carriles = [];
                lista.forEach(c => {
                    let k = carriles.findIndex(fin => fin <= c._ini);
                    if (k === -1) { k = carriles.length; carriles.push(0); }
                    carriles[k] = c._fin; c._carril = k;
                });
                const n = Math.max(1, carriles.length);
                return lista.map(c => ({
                    ...c,
                    estilo: 'top:' + ((c._ini - h0 * 60) / 60 * this.altoHora) + 'px;height:' + Math.max(34, (c._fin - c._ini) / 60 * this.altoHora - 2) + 'px;'
                        + 'left:calc(' + (c._carril * 100 / n) + '% + 2px);width:calc(' + (100 / n) + '% - 4px)',
                }));
            },
            nuevaEn(fecha, h) {
                if (!this.servicios.length) { Alpine.store('toasts').push('Primero crea un servicio en el catálogo de abajo', 'info'); return; }
                this.abrirNueva();
                this.form.inicio = fecha + 'T' + String(h).padStart(2, '0') + ':00';
            },

            get filtradas() {
                const q = gh.norm(this.buscar);
                return this.citas.filter(c =>
                    (!this.filtroEstado || c.estado === this.filtroEstado) &&
                    (!q || gh.norm(c.cliente).includes(q) || gh.norm(c.servicio).includes(q))
                );
            },

            abrirNueva() {
                this.editando = null; this.form = vacio(); this.errores = {}; this.modal = 'cita';
                const s0 = this.servicios[0]; if (s0) Object.assign(this.form, { servicio_id: s0.id, duracion_min: s0.duracion_min || 30, precio: s0.precio });
            },
            abrirEditar(c) {
                this.editando = c.id; this.errores = {};
                this.form = {
                    servicio_id: c.servicio_id, cliente_nombre: c.cliente, cliente_telefono: c.telefono || '',
                    inicio: c.inicio_local, duracion_min: c.duracion_min, precio: c.precio, notas: c.notas || '',
                };
                this.modal = 'cita';
            },
            elegirServicio() {
                const s = this.servicios.find(x => x.id == this.form.servicio_id);
                if (s) { this.form.duracion_min = s.duracion_min || 30; this.form.precio = s.precio; }
            },
            cerrar() { this.modal = null; },

            async guardar() {
                this.guardando = true; this.errores = {};
                const url = this.editando ? base + '/' + this.editando : base;
                const r = await gh.api(url, { method: this.editando ? 'PUT' : 'POST', body: this.form });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.cerrar(); gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar la cita.', 'error'); }
            },

            async cambiarEstado(c, estado) {
                if (estado === c.estado) return;
                const r = await gh.api(base + '/' + c.id + '/estado', { method: 'PATCH', body: { estado } });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo cambiar el estado.', 'error'); gh.ir(location.pathname, false); }
            },

            async eliminar(c) {
                if (!confirm('¿Eliminar la cita de ' + c.cliente + ' del ' + c.fecha + ' a las ' + c.hora + '?')) return;
                const r = await gh.api(base + '/' + c.id, { method: 'DELETE' });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar la cita.', 'error'); }
            },
        };
    };
    gh.ready(() => Alpine.data('agenda', window.agenda));
</script>
@endpush
@endsection
