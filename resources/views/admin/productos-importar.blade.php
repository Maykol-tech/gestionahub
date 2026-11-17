{{-- Importar productos desde Excel: subir → revisar → confirmar --}}
<div x-data="importarProductos()" @importar-productos.window="abrir()">
    <div class="modal-bg" x-show="abierto" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @keydown.escape.window="cerrar()" @mousedown.self="cerrar()">
        <div class="modal" style="max-width:820px;">
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="upload" size="18" /></div>
                <div>
                    <div class="modal-ttl">Importar productos desde Excel</div>
                    <div class="modal-sub" x-text="filas.length ? 'Revisa antes de guardar' : 'Sube tu lista y nosotros la cargamos'"></div>
                </div>
                <button class="icon-btn sm" type="button" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar">✕</button>
            </div>
            <div class="modal-bd">
                <template x-if="!filas.length">
                    <div>
                        <ol class="imp-pasos">
                            <li><b>Descarga la plantilla</b> o usa tu propio Excel. La primera fila debe tener los títulos: <i>Nombre</i> y <i>Precio</i> (obligatorios), y si quieres <i>Stock, Stock mínimo, Categoría, SKU, Descripción, Precio oferta, Activo</i>.</li>
                            <li><b>Sube el archivo.</b> Te mostramos qué filas están bien antes de guardar.</li>
                            <li>Si un producto ya existe (mismo SKU o mismo nombre) <b>se actualiza</b>; si no, se crea. Las categorías nuevas se crean solas.</li>
                        </ol>
                        <a class="btn btn-g btn-sm" href="{{ route('admin.productos.importar.plantilla') }}"><x-icon name="download" size="14" /> Descargar plantilla</a>
                        <label class="imp-drop" :class="{ sobre: sobre, cargando: cargando }" @dragover.prevent="sobre = true" @dragleave="sobre = false" @drop.prevent="sobre = false; subir($event.dataTransfer.files[0])">
                            <input type="file" accept=".xlsx,.xls,.csv" @change="subir($event.target.files[0]); $event.target.value = ''" hidden>
                            <x-icon name="upload" size="26" />
                            <b x-text="cargando ? 'Leyendo el archivo…' : 'Arrastra tu Excel aquí o toca para elegirlo'"></b>
                            <span>.xlsx, .xls o .csv · hasta 5 MB · máximo {{ \App\Http\Controllers\ProductosImportController::MAX_FILAS }} productos</span>
                        </label>
                        <div class="imp-err" x-show="error" x-text="error"></div>
                    </div>
                </template>
                <template x-if="filas.length">
                    <div>
                        <div class="imp-res">
                            <span class="ok" x-text="resumen.buenas + ' listos para importar'"></span>
                            <span class="mal" x-show="resumen.con_errores" x-text="resumen.con_errores + ' con errores (se saltan)'"></span>
                            <label style="margin-left:auto;font-size:12px;display:flex;gap:6px;align-items:center;" x-show="resumen.con_errores"><input type="checkbox" x-model="soloErrores"> Ver solo errores</label>
                        </div>
                        <div class="imp-tabla">
                            <table class="tbl">
                                <thead><tr><th>Fila</th><th>Nombre</th><th class="r">Precio</th><th class="r">Stock</th><th>Categoría</th><th>SKU</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <template x-for="f in visibles" :key="f.fila">
                                        <tr :class="{ 'imp-mal': f.errores.length }">
                                            <td x-text="f.fila"></td>
                                            <td x-text="f.nombre || '—'"></td>
                                            <td class="r" x-text="f.precio !== null ? gh.soles(f.precio) : '—'"></td>
                                            <td class="r" x-text="f.stock"></td>
                                            <td x-text="f.categoria || '—'"></td>
                                            <td x-text="f.sku || '—'"></td>
                                            <td><span x-show="!f.errores.length" class="badge b-ok">Bien</span><span x-show="f.errores.length" class="imp-motivo" x-text="f.errores.join(' · ')"></span></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="imp-err" x-show="error" x-text="error"></div>
                    </div>
                </template>
            </div>
            <div class="modal-ft" x-show="filas.length">
                <button class="btn btn-g" type="button" @click="filas = []; error = ''">Elegir otro archivo</button>
                <button class="btn btn-p" type="button" :disabled="guardando || !resumen.buenas" @click="confirmar()" x-text="guardando ? 'Importando…' : 'Importar ' + resumen.buenas + ' productos'"></button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .imp-pasos { margin:0 0 14px; padding-left:20px; font-size:13px; color:var(--fg2); line-height:1.6; }
    .imp-pasos li { margin-bottom:6px; }
    .imp-drop { margin-top:14px; display:flex; flex-direction:column; align-items:center; gap:6px; padding:30px 16px; border:2px dashed var(--border); border-radius:14px; cursor:pointer; text-align:center; color:var(--fg3); transition:border-color .2s, background .2s, transform .2s; }
    .imp-drop b { color:var(--fg); font-size:14px; }
    .imp-drop span { font-size:12px; }
    .imp-drop:hover, .imp-drop.sobre { border-color:var(--accent); background:var(--acl); transform:scale(1.01); }
    .imp-drop.cargando { opacity:.6; pointer-events:none; }
    .imp-err { margin-top:12px; color:var(--red); font-size:13px; font-weight:600; }
    .imp-res { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:10px; }
    .imp-res span { font-size:12px; font-weight:700; padding:4px 10px; border-radius:99px; }
    .imp-res .ok { background:#DCFCE7; color:#15803D; }
    .imp-res .mal { background:#FEE2E2; color:#B91C1C; }
    .imp-tabla { max-height:50vh; overflow:auto; border:1px solid var(--border); border-radius:10px; }
    .imp-tabla .tbl { min-width:640px; }
    .imp-tabla th { position:sticky; top:0; background:var(--surface); z-index:1; }
    .imp-tabla .r { text-align:right; font-variant-numeric:tabular-nums; }
    .imp-mal td { background:#FEF2F2; }
    .imp-motivo { color:#B91C1C; font-size:12px; font-weight:600; }
</style>
@endpush

@push('scripts')
<script>
    gh.ready(() => Alpine.data('importarProductos', () => ({
        abierto: false, cargando: false, guardando: false, sobre: false, error: '', soloErrores: false,
        filas: [], resumen: { total: 0, buenas: 0, con_errores: 0 },
        get visibles() { return this.soloErrores ? this.filas.filter(f => f.errores.length) : this.filas; },
        abrir() { this.abierto = true; this.filas = []; this.error = ''; },
        cerrar() { if (!this.guardando) this.abierto = false; },
        async subir(archivo) {
            if (!archivo) return;
            this.error = ''; this.cargando = true;
            const fd = new FormData(); fd.append('archivo', archivo);
            const r = await gh.api(@js(route('admin.productos.importar.previa')), { method: 'POST', body: fd });
            this.cargando = false;
            if (!r.ok) { this.error = (r.data.errors ? Object.values(r.data.errors)[0][0] : r.data.message) || 'No se pudo leer el archivo.'; return; }
            this.filas = r.data.filas; this.resumen = r.data.resumen; this.soloErrores = false;
        },
        async confirmar() {
            this.guardando = true; this.error = '';
            const r = await gh.api(@js(route('admin.productos.importar')), { method: 'POST', body: { filas: this.filas.filter(f => !f.errores.length) } });
            this.guardando = false;
            if (!r.ok) { this.error = r.data.message || 'No se pudo importar.'; return; }
            Alpine.store('toasts').push(r.data.message);
            this.abierto = false;
            gh.ir(location.href, false);
        },
    })));
</script>
@endpush
