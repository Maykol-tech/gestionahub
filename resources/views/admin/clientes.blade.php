@extends('layouts.app')

@section('titulo', 'Clientes')

@section('subtitulo')
    <span x-data x-text="$store.clientes.total + ' registrados'">{{ count($clientes) }} registrados</span>
@endsection

@section('acciones')
    <button class="btn btn-g" x-data @click="$dispatch('exportar-clientes')"><x-icon name="download" size="15" /> Exportar</button>
    <button class="btn btn-p" x-data @click="$dispatch('nuevo-cliente')"><x-icon name="user-plus" size="15" stroke="2.2" /> Nuevo cliente</button>
@endsection

@section('contenido')
<div x-data="clientesPage(@js($clientes))" @nuevo-cliente.window="abrir()" @exportar-clientes.window="exportar()">

    {{-- Indicadores --}}
    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Total clientes <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="users" size="14" /></span></div>
            <div class="stat-val" x-text="clientes.length"></div>
            <div class="stat-ch neutral">En tu cartera</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Activos <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="check-circle" size="14" /></span></div>
            <div class="stat-val" x-text="contar('activos')"></div>
            <div class="stat-ch up" x-text="porcentaje(contar('activos')) + '% del total'"></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Frecuentes <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="heart" size="14" /></span></div>
            <div class="stat-val" x-text="contar('frecuentes')"></div>
            <div class="stat-ch neutral">3 pedidos o más</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Nuevos <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="user-plus" size="14" /></span></div>
            <div class="stat-val" x-text="contar('nuevos')"></div>
            <div class="stat-ch neutral">Últimos 7 días</div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="panel fade-in" style="animation-delay:.18s">
        <div class="panel-hd">
            <div class="chips">
                <template x-for="f in filtros" :key="f.clave">
                    <button class="chip" :class="{ 'on': filtro === f.clave }" @click="filtro = f.clave; pagina = 1">
                        <span x-text="f.texto"></span><span class="n" x-text="contar(f.clave)"></span>
                    </button>
                </template>
            </div>
            <div class="search" style="margin-left:auto;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar cliente…" x-model="buscar" @input="pagina = 1">
            </div>
        </div>
        <div class="loadbar" x-show="cargando" x-cloak></div>

        <div class="tbl-wrap" x-show="filtrados.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="sortable" :class="{ sorted: orden.campo === 'nombre' }" @click="ordenar('nombre')">Cliente <x-icon name="sort" size="11" /></th>
                        <th>Teléfono</th>
                        <th>Distrito</th>
                        <th class="sortable num" :class="{ sorted: orden.campo === 'pedidos' }" @click="ordenar('pedidos')">Pedidos <x-icon name="sort" size="11" /></th>
                        <th class="sortable num" :class="{ sorted: orden.campo === 'total' }" @click="ordenar('total')">Total gastado <x-icon name="sort" size="11" /></th>
                        <th>Último pedido</th>
                        <th>Estado</th>
                        <th style="width:52px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(c, i) in visibles" :key="c.id">
                        <tr :class="{ 'row-new': c.id === resaltado , 'row-in': entrada }" :style="entrada ? '--d:' + (i * 50) + 'ms' : ''">
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="cav" :class="{ 'cav-off': !c.activo }" x-text="gh.iniciales(c.nombre)"></div>
                                    <div style="min-width:0;">
                                        <div style="font-weight:600;" x-text="c.nombre"></div>
                                        <div style="font-size:11px;color:var(--fg3);" x-text="c.email || (c.desde ? 'Cliente desde ' + c.desde : '')"></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-variant-numeric:tabular-nums;" x-text="c.telefono || '—'"></td>
                            <td style="color:var(--fg2);" x-text="c.distrito || '—'"></td>
                            <td class="num"><span class="badge" :class="c.pedidos >= 10 ? 'b-g' : (c.pedidos >= 3 ? 'b-a' : 'b-n')" x-text="c.pedidos"></span></td>
                            <td class="num" style="font-weight:700;" x-text="gh.soles(c.total)"></td>
                            <td :style="c.dias_sin_comprar > 14 ? 'color:var(--red)' : 'color:var(--fg3)'" x-text="c.ultimo || 'Sin pedidos'"></td>
                            <td>
                                <span class="badge" :class="!c.activo ? 'b-r' : (c.nuevo ? 'b-g' : 'b-ok')">
                                    <span class="dot"></span><span x-text="!c.activo ? 'Inactivo' : (c.nuevo ? 'Nuevo' : 'Activo')"></span>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <a class="btn btn-wa btn-sm" x-show="c.telefono" :href="'https://wa.me/' + whatsapp(c.telefono)" target="_blank" rel="noopener" title="Escribir por WhatsApp"><x-icon name="message" size="13" /></a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="empty" x-show="!filtrados.length" x-cloak>
            <x-icon name="users" size="34" />
            <div style="font-weight:600;color:var(--fg2);" x-text="clientes.length ? 'Ningún cliente coincide con tu búsqueda' : 'Aún no tienes clientes registrados'"></div>
            <button class="btn btn-p" style="margin-top:6px;" x-show="!clientes.length" @click="abrir()"><x-icon name="user-plus" size="15" /> Registrar el primero</button>
        </div>

        <div class="panel-ft" x-show="filtrados.length" x-cloak>
            <span x-text="'Mostrando ' + desde + '–' + hasta + ' de ' + filtrados.length + ' clientes'"></span>
            <div class="pager">
                <button @click="pagina--; animar()" :disabled="pagina === 1" aria-label="Anterior"><x-icon name="chev-l" size="14" /></button>
                <template x-for="n in paginas" :key="n">
                    <button :class="{ on: n === pagina }" @click="pagina = n; animar()" x-text="n"></button>
                </template>
                <button @click="pagina++; animar()" :disabled="pagina === paginas" aria-label="Siguiente"><x-icon name="chev-r" size="14" /></button>
            </div>
        </div>
    </div>

    {{-- Modal: Nuevo cliente --}}
    <div class="modal-bg" x-show="modal" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @keydown.escape.window="cerrar()" @mousedown.self="cerrar()">
        <form class="modal" @submit.prevent="guardar()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="user-plus" size="18" /></div>
                <div>
                    <div class="modal-ttl">Nuevo cliente</div>
                    <div class="modal-sub">Se guardará en tu cartera de clientes</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>

            <div class="modal-bd">
                <div class="form-sec">Datos del cliente</div>
                <div class="field">
                    <label class="lbl">Nombre completo <span class="req">*</span></label>
                    <input class="inp" :class="{ err: errores.nombre }" x-model="form.nombre" x-ref="primero" maxlength="120" placeholder="Ej: María Torres">
                    <div class="ferr" x-show="errores.nombre" x-text="errores.nombre?.[0]"></div>
                </div>
                <div class="grid2">
                    <div class="field">
                        <label class="lbl">Teléfono / WhatsApp</label>
                        <input class="inp" :class="{ err: errores.telefono }" x-model="form.telefono" maxlength="20" placeholder="987 654 321" inputmode="tel">
                        <div class="ferr" x-show="errores.telefono" x-text="errores.telefono?.[0]"></div>
                    </div>
                    <div class="field">
                        <label class="lbl">Correo</label>
                        <input class="inp" :class="{ err: errores.email }" x-model="form.email" type="email" maxlength="120" placeholder="cliente@correo.com">
                        <div class="ferr" x-show="errores.email" x-text="errores.email?.[0]"></div>
                    </div>
                    <div class="field">
                        <label class="lbl">DNI</label>
                        <input class="inp" :class="{ err: errores.dni }" x-model="form.dni" maxlength="12" placeholder="12345678" inputmode="numeric">
                        <div class="ferr" x-show="errores.dni" x-text="errores.dni?.[0]"></div>
                    </div>
                    <div class="field">
                        <label class="lbl">¿Cómo llegó?</label>
                        <select class="inp" x-model="form.fuente">
                            <option value="manual">Registro manual</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="referido">Referido</option>
                            <option value="landing">Página web</option>
                        </select>
                    </div>
                </div>
                <div class="form-sec" style="margin-top:4px;">Ubicación y notas</div>
                <div class="grid2">
                    <div class="field">
                        <label class="lbl">Distrito</label>
                        <input class="inp" x-model="form.distrito" maxlength="80" placeholder="San Martín de Porres">
                    </div>
                    <div class="field">
                        <label class="lbl">Dirección</label>
                        <input class="inp" x-model="form.direccion" maxlength="255" placeholder="Jr. Las Flores 234">
                    </div>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label class="lbl">Notas</label>
                    <textarea class="inp" x-model="form.notas" rows="2" maxlength="1000" placeholder="Preferencias, alergias, horarios…"></textarea>
                </div>
                <div class="ferr" x-show="errorGeneral" x-text="errorGeneral" style="margin-top:10px;"></div>
            </div>

            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando">
                    <x-icon name="loader" size="15" class="spin" x-show="guardando" />
                    <x-icon name="check" size="15" stroke="2.4" x-show="!guardando" />
                    <span x-text="guardando ? 'Guardando…' : 'Guardar cliente'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .cav{width:34px;height:34px;border-radius:50%;background:var(--acl);color:var(--accent);font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform .2s var(--ease);}
    .cav-off{background:var(--redl);color:var(--red);}
    .tbl tbody tr:hover .cav{transform:scale(1.06);}
    @media (max-width:860px){ .panel-hd .search{width:100%;margin-left:0!important;} }
</style>
@endpush

@push('scripts')
<script>
    gh.ready(() => {
        Alpine.store('clientes', { total: 0 });

        Alpine.data('clientesPage', (inicial) => ({
            clientes: inicial,
            filtro: 'todos', buscar: '', pagina: 1, porPagina: 10, entrada: true, _tEntrada: null,
            orden: { campo: null, dir: 1 },
            filtros: [
                { clave: 'todos', texto: 'Todos' },
                { clave: 'frecuentes', texto: 'Frecuentes' },
                { clave: 'inactivos', texto: 'Inactivos' },
                { clave: 'nuevos', texto: 'Nuevos' },
            ],
            modal: false, guardando: false, cargando: false, resaltado: null,
            form: {}, errores: {}, errorGeneral: '',

            animar() { this.entrada = true; clearTimeout(this._tEntrada); this._tEntrada = setTimeout(() => this.entrada = false, 900); },
            init() {
                this.animar();
                this.resetForm();
                this.$watch('clientes', v => Alpine.store('clientes').total = v.length);
                Alpine.store('clientes').total = this.clientes.length;
                if (new URLSearchParams(location.search).has('nuevo')) this.abrir();
            },
            resetForm() {
                this.form = { nombre: '', telefono: '', email: '', dni: '', fuente: 'manual', distrito: '', direccion: '', notas: '' };
                this.errores = {}; this.errorGeneral = '';
            },
            cumple(c, clave) {
                if (clave === 'frecuentes') return c.pedidos >= 3;
                if (clave === 'inactivos') return !c.activo;
                if (clave === 'activos') return c.activo;
                if (clave === 'nuevos') return c.nuevo;
                return true;
            },
            contar(clave) { return this.clientes.filter(c => this.cumple(c, clave)).length; },
            porcentaje(n) { return this.clientes.length ? Math.round(n * 100 / this.clientes.length) : 0; },
            ordenar(campo) {
                this.orden = { campo, dir: this.orden.campo === campo ? -this.orden.dir : (campo === 'nombre' ? 1 : -1) };
            },
            get filtrados() {
                const q = gh.norm(this.buscar.trim());
                let lista = this.clientes.filter(c => this.cumple(c, this.filtro) &&
                    (!q || [c.nombre, c.telefono, c.email, c.distrito].some(v => gh.norm(v).includes(q))));
                const { campo, dir } = this.orden;
                if (campo) lista = [...lista].sort((a, b) => (campo === 'nombre' ? a.nombre.localeCompare(b.nombre) : a[campo] - b[campo]) * dir);
                return lista;
            },
            get paginas() { return Math.max(1, Math.ceil(this.filtrados.length / this.porPagina)); },
            get desde() { return (this.pagina - 1) * this.porPagina + 1; },
            get hasta() { return Math.min(this.pagina * this.porPagina, this.filtrados.length); },
            get visibles() { return this.filtrados.slice(this.desde - 1, this.hasta); },
            whatsapp(tel) {
                const n = (tel || '').replace(/\D/g, '');
                return n.length === 9 ? '51' + n : n;
            },
            exportar() {
                gh.csv('clientes.csv', [['Nombre', 'Teléfono', 'Correo', 'Distrito', 'Pedidos', 'Total gastado', 'Último pedido', 'Estado'],
                    ...this.filtrados.map(c => [c.nombre, c.telefono, c.email, c.distrito, c.pedidos, c.total, c.ultimo || '', c.activo ? 'Activo' : 'Inactivo'])]);
                Alpine.store('toasts').push('Se descargó clientes.csv', 'info');
            },
            abrir() {
                this.resetForm();
                this.modal = true;
                this.$nextTick(() => setTimeout(() => this.$refs.primero?.focus(), 80));
            },
            cerrar() { if (!this.guardando) this.modal = false; },

            async guardar() {
                this.guardando = true; this.errores = {}; this.errorGeneral = '';
                const r = await gh.api('{{ route('admin.clientes.guardar') }}', { method: 'POST', body: this.form });
                this.guardando = false;

                if (r.status === 422) { this.errores = r.data.errors || {}; return; }
                if (!r.ok) { this.errorGeneral = r.data.message || 'No se pudo guardar. Intenta de nuevo.'; return; }

                this.modal = false;
                this.filtro = 'todos'; this.buscar = ''; this.orden = { campo: null, dir: 1 }; this.pagina = 1;
                Alpine.store('toasts').push(r.data.message || 'Cliente guardado');
                await this.recargar(r.data.data?.id);
            },
            async recargar(nuevoId = null) {
                this.cargando = true;
                const r = await gh.api('{{ route('admin.clientes.lista') }}');
                this.cargando = false;
                if (r.ok) {
                    this.clientes = r.data.data;
                    this.resaltado = nuevoId;
                    setTimeout(() => this.resaltado = null, 2400);
                }
            },
        }));
    });
</script>
@endpush
