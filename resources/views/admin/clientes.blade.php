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
                <template x-for="f in todosFiltros" :key="f.clave">
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
                        <th style="width:150px;"></th>
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
                                        <div class="etqs" x-show="c.etiquetas.length || c.cumple_mes">
                                            <template x-for="e in c.etiquetas" :key="e"><span class="etq" :class="claseEtiqueta(e)" x-text="e"></span></template>
                                            <span class="etq etq-cumple" x-show="c.cumple_mes" x-text="'🎂 ' + (c.cumple_dias === 0 ? 'Hoy' : c.cumple_texto)"></span>
                                        </div>
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
                            <td style="text-align:right;white-space:nowrap;">
                                <a class="btn btn-g btn-sm" x-show="c.telefono && c.cumple_mes" :href="'https://wa.me/' + whatsapp(c.telefono) + '?text=' + encodeURIComponent(saludoCumple(c))" target="_blank" rel="noopener" title="Saludar por su cumpleaños">🎂</a>
                                <a class="btn btn-wa btn-sm" x-show="c.telefono" :href="'https://wa.me/' + whatsapp(c.telefono)" target="_blank" rel="noopener" title="Escribir por WhatsApp"><x-icon name="message" size="13" /></a>
                                <a class="btn btn-g btn-sm" :href="urlTarjeta(c.id)" target="_blank" rel="noopener" title="Tarjeta digital de puntos"><x-icon name="card" size="13" /></a>
                                <button class="btn btn-g btn-sm" @click="editar(c)" title="Editar cliente"><x-icon name="pen" size="13" /></button>
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
                    <div class="modal-ttl" x-text="editando ? 'Editar cliente' : 'Nuevo cliente'"></div>
                    <div class="modal-sub" x-text="editando ? 'Cambia sus datos, etiquetas o cumpleaños' : 'Se guardará en tu cartera de clientes'"></div>
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
                    <div class="field">                        <label class="lbl">¿Cómo llegó?</label>
                        <select class="inp" x-model="form.fuente">
                            <option value="manual">Registro manual</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="referido">Referido</option>
                            <option value="landing">Página web</option>
                        </select>
                    </div>
                </div>
                <div class="form-sec" style="margin-top:4px;">Etiquetas y cumpleaños</div>
                <div class="field">
                    <label class="lbl">Etiquetas <span style="font-weight:400;color:var(--fg3);">(para filtrar y enviar promociones)</span></label>
                    <div class="etq-edit">
                        <template x-for="e in form.etiquetas" :key="e">
                            <span class="etq" :class="claseEtiqueta(e)"><span x-text="e"></span><button type="button" @click="quitarEtiqueta(e)" aria-label="Quitar">×</button></span>
                        </template>
                        <input class="etq-inp" x-model="nuevaEtiqueta" maxlength="20" placeholder="Escribe y pulsa Enter"
                               @keydown.enter.prevent="agregarEtiqueta(nuevaEtiqueta)" @keydown.comma.prevent="agregarEtiqueta(nuevaEtiqueta)" @blur="agregarEtiqueta(nuevaEtiqueta)">
                    </div>
                    <div class="etq-sug">
                        <template x-for="e in sugerencias" :key="e">
                            <button type="button" class="etq etq-add" @click="agregarEtiqueta(e)" x-text="'+ ' + e"></button>
                        </template>
                    </div>
                    <div class="ferr" x-show="errores.etiquetas || errores['etiquetas.0']" x-text="(errores.etiquetas || errores['etiquetas.0'] || [])[0]"></div>
                </div>
                <div class="field">
                    <label class="lbl">Cumpleaños</label>
                    <input class="inp" :class="{ err: errores.cumpleanos }" type="date" x-model="form.cumpleanos" :max="hoy" style="max-width:220px;">
                    <div class="ferr" x-show="errores.cumpleanos" x-text="errores.cumpleanos?.[0]"></div>
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
                    <span x-text="guardando ? 'Guardando…' : (editando ? 'Guardar cambios' : 'Guardar cliente')"></span>
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
    .etqs{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;}
    .etq{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;background:var(--surface2,#EEF2F7);color:var(--fg2);border:1px solid var(--border);line-height:1.5;}
    .etq-vip{background:#FEF3C7;color:#92400E;border-color:#FDE68A;}
    .etq-may{background:#E0E7FF;color:#3730A3;border-color:#C7D2FE;}
    .etq-cumple{background:#FCE7F3;color:#9D174D;border-color:#FBCFE8;}
    .etq button{border:0;background:none;color:inherit;cursor:pointer;font-size:14px;line-height:1;padding:0 0 0 2px;}
    .etq-edit{display:flex;flex-wrap:wrap;gap:6px;align-items:center;padding:7px 9px;border:1px solid var(--border);border-radius:10px;background:var(--surface);min-height:42px;}
    .etq-edit .etq{font-size:12px;padding:3px 9px;}
    .etq-inp{border:0;outline:0;background:none;flex:1;min-width:140px;font:inherit;font-size:13px;color:var(--fg);}
    .etq-sug{display:flex;flex-wrap:wrap;gap:6px;margin-top:7px;}
    .etq-add{cursor:pointer;border-style:dashed;background:none;font-size:12px;}
    .etq-add:hover{border-color:var(--accent);color:var(--accent);}
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
            form: {}, errores: {}, errorGeneral: '', editando: null, nuevaEtiqueta: '',
            hoy: new Date().toISOString().slice(0, 10),
            get todasEtiquetas() {
                const m = new Map();
                this.clientes.forEach(c => (c.etiquetas || []).forEach(e => { if (!m.has(e.toLowerCase())) m.set(e.toLowerCase(), e); }));
                return [...m.values()].sort((a, b) => a.localeCompare(b));
            },
            get todosFiltros() {
                const extra = this.todasEtiquetas.map(e => ({ clave: 'tag:' + e, texto: e }));
                return [...this.filtros, { clave: 'cumple', texto: '🎂 Cumpleaños del mes' }, ...extra];
            },
            get sugerencias() {
                const base = ['VIP', 'Mayorista', 'Frecuente', 'Delivery', ...this.todasEtiquetas];
                const ya = (this.form.etiquetas || []).map(e => e.toLowerCase());
                return [...new Map(base.map(e => [e.toLowerCase(), e])).values()].filter(e => !ya.includes(e.toLowerCase())).slice(0, 8);
            },
            claseEtiqueta(e) { const k = gh.norm(e); return k === 'vip' ? 'etq-vip' : (k.startsWith('mayorista') ? 'etq-may' : ''); },
            agregarEtiqueta(e) {
                e = (e || '').replace(/,/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 20);
                this.nuevaEtiqueta = '';
                if (!e || this.form.etiquetas.some(x => x.toLowerCase() === e.toLowerCase())) return;
                if (this.form.etiquetas.length >= 6) { Alpine.store('toasts').push('Máximo 6 etiquetas por cliente', 'error'); return; }
                this.form.etiquetas.push(e);
            },
            quitarEtiqueta(e) { this.form.etiquetas = this.form.etiquetas.filter(x => x !== e); },
            saludoCumple(c) {
                const nombre = (c.nombre || '').split(' ')[0];
                return '¡Feliz cumpleaños, ' + nombre + '! 🎉 En {{ addslashes($layout['negocio'] ?? 'nuestro negocio') }} queremos celebrarlo contigo. Muestra este mensaje en tu próxima compra y recibe un detalle especial.';
            },
            urlTarjeta(id) { return '{{ url('admin/clientes') }}/' + id + '/tarjeta'; },

            animar() { this.entrada = true; clearTimeout(this._tEntrada); this._tEntrada = setTimeout(() => this.entrada = false, 900); },
            init() {
                this.animar();
                this.resetForm();
                this.$watch('clientes', v => Alpine.store('clientes').total = v.length);
                Alpine.store('clientes').total = this.clientes.length;
                if (new URLSearchParams(location.search).has('nuevo')) this.abrir();
            },
            resetForm() {
                this.form = { nombre: '', telefono: '', email: '', dni: '', fuente: 'manual', distrito: '', direccion: '', notas: '', etiquetas: [], cumpleanos: '' };
                this.editando = null; this.nuevaEtiqueta = '';
                this.errores = {}; this.errorGeneral = '';
            },
            cumple(c, clave) {
                if (clave === 'frecuentes') return c.pedidos >= 3;
                if (clave === 'inactivos') return !c.activo;
                if (clave === 'activos') return c.activo;
                if (clave === 'nuevos') return c.nuevo;
                if (clave === 'cumple') return c.cumple_mes;
                if (clave.startsWith('tag:')) return (c.etiquetas || []).some(e => e.toLowerCase() === clave.slice(4).toLowerCase());
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
                gh.excel('Clientes', [['Nombre'], ['Teléfono'], ['Correo'], ['Distrito'], ['Pedidos', 'numero'], ['Total gastado', 'moneda'], ['Último pedido', 'fecha'], ['Estado']],
                    this.filtrados.map(c => [c.nombre, c.telefono, c.email, c.distrito, c.pedidos, c.total, c.ultimo || '', c.activo ? 'Activo' : 'Inactivo']));
            },
            abrir() {
                this.resetForm();
                this.modal = true;
                this.$nextTick(() => setTimeout(() => this.$refs.primero?.focus(), 80));
            },
            editar(c) {
                this.resetForm();
                this.editando = c.id;
                Object.assign(this.form, {
                    nombre: c.nombre || '', telefono: c.telefono || '', email: c.email || '', dni: c.dni || '',
                    fuente: ['manual', 'whatsapp', 'referido', 'landing'].includes(c.fuente) ? c.fuente : 'manual',
                    distrito: c.distrito || '', direccion: c.direccion || '', notas: c.notas || '',
                    etiquetas: [...(c.etiquetas || [])], cumpleanos: c.cumpleanos || '',
                });
                this.modal = true;
                this.$nextTick(() => setTimeout(() => this.$refs.primero?.focus(), 80));
            },
            cerrar() { if (!this.guardando) this.modal = false; },

            async guardar() {
                this.guardando = true; this.errores = {}; this.errorGeneral = '';
                if (this.nuevaEtiqueta) this.agregarEtiqueta(this.nuevaEtiqueta);
                const url = this.editando ? '{{ url('admin/clientes') }}/' + this.editando : '{{ route('admin.clientes.guardar') }}';
                const id = this.editando;
                const r = await gh.api(url, { method: this.editando ? 'PUT' : 'POST', body: this.form });
                this.guardando = false;

                if (r.status === 422) { this.errores = r.data.errors || {}; return; }
                if (!r.ok) { this.errorGeneral = r.data.message || 'No se pudo guardar. Intenta de nuevo.'; return; }

                this.modal = false;
                if (!id) { this.filtro = 'todos'; this.buscar = ''; this.orden = { campo: null, dir: 1 }; this.pagina = 1; }
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
