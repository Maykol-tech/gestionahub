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
        .fila-item { display:grid; grid-template-columns:1fr 100px 90px auto; gap:8px; align-items:center; margin-bottom:8px; }
        .margen-cfg { display:flex; align-items:center; gap:6px; flex-wrap:wrap; font-size:13px; padding:10px 12px; border-radius:12px; background:var(--surface-2, #F6F8FB); margin-bottom:14px; }
        .margen-cfg .inp { width:70px; padding:6px 8px; }
        .margen-box { margin-top:10px; font-size:12px; color:var(--fg2); display:grid; gap:4px; }
        .margen-box > div > div, .margen-box > div { display:flex; justify-content:space-between; gap:8px; }
        .margen-alerta { display:block !important; background:var(--redl); color:var(--red); border-radius:8px; padding:6px 8px; font-weight:600; }
        .ficha-det summary { cursor:pointer; font-size:13px; font-weight:700; color:var(--accent); }
        .costo-prev { font-size:13px; color:var(--fg2); background:var(--surface-2, #F6F8FB); border-radius:10px; padding:10px 12px; }
        @media (max-width: 640px) { .campos-2 { grid-template-columns:1fr; } .fila-item { grid-template-columns:1fr 80px 70px auto; } }
    </style>
@endpush

@section('contenido')
<div x-data="produccion(@js($insumos), @js($recetas), @js($lotes))" @keydown.escape.window="cerrar(); traza = null">

    {{-- Indicadores --}}
    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Lotes por terminar</div><div class="stat-val">{{ $stats['en_proceso'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Insumos bajo mínimo</div><div class="stat-val" style="{{ $stats['bajo_minimo'] ? 'color:#B91C1C' : '' }}">{{ $stats['bajo_minimo'] }}</div></div>
        @if ($stats['por_vencer'])
        <div class="stat fade-in" style="animation-delay:.08s;border-color:var(--amber);"><div class="stat-lbl">Lotes por vencer (3 días)</div><div class="stat-val" style="color:var(--amber)">{{ $stats['por_vencer'] }}</div></div>
        @endif
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
            <button class="btn btn-p" type="button" style="margin-left:auto;" @click="recetas.length ? abrirLote() : (tab = 'recetas', $store.toasts.push('Primero crea una receta: el lote se fabrica a partir de ella', 'info'))" :class="{ 'btn-off': !recetas.length }">
                <x-icon name="plus" size="15" stroke="2.4" /> Nuevo lote
            </button>
        </div>
        <div class="tbl-wrap" x-show="lotes.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Lote</th>
                        <th>Vence</th>
                        <th class="num">Cantidad</th>
                        <th class="num">Costo</th>
                        <th>Estado</th>
                        <th style="width:160px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(l, i) in lotes" :key="l.id">
                        <tr class="row-in" :style="'--d:' + (i * 35) + 'ms'">
                            <td x-text="l.fecha"></td>
                            <td>
                                <div style="font-weight:600;" x-text="l.titulo"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="l.codigo + ' · ' + (l.responsable || recetaNombre(l.receta_id))"></div>
                            </td>
                            <td>
                                <span x-show="l.vence" x-text="l.vence" :style="l.vencido ? 'color:var(--red);font-weight:700' : (l.por_vencer ? 'color:var(--amber);font-weight:700' : '')"></span>
                                <span class="chip chip-alerta" x-show="l.por_vencer && !l.vencido" x-cloak style="margin-left:4px;">Por vencer</span>
                                <span x-show="!l.vence" style="color:var(--fg3);">—</span>
                            </td>
                            <td class="num" x-text="formato(l.cantidad) + ' und'"></td>
                            <td class="num" x-text="l.costo_total !== null ? money(l.costo_total) : '—'"></td>
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
                                    <button class="btn btn-g btn-sm" type="button" x-show="l.estado === 'terminada'" x-cloak @click="verTraza(l)">Trazabilidad</button>
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
            <div class="margen-cfg">
                <span style="font-weight:600;">Margen objetivo</span>
                <input class="inp" type="number" min="1" max="95" step="1" x-model.number="margenes.objetivo" aria-label="Margen objetivo %"> %
                <span style="font-weight:600;margin-left:10px;">Mínimo</span>
                <input class="inp" type="number" min="0" max="95" step="1" x-model.number="margenes.minimo" aria-label="Margen mínimo %"> %
                <button type="button" class="btn btn-g btn-sm" @click="guardarMargenes()">Guardar</button>
                <span style="font-size:12px;color:var(--fg3);">El costo se recalcula solo cuando cambia el costo de un insumo.</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <span style="font-weight:700;">Recetas y costo por unidad</span>
                <button class="btn btn-p" type="button" style="margin-left:auto;" @click="insumos.length ? abrirReceta() : (tab = 'insumos', $store.toasts.push('Primero registra tus insumos: la receta se arma con ellos', 'info'))" :class="{ 'btn-off': !insumos.length }">
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
                        <div class="margen-box">
                            <template x-if="margenDe(r) !== null">
                                <div>
                                    <span>Precio <b x-text="money(precioDe(r))"></b></span>
                                    <span>Margen <b :style="margenDe(r) < margenes.minimo ? 'color:var(--red)' : 'color:var(--green)'" x-text="margenDe(r).toFixed(1) + '%'"></b></span>
                                </div>
                            </template>
                            <div><span>Precio sugerido (<span x-text="margenes.objetivo"></span>%)</span> <b x-text="money(sugerido(r))"></b></div>
                            <div class="margen-alerta" x-show="margenDe(r) !== null && margenDe(r) < margenes.minimo" x-cloak>⚠ El margen está por debajo del mínimo (<span x-text="margenes.minimo"></span>%). Sube el precio o revisa los costos.</div>
                        </div>
                        <ul>
                            <template x-for="it in r.items" :key="it.insumo_id + '-' + it.cantidad">
                                <li><span x-text="it.insumo"></span><span x-text="formato(it.cantidad) + ' ' + (it.unidad || '') + (it.merma_pct ? ' · merma ' + formato(it.merma_pct) + '%' : '')"></span></li>
                            </template>
                        </ul>
                        <div class="acciones" style="margin-top:12px;">
                            <a class="btn btn-g btn-sm" :href="urlFicha(r)" target="_blank" rel="noopener">Ficha técnica</a>
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
                                    <a class="btn btn-p btn-sm" x-show="s.stock <= s.stock_minimo" x-cloak :href="@js(route('admin.proveedores')) + '?insumo=' + s.id" title="Crear orden de compra con este insumo">Pedir</a>
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
            <div class="modal-bd" style="padding-top:0;">
                <div class="field">
                    <label class="lbl" for="i-prov">Proveedor habitual</label>
                    <select class="inp" id="i-prov" x-model="fInsumo.proveedor_id">
                        <option value="">Sin proveedor</option>
                        @foreach ($proveedores as $pv)<option value="{{ $pv->id }}">{{ $pv->nombre }}</option>@endforeach
                    </select>
                    <div style="font-size:12px;color:var(--fg3);margin-top:4px;">Cuando el stock baje del mínimo, la orden de compra se arma con este proveedor.</div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Guardar insumo'"></button>
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
                    <label class="lbl">Insumos * <span style="font-weight:400;color:var(--fg3);">(insumo · cantidad · merma %)</span></label>
                    <template x-for="(it, idx) in fReceta.items" :key="idx">
                        <div class="fila-item">
                            <select class="inp" x-model="it.insumo_id" aria-label="Insumo">
                                <option value="">Elige un insumo…</option>
                                <template x-for="s in insumos" :key="s.id"><option :value="s.id" x-text="s.nombre + ' (' + s.unidad + ')'"></option></template>
                            </select>
                            <input class="inp" type="number" min="0.001" step="0.001" x-model.number="it.cantidad" aria-label="Cantidad" placeholder="Cantidad">
                            <input class="inp" type="number" min="0" max="90" step="0.5" x-model.number="it.merma_pct" aria-label="Merma %" placeholder="Merma %" title="Merma (%)">
                            <button type="button" class="icon-btn sm" @click="quitarItem(idx)" :disabled="fReceta.items.length === 1" aria-label="Quitar insumo"><x-icon name="x" size="14" /></button>
                        </div>
                    </template>
                    <div class="ferr" x-show="errorItems" x-text="errorItems"></div>
                    <button type="button" class="btn btn-g btn-sm" @click="agregarItem()" :disabled="!insumos.length">+ Agregar insumo</button>
                </div>

                <div class="costo-prev">
                    Costo estimado por unidad: <strong x-text="money(costoPreview)"></strong>
                </div>

                <div class="costo-prev" x-show="precioProducto() > 0" x-cloak style="margin-top:6px;">
                    Precio de venta: <strong x-text="money(precioProducto())"></strong> · margen
                    <strong :style="margenPreview() < 30 ? 'color:var(--red)' : 'color:var(--green)'" x-text="margenPreview().toFixed(1) + '%'"></strong>
                </div>

                <details class="ficha-det" style="margin-top:12px;">
                    <summary>Ficha técnica (producto, foto, pasos y alérgenos)</summary>
                    <div class="campos-2" style="margin-top:10px;">
                        <div class="field">
                            <label class="lbl" for="r-prod">Producto que se vende</label>
                            <select class="inp" id="r-prod" x-model="fReceta.producto_id">
                                <option value="">Sin vincular</option>
                                @foreach ($productos as $pr)<option value="{{ $pr->id }}">{{ $pr->nombre }}</option>@endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label class="lbl" for="r-img">Foto (enlace https)</label>
                            <input class="inp" id="r-img" type="url" maxlength="500" x-model="fReceta.imagen_url" placeholder="https://…" :class="{ err: errores.imagen_url }">
                            <div class="ferr" x-show="errores.imagen_url" x-text="errores.imagen_url ? errores.imagen_url[0] : ''"></div>
                        </div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="r-pasos">Pasos de preparación (uno por línea)</label>
                        <textarea class="inp" id="r-pasos" rows="4" maxlength="4000" x-model="fReceta.pasos" placeholder="1. Marinar el pollo 4 horas&#10;2. Hornear a 220 °C por 60 minutos"></textarea>
                    </div>
                    <div class="field">
                        <label class="lbl" for="r-alerg">Alérgenos</label>
                        <input class="inp" id="r-alerg" maxlength="255" x-model="fReceta.alergenos" placeholder="Gluten, lácteos, soya…">
                    </div>
                </details>

                <div class="field" style="margin-top:12px;">
                    <label class="lbl" for="r-notas">Notas</label>
                    <textarea class="inp" id="r-notas" rows="2" maxlength="500" x-model="fReceta.notas"></textarea>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Guardar receta'"></button>
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
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="l-vence">Vence el</label>
                        <input class="inp" id="l-vence" type="date" x-model="fLote.vence_at" :min="fLote.fecha" :class="{ err: errores.vence_at }">
                        <div class="ferr" x-show="errores.vence_at" x-text="errores.vence_at ? errores.vence_at[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="l-resp">Responsable</label>
                        <input class="inp" id="l-resp" maxlength="120" x-model="fLote.responsable" placeholder="{{ auth()->user()->name }}">
                    </div>
                </div>
                <div class="field">
                    <label class="lbl" for="l-notas">Notas</label>
                    <textarea class="inp" id="l-notas" rows="2" maxlength="500" x-model="fLote.notas"></textarea>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Programar lote'"></button>
            </div>
        </form>
    </div>

    {{-- Ventana: trazabilidad del lote (GEST-74) --}}
    <div class="modal-bg" x-show="traza" x-cloak x-transition.opacity @mousedown.self="traza = null">
        <div class="modal" style="max-width:520px;">
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="layers" size="18" /></div>
                <div>
                    <div class="modal-ttl" x-text="traza ? 'Trazabilidad ' + traza.lote.codigo : ''"></div>
                    <div class="modal-sub" x-text="traza ? traza.lote.titulo + ' · ' + traza.lote.fecha + (traza.lote.responsable ? ' · ' + traza.lote.responsable : '') : ''"></div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="traza = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <table class="tbl" x-show="traza && traza.consumos.length">
                    <thead><tr><th>Insumo usado</th><th class="num">Cantidad</th><th class="num">Costo</th></tr></thead>
                    <tbody>
                        <template x-for="c in (traza ? traza.consumos : [])" :key="c.insumo">
                            <tr><td x-text="c.insumo"></td><td class="num" x-text="formato(c.cantidad) + ' ' + (c.unidad || '')"></td><td class="num" x-text="money(c.costo)"></td></tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="traza && !traza.consumos.length" style="font-size:13px;color:var(--fg3);">Este lote se terminó antes de que se registrara la trazabilidad.</div>
            </div>
        </div>
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
        const vacioInsumo = () => ({ nombre: '', unidad: 'und', stock: 0, stock_minimo: 0, costo_unitario: 0, proveedor_id: '' });
        const vacioReceta = () => ({ nombre: '', rendimiento: 1, notas: '', producto_id: '', pasos: '', alergenos: '', imagen_url: '', items: [{ insumo_id: '', cantidad: 1, merma_pct: 0 }] });
        const productos = @js($productos);
        const vacioLote = () => ({ receta_id: recetas[0] ? recetas[0].id : '', titulo: '', cantidad: recetas[0] ? recetas[0].rendimiento : 1, fecha: hoy(), vence_at: '', responsable: '', notas: '' });
        const margenes = @js($margenes);

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
                    return acc + (ins ? (Number(it.cantidad) || 0) * (1 + (Number(it.merma_pct) || 0) / 100) * ins.costo_unitario : 0);
                }, 0);
                return total / rend;
            },
            get errorItems() {
                const k = Object.keys(this.errores).find(x => x === 'items' || x.startsWith('items.'));
                return k ? this.errores[k][0] : '';
            },

            abrirInsumo(s = null) {
                this.errores = {}; this.editando = s ? s.id : null;
                this.fInsumo = s ? { nombre: s.nombre, unidad: s.unidad, stock: s.stock, stock_minimo: s.stock_minimo, costo_unitario: s.costo_unitario, proveedor_id: s.proveedor_id || '' } : vacioInsumo();
                this.modal = 'insumo';
            },
            abrirReceta(r = null) {
                this.errores = {}; this.editando = r ? r.id : null;
                this.fReceta = r
                    ? { nombre: r.nombre, rendimiento: r.rendimiento, notas: r.notas || '', producto_id: r.producto_id || '', pasos: r.pasos || '', alergenos: r.alergenos || '', imagen_url: r.imagen_url || '', items: r.items.map(i => ({ insumo_id: i.insumo_id, cantidad: i.cantidad, merma_pct: i.merma_pct || 0 })) }
                    : vacioReceta();
                this.modal = 'receta';
            },
            abrirLote() {
                this.errores = {}; this.editando = null; this.fLote = vacioLote(); this.modal = 'lote';
            },
            agregarItem() { this.fReceta.items.push({ insumo_id: '', cantidad: 1, merma_pct: 0 }); },
            traza: null, margenes,
            async verTraza(l) {
                const r = await gh.api(@js(url('admin/produccion/lotes')) + '/' + l.id + '/trazabilidad');
                this.traza = { lote: l, consumos: r.ok ? r.data.consumos : [] };
            },
            precioDe(r) { const p = productos.find(x => x.id == r.producto_id); return p ? Number(p.precio) : 0; },
            margenDe(r) { const pv = this.precioDe(r); return pv > 0 ? (pv - r.costo_unitario) / pv * 100 : null; },
            sugerido(r) { return r.costo_unitario / (1 - this.margenes.objetivo / 100); },
            async guardarMargenes() {
                const r = await gh.api(@js(route('admin.produccion.margenes')), { method: 'POST', body: this.margenes });
                Alpine.store('toasts').push(r.ok ? r.data.message : (r.data.message || 'Revisa los márgenes'), r.ok ? 'ok' : 'error');
            },
            precioProducto() { const p = productos.find(x => x.id == this.fReceta.producto_id); return p ? Number(p.precio) : 0; },
            margenPreview() { const pv = this.precioProducto(); return pv > 0 ? (pv - this.costoPreview) / pv * 100 : 0; },
            urlFicha(r) { return @js(url('admin/produccion/recetas')) + '/' + r.id + '/ficha'; },
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
                if (estado === 'terminada' && !confirm('Al terminar el lote se descontarán sus insumos del stock. ¿Continuar?')) {
                    gh.ir(location.pathname, false); return;
                }
                const r = await gh.api(urls.lotes + '/' + l.id + '/estado', { method: 'PATCH', body: { estado } });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo cambiar el estado.', 'error'); gh.ir(location.pathname, false); }
            },

            async eliminar(tipo, item) {
                const nombre = item.nombre || item.titulo;
                if (!confirm('¿Eliminar «' + nombre + '»?')) return;
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
