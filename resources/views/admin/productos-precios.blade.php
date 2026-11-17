{{-- Cambiar precios de varios productos a la vez --}}
<div x-data="preciosMasivo(@js(collect($categorias)->map(fn ($c) => ['id' => $c['id'], 'nombre' => $c['nombre']])->values()), @js(collect($productos)->map(fn ($p) => ['id' => $p['id'], 'nombre' => $p['nombre'], 'precio' => (float) ($p['precio'] ?? 0)])->values()))" @precios-masivo.window="abrir()">
    <div class="modal-bg" x-show="abierto" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @keydown.escape.window="abierto = false" @mousedown.self="abierto = false">
        <div class="modal" style="max-width:640px;">
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="cash" size="18" /></div>
                <div><div class="modal-ttl">Cambiar precios de varios productos</div><div class="modal-sub">Ves cómo quedan antes de guardar</div></div>
                <button class="icon-btn sm" type="button" style="margin-left:auto;" @click="abierto = false" aria-label="Cerrar">✕</button>
            </div>
            <div class="modal-bd">
                <div class="lbl" style="margin-top:0;">¿A qué productos?</div>
                <div class="pm-seg">
                    <button type="button" :class="{ on: f.alcance === 'todos' }" @click="f.alcance = 'todos'; simular()">Todos</button>
                    <button type="button" :class="{ on: f.alcance === 'categoria' }" @click="f.alcance = 'categoria'; simular()">Una categoría</button>
                    <button type="button" :class="{ on: f.alcance === 'elegidos' }" @click="f.alcance = 'elegidos'; simular()">Elegirlos</button>
                </div>
                <select class="inp" x-show="f.alcance === 'categoria'" x-model="f.categoria_id" @change="simular()" style="margin-top:8px;" aria-label="Categoría">
                    <option value="">Elige la categoría…</option>
                    <template x-for="c in categorias" :key="c.id"><option :value="c.id" x-text="c.nombre"></option></template>
                </select>
                <div x-show="f.alcance === 'elegidos'" class="pm-lista">
                    <input class="inp" x-model="q" placeholder="Buscar producto…" aria-label="Buscar producto" style="margin-bottom:6px;">
                    <template x-for="p in productos.filter(p => !q || gh.norm(p.nombre).includes(gh.norm(q))).slice(0, 80)" :key="p.id">
                        <label><input type="checkbox" :value="p.id" x-model.number="f.ids" @change="simular()"> <span x-text="p.nombre"></span> <b x-text="gh.soles(p.precio)"></b></label>
                    </template>
                </div>

                <div class="pm-fila">
                    <div>
                        <label class="lbl" for="pm-op">¿Qué cambio?</label>
                        <select class="inp" id="pm-op" x-model="f.operacion" @change="simular()">
                            <option value="subir_pct">Subir un porcentaje (%)</option>
                            <option value="bajar_pct">Bajar un porcentaje (%)</option>
                            <option value="sumar">Sumar soles (S/)</option>
                            <option value="restar">Restar soles (S/)</option>
                            <option value="fijar">Poner un precio fijo (S/)</option>
                        </select>
                    </div>
                    <div><label class="lbl" for="pm-val" x-text="f.operacion.endsWith('pct') ? 'Porcentaje' : 'Monto (S/)'"></label><input class="inp" id="pm-val" type="number" step="0.01" min="0" x-model="f.valor" @input.debounce.400ms="simular()"></div>
                    <div>
                        <label class="lbl" for="pm-red">Redondear</label>
                        <select class="inp" id="pm-red" x-model="f.redondear" @change="simular()">
                            <option value="no">No</option><option value="decima">A 10 céntimos</option><option value="entero">A sol entero</option><option value="90">Terminar en .90</option>
                        </select>
                    </div>
                </div>

                <div class="pm-prev" x-show="prev.total !== null">
                    <div style="font-weight:700;margin-bottom:6px;" x-text="prev.total + (prev.total === 1 ? ' producto cambiará' : ' productos cambiarán')"></div>
                    <template x-for="e in prev.ejemplos" :key="e.nombre">
                        <div class="pm-ej"><span x-text="e.nombre"></span><s x-text="gh.soles(e.antes)"></s><b x-text="gh.soles(e.despues)"></b></div>
                    </template>
                    <div class="sub" x-show="prev.total > prev.ejemplos.length" style="font-size:12px;color:var(--fg3);margin-top:4px;" x-text="'y ' + (prev.total - prev.ejemplos.length) + ' más'"></div>
                </div>
                <div class="imp-err" x-show="error" x-text="error" style="color:var(--red);font-size:13px;font-weight:600;margin-top:10px;"></div>
            </div>
            <div class="modal-ft">
                <button class="btn btn-g" type="button" @click="abierto = false">Cancelar</button>
                <button class="btn btn-p" type="button" :disabled="guardando || !prev.total" @click="aplicar()" x-text="guardando ? 'Guardando…' : 'Cambiar ' + (prev.total || 0) + ' precios'"></button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .pm-seg { display:flex; gap:4px; padding:4px; background:var(--surface2); border-radius:10px; }
    .pm-seg button { flex:1; border:none; background:none; padding:8px; border-radius:8px; font:inherit; font-size:13px; font-weight:600; color:var(--fg3); cursor:pointer; }
    .pm-seg button.on { background:var(--surface); color:var(--fg); box-shadow:0 1px 3px rgba(0,0,0,.1); }
    .pm-lista { margin-top:8px; max-height:200px; overflow:auto; border:1px solid var(--border); border-radius:10px; padding:8px; }
    .pm-lista label { display:flex; gap:8px; align-items:center; padding:5px 4px; font-size:13px; }
    .pm-lista label b { margin-left:auto; font-weight:600; color:var(--fg3); }
    .pm-fila { display:grid; grid-template-columns:1.4fr 1fr 1fr; gap:10px; margin-top:12px; }
    .pm-prev { margin-top:14px; padding:12px; border-radius:12px; background:var(--surface2); font-size:13px; animation:subnavIn .25s ease both; }
    .pm-ej { display:flex; gap:10px; align-items:center; padding:3px 0; }
    .pm-ej span { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pm-ej s { color:var(--fg3); } .pm-ej b { color:#15803D; }
    @media (max-width:600px) { .pm-fila { grid-template-columns:1fr; } }
</style>
@endpush

@push('scripts')
<script>
    gh.ready(() => Alpine.data('preciosMasivo', (categorias, productos) => ({
        categorias, productos, abierto: false, guardando: false, error: '', q: '',
        f: { alcance: 'todos', categoria_id: '', ids: [], operacion: 'subir_pct', valor: 10, redondear: 'no' },
        prev: { total: null, ejemplos: [] },
        abrir() { this.abierto = true; this.error = ''; this.simular(); },
        cuerpo(simular) { return { ...this.f, categoria_id: this.f.categoria_id || null, simular }; },
        async simular() {
            this.error = '';
            if (this.f.alcance === 'categoria' && !this.f.categoria_id) { this.prev = { total: null, ejemplos: [] }; return; }
            if (this.f.alcance === 'elegidos' && !this.f.ids.length) { this.prev = { total: 0, ejemplos: [] }; return; }
            const r = await gh.api(@js(route('admin.productos.precios_masivo')), { method: 'POST', body: this.cuerpo(true) });
            if (!r.ok) { this.error = (r.data.errors ? Object.values(r.data.errors)[0][0] : r.data.message) || 'Revisa los datos.'; this.prev = { total: null, ejemplos: [] }; return; }
            this.prev = r.data;
        },
        async aplicar() {
            if (!confirm('¿Cambiar el precio de ' + this.prev.total + ' productos?')) return;
            this.guardando = true;
            const r = await gh.api(@js(route('admin.productos.precios_masivo')), { method: 'POST', body: this.cuerpo(false) });
            this.guardando = false;
            if (!r.ok) { this.error = r.data.message || 'No se pudo guardar.'; return; }
            Alpine.store('toasts').push(r.data.message);
            this.abierto = false;
            gh.ir(location.href, false);
        },
    })));
</script>
@endpush
