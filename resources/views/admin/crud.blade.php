{{-- CRUD sencillo (catálogo de servicios, recompensas…). Configuración en App\Support\CrudModulos. --}}
<div class="panel fade-in" x-data="ghCrud(@js($cfg))" @keydown.escape.window="cerrar()">
    <div class="panel-hd">
        <div class="search" style="flex:1;min-width:180px;max-width:320px;">
            <x-icon name="search" size="15" />
            <input class="inp" :placeholder="'Buscar ' + cfg.plural + '…'" x-model="buscar" aria-label="Buscar">
        </div>
        <span style="font-size:12px;color:var(--fg3);" x-text="filtrados.length + ' ' + cfg.plural"></span>
        <button type="button" class="btn btn-p" style="margin-left:auto;" @click="abrir()">
            <x-icon name="plus" size="15" stroke="2.4" /> <span x-text="cfg.nuevo"></span>
        </button>
    </div>

    <div class="tbl-wrap" x-show="filtrados.length" x-cloak>
        <table class="tbl">
            <thead>
                <tr>
                    <template x-for="col in cfg.columnas" :key="col.nombre">
                        <th :class="{ num: ['moneda','numero','minutos','porcentaje'].includes(col.tipo) }" x-text="col.etiqueta"></th>
                    </template>
                    <th style="width:150px;"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(it, i) in filtrados" :key="it.id">
                    <tr class="row-in" :style="'--d:' + (i * 35) + 'ms'">
                        <template x-for="(col, j) in cfg.columnas" :key="col.nombre">
                            <td :class="{ num: ['moneda','numero','minutos','porcentaje'].includes(col.tipo) }">
                                <template x-if="col.tipo === 'si_no'">
                                    <span class="badge" :class="it[col.nombre] ? 'b-ok' : 'b-n'"><span class="dot"></span><span x-text="it[col.nombre] ? 'Activo' : 'Inactivo'"></span></span>
                                </template>
                                <template x-if="col.tipo !== 'si_no'">
                                    <span style="display:inline-flex;align-items:center;gap:8px;" :style="j === 0 ? 'font-weight:600' : ''">
                                        <span x-show="j === 0 && it.color" class="crud-color" :style="'background:' + it.color"></span>
                                        <span x-text="mostrar(it[col.nombre], col.tipo)"></span>
                                    </span>
                                </template>
                            </td>
                        </template>
                        <td>
                            <div class="acciones">
                                <button type="button" class="btn btn-g btn-sm" @click="abrir(it)"><x-icon name="pen" size="13" /> Editar</button>
                                <button type="button" class="icon-btn sm" title="Eliminar" aria-label="Eliminar" @click="eliminar(it)"><x-icon name="trash" size="14" /></button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="empty" x-show="!filtrados.length" x-cloak>
        <x-icon :name="$cfg->icono" size="34" />
        <div style="font-weight:600;color:var(--fg2);" x-text="items.length ? 'Nada coincide con tu búsqueda' : 'Aún no tienes ' + cfg.plural"></div>
        <button type="button" class="btn btn-p btn-sm" x-show="!items.length" @click="abrir()"><x-icon name="plus" size="13" /> <span x-text="'Crear el primero'"></span></button>
    </div>

    {{-- Ventana: crear o editar --}}
    <div class="modal-bg" x-show="modal" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" @submit.prevent="guardar()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon :name="$cfg->icono" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="editando ? 'Editar ' + cfg.titulo.toLowerCase() : cfg.nuevo"></div>
                    <div class="modal-sub">Los campos con * son obligatorios.</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <template x-for="c in cfg.campos" :key="c.nombre">
                    <div class="field">
                        <template x-if="c.tipo === 'si_no'">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px;">
                                <input type="checkbox" x-model="form[c.nombre]" style="width:18px;height:18px;accent-color:var(--p);">
                                <span x-text="c.etiqueta"></span>
                            </label>
                        </template>
                        <template x-if="c.tipo !== 'si_no'">
                            <div>
                                <label class="lbl" :for="'crud-' + c.nombre" x-text="c.etiqueta + (c.requerido ? ' *' : '')"></label>
                                <template x-if="c.tipo === 'area'">
                                    <textarea class="inp" rows="3" :id="'crud-' + c.nombre" :name="c.nombre" :placeholder="c.placeholder" x-model="form[c.nombre]" :class="{ err: errores[c.nombre] }"></textarea>
                                </template>
                                <template x-if="c.tipo === 'color'">
                                    <div style="display:flex;gap:8px;align-items:center;">
                                        <input type="color" :id="'crud-' + c.nombre" x-model="form[c.nombre]" style="width:44px;height:36px;border:1px solid var(--bd);border-radius:8px;padding:2px;background:none;">
                                        <input class="inp" style="width:120px;" :name="c.nombre" x-model="form[c.nombre]" maxlength="20">
                                    </div>
                                </template>
                                <template x-if="['texto','moneda','numero'].includes(c.tipo)">
                                    <input class="inp" :id="'crud-' + c.nombre" :name="c.nombre" :placeholder="c.placeholder"
                                           :type="c.tipo === 'texto' ? 'text' : 'number'" :step="c.tipo === 'moneda' ? '0.01' : 'any'" min="0"
                                           x-model="form[c.nombre]" :class="{ err: errores[c.nombre] }">
                                </template>
                                <div class="ferr" x-show="errores[c.nombre]" x-text="errores[c.nombre] ? errores[c.nombre][0] : ''"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : (editando ? 'Guardar cambios' : 'Guardar')"></button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<style>
    .crud-color { width: 12px; height: 12px; border-radius: 4px; flex: none; box-shadow: 0 0 0 2px var(--bg, #fff), 0 0 0 3px rgba(0,0,0,.08); }
</style>
<script>
    window.ghCrud = window.ghCrud || function (cfg) { return {
        cfg, items: cfg.items || [], buscar: '', modal: false, editando: null, form: {}, errores: {}, guardando: false,
        get filtrados() {
            const q = gh.norm(this.buscar);
            return q ? this.items.filter(it => gh.norm(it.nombre).includes(q)) : this.items;
        },
        mostrar(v, tipo) {
            if (v === null || v === undefined || v === '') return '—';
            if (tipo === 'moneda') return gh.soles(v);
            if (tipo === 'minutos') return v + ' min';
            if (tipo === 'porcentaje') return Number(v) + ' %';
            if (tipo === 'numero') return Number(v).toLocaleString('es-PE');
            return v;
        },
        abrir(it = null) {
            this.editando = it;
            this.errores = {};
            this.form = {};
            for (const c of this.cfg.campos) {
                let v = it ? it[c.nombre] : c.defecto;
                if (c.tipo === 'si_no') v = !!Number(v === true ? 1 : v);
                this.form[c.nombre] = v ?? '';
            }
            this.modal = true;
            this.$nextTick(() => { const el = this.$root.querySelector('.modal input:not([type=checkbox]), .modal textarea'); el && el.focus(); });
        },
        cerrar() { this.modal = false; },
        avisar() { window.dispatchEvent(new CustomEvent('crud-' + this.cfg.modulo, { detail: this.items })); },
        async guardar() {
            this.guardando = true; this.errores = {};
            const url = this.editando ? this.cfg.urls.base + '/' + this.editando.id : this.cfg.urls.guardar;
            const r = await gh.api(url, { method: this.editando ? 'PUT' : 'POST', body: this.form });
            this.guardando = false;
            if (!r.ok) {
                this.errores = r.data.errors || {};
                Alpine.store('toasts').push(r.data.errors ? 'Revisa los campos marcados' : (r.data.message || 'No se pudo guardar'), 'error');
                return;
            }
            if (this.editando) this.items = this.items.map(x => x.id === r.data.item.id ? r.data.item : x);
            else this.items = [...this.items, r.data.item];
            this.modal = false;
            this.avisar();
            Alpine.store('toasts').push(r.data.message || 'Guardado');
        },
        async eliminar(it) {
            if (!confirm('¿Eliminar «' + it.nombre + '»?')) return;
            const r = await gh.api(this.cfg.urls.base + '/' + it.id, { method: 'DELETE' });
            if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar', 'error'); return; }
            this.items = r.data.item ? this.items.map(x => x.id === it.id ? r.data.item : x) : this.items.filter(x => x.id !== it.id);
            this.avisar();
            Alpine.store('toasts').push(r.data.message || 'Eliminado');
        },
    }; };
</script>
@endpush
@endonce
