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
    </style>
@endpush

@section('contenido')
<div x-data="agenda(@js($citas), @js($servicios))" @keydown.escape.window="cerrar()">

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
            <button class="btn btn-p" type="button" style="margin-left:auto;" @click="abrirNueva()" :disabled="!servicios.length">
                <x-icon name="plus" size="15" stroke="2.4" /> Nueva cita
            </button>
        </div>

        <div class="tbl-wrap" x-show="filtradas.length" x-cloak>
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

        <div class="empty" x-show="!filtradas.length" x-cloak>
            <x-icon name="wrench" size="34" />
            <div style="font-weight:600;color:var(--fg2);" x-text="citas.length ? 'Ninguna cita coincide con tu búsqueda' : 'Aún no hay citas agendadas'"></div>
            <div style="font-size:13px;color:var(--fg3);max-width:420px;text-align:center;">
                La agenda muestra los últimos 7 días y los próximos 30. Crea una cita con el botón Nueva cita.
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
    window.agenda = window.agenda || function (citas, servicios) {
        const estados = {
            pendiente: { nombre: 'Pendiente', color: '#B45309', fondo: '#FEF3C7' },
            confirmada: { nombre: 'Confirmada', color: '#1D4ED8', fondo: '#DBEAFE' },
            completada: { nombre: 'Completada', color: '#15803D', fondo: '#DCFCE7' },
            no_asistio: { nombre: 'No asistió', color: '#B91C1C', fondo: '#FEE2E2' },
            cancelada: { nombre: 'Cancelada', color: '#475569', fondo: '#E2E8F0' },
        };
        const base = @js(route('admin.servicios.citas.guardar'));

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

            get filtradas() {
                const q = gh.norm(this.buscar);
                return this.citas.filter(c =>
                    (!this.filtroEstado || c.estado === this.filtroEstado) &&
                    (!q || gh.norm(c.cliente).includes(q) || gh.norm(c.servicio).includes(q))
                );
            },

            abrirNueva() {
                this.editando = null; this.form = vacio(); this.errores = {}; this.modal = 'cita';
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
