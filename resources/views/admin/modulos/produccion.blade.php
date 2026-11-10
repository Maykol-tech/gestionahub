@extends('layouts.app')

@section('titulo', 'Producción')
@section('subtitulo', 'Insumos, recetas con costo y lotes que descuentan stock')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .tabs { display:flex; gap:6px; margin-bottom:14px; flex-wrap:wrap; }
        .tab-btn { border:1px solid var(--border); background:#fff; border-radius:99px; padding:7px 16px; font-size:13px; font-weight:600; color:var(--fg2); cursor:pointer; transition: background .2s var(--ease), color .2s var(--ease), border-color .2s var(--ease); }
        .tab-btn:hover { border-color:var(--brand, #2B6EF6); color:var(--fg); }
        .tab-btn.on { background:var(--brand, #2B6EF6); border-color:var(--brand, #2B6EF6); color:#fff; }
        .chip { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; white-space:nowrap; }
        .chip-alerta { background:#FEE2E2; color:#B91C1C; }
        .estado-sel { min-width:150px; padding:6px 8px; font-size:12px; font-weight:700; border-radius:99px; }
        .receta-card { border:1px solid var(--border); border-radius:12px; padding:16px; background:#fff; transition: transform .2s var(--ease), box-shadow .2s var(--ease); }
        .receta-card:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(26,35,50,.09); }
        .receta-card ul { list-style:none; margin:10px 0 0; padding:0; font-size:13px; color:var(--fg2); }
        .receta-card li { display:flex; justify-content:space-between; padding:4px 0; border-top:1px dashed var(--border); }
        .acciones { display:flex; gap:6px; justify-content:flex-end; align-items:center; }
        .campos-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .fila-item { display:grid; grid-template-columns:1fr 110px auto; gap:8px; align-items:center; margin-bottom:8px; }
        .costo-prev { font-size:13px; color:var(--fg2); background:var(--surface-2, #F6F8FB); border-radius:10px; padding:10px 12px; }
        @media (max-width: 640px) { .campos-2 { grid-template-columns:1fr; } .fila-item { grid-template-columns:1fr 90px auto; } }
    </style>
@endpush

@section('contenido')
<div x-data="produccion(@js($insumos), @js($recetas), @js($lotes))" @keydown.escape.window="cerrar()">

    {{-- Indicadores --}}
    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Lotes por terminar</div><div class="stat-val">{{ $stats['en_proceso'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Insumos bajo mínimo</div><div class="stat-val" style="{{ $stats['bajo_minimo'] ? 'color:#B91C1C' : '' }}">{{ $stats['bajo_minimo'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Unidades terminadas (mes)</div><div class="stat-val">{{ number_format($stats['unidades_mes']) }}</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Costo de producción (mes)</div><div class="stat-val">S/ {{ number_format($stats['costo_mes'], 2) }}</div></div>
    </div>

    <div class="tabs" role="tablist">
        <button type="button" class="tab-btn" :class="{ on: tab === 'lotes' }" @click="tab = 'lotes'" role="tab">Lotes</button>
        <button type="button" class="tab-btn" :class="{ on: tab === 'recetas' }" @click="tab = 'recetas'" role="tab">Recetas</button>
        <button type="button" class="tab-btn" :class="{ on: tab === 'insumos' }" @click="tab = 'insumos'" role="tab">Insumos</button>
    </div>

    {{-- LOTES --}}
    <div class="panel fade-in" x-show="tab === 'lotes'" x-cloak>
        <div class="panel-hd">
            <span style="font-weight:700;">Lotes de producción</span>
            <button class="btn btn-p" type="button" style="margin-left:auto;" @click="abrirLote()" :disabled="!recetas.length">
                <x-icon name="plus" size="15" stroke="2.4" /> Nuevo lote
            </button>
        </div>
        <div class="tbl-wrap" x-show="lotes.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Lote</th>
                        <th class="num">Cantidad</th>
                        <th class="num">Costo</th>
                        <th>Estado</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(l, i) in lotes" :key="l.id">
                        <tr class="row-in" :style="'--d:' + (i * 35) + 'ms'">
                            <td x-text="l.fecha"></td>
                            <td>
                                <div style="font-weight:600;" x-text="l.titulo"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="recetaNombre(l.receta_id)"></div>
                            </td>
                            <td class="num" x-text="formato(l.cantidad) + ' und'"></td>
                            <td class="num" x-text="l.costo_total !== null ? money(l.costo_total) : '”'"></td>
                            <td>
                                <select class="inp estado-sel"
                                        :style="'background:' + estados[l.estado].fondo + ';color:' + estados[l.estado].color"
                                        :disabled="l.estado === 'terminada'"
                                        @change="cambiarEstado(l, $event.target.value)"
                                        :aria-label="'Estado del lote ' + l.titulo">
                                    <template x-for="(e, k) in estados" :key="k">
                                        <option :value="k" :selected="k === l.estado" x-text="e.nombre"></option>
                                    </template>
                                </select>
                            </td>
                            <td>
                                <div class="acciones">
                                    <button class="icon-btn sm" type="button" :disabled="l.estado === 'terminada'" @click="eliminar('lotes', l)" aria-label="Eliminar lote"><x-icon name="x" size="14" /></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="empty" x-show="!lotes.length" x-cloak>
            <x-icon name="settings" size="34" />
            <div style="font-weight:600;color:var(--fg2);">Aún no hay lotes</div>
            <div style="font-size:13px;color:var(--fg3);max-width:420px;text-align:center;">Crea un lote con una receta. Al marcarlo como terminado se descuentan sus insumos del stock.</div>
        </div>
    </div>

    {{-- RECETAS --}}
    <div x-show="tab === 'recetas'" x-cloak>
        <div class="panel fade-in" style="padding:16px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <span style="font-weight:700;">Recetas y costo por unidad</span>
                <button class="btn btn-p" type="button" style="margin-left:auto;" @click="abrirReceta()" :disabled="!insumos.length">
                    <x-icon name="plus" size="15" stroke="2.4" /> Nueva receta
                </button>
            </div>
            <div class="g-3" x-show="recetas.length" x-cloak>
                <template x-for="(r, i) in recetas" :key="r.id">
                    <div class="receta-card row-in" :style="'--d:' + (i * 35) + 'ms'">
                        <div style="display:flex;align-items:flex-start;gap:8px;">
                            <div style="flex:1;">
                                <div style="font-weight:700;" x-text="r.nombre"></div>
                                <div style="font-size:12px;color:var(--fg3);" x-text="'Rinde ' + formato(r.rendimiento) + ' und'"></div>
                            </div>
                            <span class="chip" style="background:#DCFCE7;color:#15803D;" x-text="'S/ ' + Number(r.costo_unitario).toFixed(2) + ' c/u'"></span>
                        </div>
                        <ul>
                            <template x-for="it in r.items" :key="it.insumo_id + '-' + it.cantidad">
                                <li><span x-text="it.insumo"></span><span x-text="formato(it.cantidad) + ' ' + (it.unidad || '')"></span></li>
                            </template>
                        </ul>
                        <div class="acciones" style="margin-top:12px;">
                            <button class="btn btn-g btn-sm" type="button" @click="abrirReceta(r)">Editar</button>
                            <button class="icon-btn sm" type="button" @click="eliminar('recetas', r)" aria-label="Eliminar receta"><x-icon name="x" size="14" /></button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="empty" x-show="!recetas.length" x-cloak>
                <x-icon name="settings" size="34" />
                <div style="font-weight:600;color:var(--fg2);">Aún no hay recetas</div>
                <div style="font-size:13px;color:var(--fg3);">Primero registra tus insumos. Después arma la receta con sus cantidades.</div>
            </div>
        </div>
    </div>

    {{-- INSUMOS --}}
    <div class="panel fade-in" x-show="tab === 'insumos'" x-cloak>
        <div class="panel-hd">
            <span style="font-weight:700;">Insumos</span>
            <button class="btn btn-p" type="button" style="margin-left:auto;" @click="abrirInsumo()">
                <x-icon name="plus" size="15" stroke="2.4" /> Nuevo insumo
            </button>
        </div>
        <div class="tbl-wrap" x-show="insumos.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th class="num">Stock</th>
                        <th class="num">Mínimo</th>
                        <th class="num">Costo unitario</th>
                        <th style="width:110px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(s, i) in insumos" :key="s.id">
                        <tr class="row-in" :style="'--d:' + (i * 35) + 'ms'">
                            <td>
                                <div style="font-weight:600;" x-text="s.nombre"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="s.unidad"></div>
                            </td>
                            <td class="num">
                                <span x-text="formato(s.stock)"></span>
                                <span class="chip chip-alerta" x-show="s.stock <= s.stock_minimo" x-cloak style="margin-left:6px;">Bajo</span>
                            </td>
                            <td class="num" x-text="formato(s.stock_minimo)"></td>
                            <td class="num" x-text="money(s.costo_unitario)"></td>
                            <td>
                                <div class="acciones">
                                    <button class="btn btn-g btn-sm" type="button" @click="abrirInsumo(s)">Editar</button>
                                    <button class="icon-btn sm" type="button" @click="eliminar('insumos', s)" aria-label="Eliminar insumo"><x-icon name="x" size="14" /></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="empty" x-show="!insumos.length" x-cloak>
            <x-icon name="settings" size="34" />
            <div style="font-weight:600;color:var(--fg2);">Aún no hay insumos</div>
        </div>
    </div>

    {{-- Ventana: insumo --}}
    <div class="modal-bg" x-show="modal === 'insumo'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" @submit.prevent="guardar('insumos')" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="settings" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="editando ? 'Editar insumo' : 'Nuevo insumo'"></div>
                    <div class="modal-sub">Stock y costo por unidad de medida</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="i-nombre">Nombre *</label>
                    <input class="inp" id="i-nombre" maxlength="120" x-model="fInsumo.nombre" :class="{ err: errores.nombre }">
                    <div class="ferr" x-show="errores.nombre" x-text="errores.nombre ? errores.nombre[0] : ''"></div>
                </div>
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="i-unidad">Unidad *</label>
                        <select class="inp" id="i-unidad" x-model="fInsumo.unidad">
                            <option value="und">Unidad</option><option value="kg">Kilogramo (kg)</option><option value="g">Gramo (g)</option>
                            <option value="l">Litro (l)</option><option value="ml">Mililitro (ml)</option>
                            <option value="paquete">Paquete</option><option value="caja">Caja</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="lbl" for="i-costo">Costo unitario (S/) *</label>
                        <input class="inp" id="i-costo" type="number" min="0" step="0.0001" x-model.number="fInsumo.costo_unitario" :class="{ err: errores.costo_unitario }">
                        <div class="ferr" x-show="errores.costo_unitario" x-text="errores.costo_unitario ? errores.costo_unitario[0] : ''"></div>
                    </div>
                </div>
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="i-stock">Stock actual *</label>
                        <input class="inp" id="i-stock" type="number" min="0" step="0.001" x-model.number="fInsumo.stock" :class="{ err: errores.stock }">
                        <div class="ferr" x-show="errores.stock" x-text="errores.stock ? errores.stock[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="i-minimo">Stock mínimo *</label>
                        <input class="inp" id="i-minimo" type="number" min="0" step="0.001" x-model.number="fInsumo.stock_minimo" :class="{ err: errores.stock_minimo }">
                        <div class="ferr" x-show="errores.stock_minimo" x-text="errores.stock_minimo ? errores.stock_minimo[0] : ''"></div>
                    </div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando¦' : 'Guardar insumo'"></button>
            </div>
        </form>
    </div>

    {{-- Ventana: receta --}}
    <div class="modal-bg" x-show="modal === 'receta'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" style="max-width:560px;" @submit.prevent="guardar('recetas')" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="settings" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="editando ? 'Editar receta' : 'Nueva receta'"></div>
                    <div class="modal-sub">Cuánto de cada insumo se usa para una tanda</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="r-nombre">Nombre *</label>
                        <input class="inp" id="r-nombre" maxlength="120" x-model="fReceta.nombre" :class="{ err: errores.nombre }">
                        <div class="ferr" x-show="errores.nombre" x-text="errores.nombre ? errores.nombre[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="r-rend">Rinde (unidades) *</label>
                        <input class="inp" id="r-rend" type="number" min="1" step="1" x-model.number="fReceta.rendimiento" :class="{ err: errores.rendimiento }">
                        <div class="ferr" x-show="errores.rendimiento" x-text="errores.rendimiento ? errores.rendimiento[0] : ''"></div>
                    </div>
                </div>

                <div class="field">
                    <label class="lbl">Insumos *</label>
                    <template x-for="(it, idx) in fReceta.items" :key="idx">
                        <div class="fila-item">
                            <select class="inp" x-model="it.insumo_id" aria-label="Insumo">
                                <option value="">Elige un insumo¦</option>
                                <template x-for="s in insumos" :key="s.id"><option :value="s.id" x-text="s.nombre + ' (' + s.unidad + ')'"></option></template>
                            </select>
                            <input class="inp" type="number" min="0.001" step="0.001" x-model.number="it.cantidad" aria-label="Cantidad">
                            <button type="button" class="icon-btn sm" @click="quitarItem(idx)" :disabled="fReceta.items.length === 1" aria-label="Quitar insumo"><x-icon name="x" size="14" /></button>
                        </div>
                    </template>
                    <div class="ferr" x-show="errorItems" x-text="errorItems"></div>
                    <button type="button" class="btn btn-g btn-sm" @click="agregarItem()" :disabled="!insumos.length">+ Agregar insumo</button>
                </div>

                <div class="costo-prev">
                    Costo estimado por unidad: <strong x-text="money(costoPreview)"></strong>
                </div>

                <div class="field" style="margin-top:12px;">
                    <label class="lbl" for="r-notas">Notas</label>
                    <textarea class="inp" id="r-notas" rows="2" maxlength="500" x-model="fReceta.notas"></textarea>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando¦' : 'Guardar receta'"></button>
            </div>
        </form>
    </div>

    {{-- Ventana: lote --}}
    <div class="modal-bg" x-show="modal === 'lote'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" @submit.prevent="guardar('lotes')" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="settings" size="18" /></div>
                <div>
                    <div class="modal-ttl">Nuevo lote</div>
                    <div class="modal-sub">Los insumos se descuentan al marcarlo como terminado</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="l-receta">Receta *</label>
                    <select class="inp" id="l-receta" x-model="fLote.receta_id" :class="{ err: errores.receta_id }">
                        <template x-for="r in recetas" :key="r.id"><option :value="r.id" x-text="r.nombre"></option></template>
                    </select>
                    <div class="ferr" x-show="errores.receta_id" x-text="errores.receta_id ? errores.receta_id[0] : ''"></div>
                </div>
                <div class="field">
                    <label class="lbl" for="l-titulo">Nombre del lote (opcional)</label>
                    <input class="inp" id="l-titulo" maxlength="120" x-model="fLote.titulo" placeholder="Por defecto, el nombre de la receta">
                </div>
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="l-cantidad">Unidades a producir *</label>
                        <input class="inp" id="l-cantidad" type="number" min="1" step="1" x-model.number="fLote.cantidad" :class="{ err: errores.cantidad }">
                        <div class="ferr" x-show="errores.cantidad" x-text="errores.cantidad ? errores.cantidad[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="l-fecha">Fecha *</label>
                        <input class="inp" id="l-fecha" type="date" x-model="fLote.fecha" :class="{ err: errores.fecha }">
                        <div class="ferr" x-show="errores.fecha" x-text="errores.fecha ? errores.fecha[0] : ''"></div>
                    </div>
                </div>
                <div class="field">
                    <label class="lbl" for="l-notas">Notas</label>
                    <textarea class="inp" id="l-notas" rows="2" maxlength="500" x-model="fLote.notas"></textarea>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando¦' : 'Programar lote'"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.produccion = window.produccion || function (insumos, recetas, lotes) {
        const estados = {
            pendiente: { nombre: 'Pendiente', color: '#B45309', fondo: '#FEF3C7' },
            en_proceso: { nombre: 'En proceso', color: '#1D4ED8', fondo: '#DBEAFE' },
            terminada: { nombre: 'Terminada', color: '#15803D', fondo: '#DCFCE7' },
        };
        const urls = {
            insumos: @js(route('admin.produccion.insumos.guardar')),
            recetas: @js(route('admin.produccion.recetas.guardar')),
            lotes: @js(route('admin.produccion.lotes.guardar')),
        };
        const hoy = () => { const d = new Date(); return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };
        const vacioInsumo = () => ({ nombre: '', unidad: 'und', stock: 0, stock_minimo: 0, costo_unitario: 0 });
        const vacioReceta = () => ({ nombre: '', rendimiento: 1, notas: '', items: [{ insumo_id: '', cantidad: 1 }] });
        const vacioLote = () => ({ receta_id: recetas[0] ? recetas[0].id : '', titulo: '', cantidad: recetas[0] ? recetas[0].rendimiento : 1, fecha: hoy(), notas: '' });

        return {
            insumos, recetas, lotes, estados,
            tab: 'lotes', modal: null, editando: null,
            fInsumo: vacioInsumo(), fReceta: vacioReceta(), fLote: vacioLote(),
            errores: {}, guardando: false,

            formato(n) { return Number(n || 0).toLocaleString('es-PE', { maximumFractionDigits: 3 }); },
            money(n) { return 'S/ ' + Number(n || 0).toFixed(2); },
            recetaNombre(id) { const r = this.recetas.find(x => x.id == id); return r ? r.nombre : 'Receta eliminada'; },

            get costoPreview() {
                const rend = Math.max(1, Number(this.fReceta.rendimiento) || 1);
                const total = this.fReceta.items.reduce((acc, it) => {
                    const ins = this.insumos.find(x => x.id == it.insumo_id);
                    return acc + (ins ? (Number(it.cantidad) || 0) * ins.costo_unitario : 0);
                }, 0);
                return total / rend;
            },
            get errorItems() {
                const k = Object.keys(this.errores).find(x => x === 'items' || x.startsWith('items.'));
                return k ? this.errores[k][0] : '';
            },

            abrirInsumo(s = null) {
                this.errores = {}; this.editando = s ? s.id : null;
                this.fInsumo = s ? { nombre: s.nombre, unidad: s.unidad, stock: s.stock, stock_minimo: s.stock_minimo, costo_unitario: s.costo_unitario } : vacioInsumo();
                this.modal = 'insumo';
            },
            abrirReceta(r = null) {
                this.errores = {}; this.editando = r ? r.id : null;
                this.fReceta = r
                    ? { nombre: r.nombre, rendimiento: r.rendimiento, notas: r.notas || '', items: r.items.map(i => ({ insumo_id: i.insumo_id, cantidad: i.cantidad })) }
                    : vacioReceta();
                this.modal = 'receta';
            },
            abrirLote() {
                this.errores = {}; this.editando = null; this.fLote = vacioLote(); this.modal = 'lote';
            },
            agregarItem() { this.fReceta.items.push({ insumo_id: '', cantidad: 1 }); },
            quitarItem(i) { if (this.fReceta.items.length > 1) this.fReceta.items.splice(i, 1); },
            cerrar() { this.modal = null; },

            async guardar(tipo) {
                const datos = { insumos: this.fInsumo, recetas: this.fReceta, lotes: this.fLote }[tipo];
                const url = this.editando ? urls[tipo] + '/' + this.editando : urls[tipo];
                this.guardando = true; this.errores = {};
                const r = await gh.api(url, { method: this.editando ? 'PUT' : 'POST', body: datos });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.cerrar(); gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo guardar.', 'error'); }
            },

            async cambiarEstado(l, estado) {
                if (estado === l.estado) return;
                if (estado === 'terminada' && !confirm('Al terminar el lote se descontarán sus insumos del stock. Â¿Continuar?')) {
                    gh.ir(location.pathname, false); return;
                }
                const r = await gh.api(urls.lotes + '/' + l.id + '/estado', { method: 'PATCH', body: { estado } });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo cambiar el estado.', 'error'); gh.ir(location.pathname, false); }
            },

            async eliminar(tipo, item) {
                const nombre = item.nombre || item.titulo;
                if (!confirm('Â¿Eliminar Â«' + nombre + 'Â»?')) return;
                const r = await gh.api(urls[tipo] + '/' + item.id, { method: 'DELETE' });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar.', 'error'); }
            },
        };
    };
    gh.ready(() => Alpine.data('produccion', window.produccion));
</script>
@endpush
@endsection
