@extends('layouts.app')

@section('titulo', 'Pedidos')

@section('subtitulo')
    <span x-data x-text="$store.pedidos.activos + ' pedidos en curso'">{{ collect($pedidos)->whereIn('columna', ['pendiente', 'preparando', 'en_camino'])->count() }} pedidos en curso</span>
@endsection

@section('acciones')
    <div class="search">
        <x-icon name="search" size="15" />
        <input class="inp" placeholder="Buscar pedido o cliente¦" x-data x-model="$store.pedidos.buscar">
    </div>
    <div class="vseg" x-data>
        <button type="button" :class="{ on: $store.pedidos.vista === 'tablero' }" @click="$store.pedidos.vista = 'tablero'" title="Tablero"><x-icon name="grid" size="15" /></button>
        <button type="button" :class="{ on: $store.pedidos.vista === 'lista' }" @click="$store.pedidos.vista = 'lista'" title="Lista"><x-icon name="menu" size="15" /></button>
    </div>
    <button class="btn btn-p" x-data @click="$dispatch('nuevo-pedido')"><x-icon name="plus" size="15" stroke="2.4" /> Nuevo pedido</button>
@endsection

@section('contenido')
<div x-data="pedidosPage(@js($pedidos), @js($columnas), @js($clientes), @js($catalogo))"
     @nuevo-pedido.window="abrirForm()" @recargar-pedidos.window="recargar(true)">

    @unless ($hayTabla)
        <div class="card empty">
            <x-icon name="clipboard" size="34" />
            <div style="font-weight:600;color:var(--fg2);">Aún no existe la tabla de pedidos</div>
            <div>Ejecuta <b>php artisan migrate</b> y vuelve a cargar esta página.</div>
        </div>
    @else
        {{-- Indicadores por columna --}}
        <div class="stat-row s5">
            <template x-for="(col, i) in columnas" :key="col.clave">
                <button type="button" class="stat fade-in stat-btn" :style="'animation-delay:' + (i * .05) + 's'" @click="irAColumna(col.clave)">
                    <div class="stat-lbl"><span x-text="col.titulo"></span><span class="kdot" :class="'kdot-' + col.clave"></span></div>
                    <div class="stat-val" x-text="deColumna(col.clave).length"></div>
                    <div class="stat-ch neutral" x-text="gh.soles(totalDe(deColumna(col.clave)))"></div>
                </button>
            </template>
            <div class="stat fade-in" style="animation-delay:.2s">
                <div class="stat-lbl">Ventas de hoy <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="cash" size="14" /></span></div>
                <div class="stat-val" x-text="gh.soles(totalDe(deHoy()))"></div>
                <div class="stat-ch neutral" x-text="deHoy().length + ' pedidos Â· ' + deHoy().filter(p => !p.pagado).length + ' por cobrar'"></div>
            </div>
        </div>

        <div class="loadbar" x-show="cargando" x-cloak style="margin-bottom:8px;"></div>

        {{-- ===== TABLERO KANBAN (GEST-22) ===== --}}
        <div class="board" x-show="$store.pedidos.vista === 'tablero'">
            <template x-for="col in columnas" :key="col.clave">
                <section class="kcol" :id="'col-' + col.clave" :class="['kcol-' + col.clave, { 'kcol-over': sobre === col.clave }]"
                         @dragover.prevent="sobre = col.clave" @dragleave.self="sobre = null" @drop.prevent="soltar(col.clave)">
                    <header class="khd">
                        <span class="kdot" :class="'kdot-' + col.clave"></span>
                        <span class="kttl" x-text="col.titulo"></span>
                        <span class="badge b-n" x-text="deColumna(col.clave).length"></span>
                        <span class="ktotal" x-text="gh.soles(totalDe(deColumna(col.clave)))"></span>
                    </header>

                    <div class="klist">
                        <template x-for="(p, j) in deColumna(col.clave)" :key="p.id">
                            <article class="kcard" draggable="true" tabindex="0" :style="'animation-delay:' + Math.min(j * 40, 400) + 'ms'"
                                     :class="{ 'kcard-drag': arrastrado === p.id, 'kcard-moved': p.id === movido }"
                                     @dragstart="arrastrar($event, p)" @dragend="arrastrado = null; sobre = null"
                                     @click="verDetalle(p)" @keydown.enter="verDetalle(p)">
                                <div class="kc-top">
                                    <span class="kc-num" x-text="p.numero"></span>
                                    <span class="badge" :class="p.badge" x-text="p.estado_texto"></span>
                                    <span class="kc-amt" x-text="gh.soles(p.total)"></span>
                                </div>
                                <div class="kc-cli" x-text="p.cliente"></div>
                                <div class="kc-meta">
                                    <span x-text="p.hace"></span>
                                    <span x-text="'Â· ' + p.items + (p.items === 1 ? ' producto' : ' productos')"></span>
                                    <template x-if="p.tipo === 'delivery' && (p.distrito || p.direccion)"><span x-text="'Â· ' + (p.distrito || p.direccion)"></span></template>
                                </div>
                                <div class="kc-foot">
                                    <span class="kc-tag" :class="'tipo-' + p.tipo" x-text="tipoTexto(p.tipo)"></span>
                                    <span class="kc-tag" :class="{ 'kc-pag': p.pagado }" x-text="pagoTexto(p.metodo_pago) + (p.pagado ? ' Â· pagado' : '')"></span>
                                    <button class="kc-next" x-show="siguiente(p)" @click.stop="mover(p, siguiente(p))" :title="'Mover a ' + tituloDe(siguiente(p))">
                                        <span x-text="tituloDe(siguiente(p))"></span> <x-icon name="arrow-right" size="12" stroke="2.2" />
                                    </button>
                                </div>
                            </article>
                        </template>

                        <div class="kempty" x-show="!deColumna(col.clave).length">
                            <x-icon name="inbox" size="22" />
                            <span x-text="col.clave === 'pendiente' ? 'Sin pedidos pendientes' : 'Arrastra pedidos aquí'"></span>
                            <button class="btn btn-g btn-sm" x-show="col.clave === 'pendiente'" @click="abrirForm()"><x-icon name="plus" size="13" /> Nuevo pedido</button>
                        </div>
                    </div>
                </section>
            </template>
        </div>

        {{-- ===== LISTA (GEST-21) ===== --}}
        <div class="panel fade-in" x-show="$store.pedidos.vista === 'lista'" x-cloak>
            <div class="panel-hd">
                <div class="chips">
                    <template x-for="f in filtros" :key="f.clave">
                        <button class="chip" :class="{ on: filtro === f.clave }" @click="filtro = f.clave; pagina = 1">
                            <span x-text="f.titulo"></span><span class="n" x-text="filtrar(f.clave).length"></span>
                        </button>
                    </template>
                </div>
                <button class="btn btn-g btn-sm" @click="exportar()"><x-icon name="download" size="14" /> Exportar</button>
            </div>

            <div class="tbl-wrap" x-show="enLista.length">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Pedido</th><th>Cliente</th><th>Fecha</th><th>Entrega</th><th>Pago</th>
                            <th class="num">Total</th><th>Estado</th><th style="width:120px;text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in paginaLista" :key="p.id">
                            <tr :class="{ 'row-new': p.id === movido, 'row-off': p.estado === 'cancelado' }" style="cursor:pointer;" @click="verDetalle(p)">
                                <td><b x-text="p.numero"></b><div class="sub" x-text="p.items + (p.items === 1 ? ' producto' : ' productos')"></div></td>
                                <td><div style="font-weight:600;" x-text="p.cliente"></div><div class="sub" x-text="p.telefono || ''"></div></td>
                                <td><span x-text="p.fecha"></span><div class="sub" x-text="p.hace"></div></td>
                                <td><span class="kc-tag" :class="'tipo-' + p.tipo" x-text="tipoTexto(p.tipo)"></span></td>
                                <td><span x-text="pagoTexto(p.metodo_pago)"></span><div class="sub" :style="p.pagado ? 'color:var(--green)' : 'color:var(--amber)'" x-text="p.pagado ? 'Pagado' : 'Por cobrar'"></div></td>
                                <td class="num" style="font-weight:700;" x-text="gh.soles(p.total)"></td>
                                <td><span class="badge" :class="p.badge"><span class="dot"></span><span x-text="p.estado_texto"></span></span></td>
                                <td @click.stop>
                                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                                        <button class="icon-btn sm" title="Ver detalle" @click="verDetalle(p)"><x-icon name="eye" size="14" /></button>
                                        <button class="icon-btn sm" title="Editar" x-show="editable(p)" @click="editar(p)"><x-icon name="pen" size="14" /></button>
                                        <button class="icon-btn sm del-btn" title="Cancelar pedido" x-show="editable(p)" @click="pedirCancelar(p)"><x-icon name="x" size="14" /></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="empty" x-show="!enLista.length">
                <x-icon name="clipboard" size="34" />
                <div style="font-weight:600;color:var(--fg2);">No hay pedidos con este filtro</div>
            </div>
            <div class="panel-ft" x-show="enLista.length > porPagina">
                <span x-text="'Mostrando ' + ((pagina - 1) * porPagina + 1) + '–' + Math.min(pagina * porPagina, enLista.length) + ' de ' + enLista.length"></span>
                <div class="pager">
                    <button @click="pagina--" :disabled="pagina === 1" aria-label="Anterior"><x-icon name="chev-l" size="14" /></button>
                    <span style="font-size:12px;padding:0 8px;" x-text="pagina + ' / ' + paginas"></span>
                    <button @click="pagina++" :disabled="pagina >= paginas" aria-label="Siguiente"><x-icon name="chev-r" size="14" /></button>
                </div>
            </div>
        </div>

        @include('admin.pedidos-modales')
    @endunless
</div>
@endsection

@push('styles')
@include('admin.modulos._base')
<style>
    .vseg{display:inline-flex;background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:2px;}
    .vseg button{border:none;background:transparent;color:var(--fg3);width:32px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;cursor:pointer;}
    .vseg button.on{background:#fff;color:var(--accent);box-shadow:var(--shadow);}
    .stat-row.s5{grid-template-columns:repeat(5,1fr);}
    .stat-btn{text-align:left;font-family:inherit;cursor:pointer;transition:transform .2s var(--ease),box-shadow .2s;}
    .stat-btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(26,35,50,.08);}
    .board{display:grid;grid-template-columns:repeat(4,minmax(240px,1fr));gap:12px;align-items:start;overflow-x:auto;padding-bottom:4px;}
    .kcol{background:var(--surface3);border:1px solid var(--border);border-top:3px solid var(--fg3);border-radius:10px;display:flex;flex-direction:column;max-height:calc(100vh - 56px - 170px);min-width:0;transition:background .2s,border-color .2s,box-shadow .2s;}
    .kcol-over{background:var(--acl);border-color:var(--acm);box-shadow:inset 0 0 0 2px var(--acm);}
    .kcol-pendiente{border-top-color:var(--amber);} .kcol-preparando{border-top-color:#8B5CF6;} .kcol-en_camino{border-top-color:var(--accent);} .kcol-entregado{border-top-color:var(--green);}
    .khd{display:flex;align-items:center;gap:8px;padding:12px 14px;border-bottom:1px solid var(--border);background:#fff;border-radius:8px 8px 0 0;}
    .kttl{text-transform:uppercase;letter-spacing:.06em;font-size:11px;font-weight:700;white-space:nowrap;}
    .ktotal{margin-left:auto;font-size:12px;font-weight:600;color:var(--fg2);font-variant-numeric:tabular-nums;white-space:nowrap;}
    .kdot{width:9px;height:9px;border-radius:50%;flex-shrink:0;}
    .kdot-pendiente{background:var(--amber);box-shadow:0 0 0 3px var(--ambl);}
    .kdot-preparando{background:#8B5CF6;box-shadow:0 0 0 3px #EDE9FE;}
    .kdot-en_camino{background:var(--accent);box-shadow:0 0 0 3px var(--acl);}
    .kdot-entregado{background:var(--green);box-shadow:0 0 0 3px var(--greenl);}
    .klist{padding:10px;display:flex;flex-direction:column;gap:8px;overflow-y:auto;min-height:120px;}
    .kcard{background:#fff;border:1px solid var(--border);border-radius:8px;box-shadow:var(--shadow);padding:11px 12px;cursor:pointer;transition:transform .2s var(--ease),box-shadow .2s var(--ease),opacity .2s,border-color .2s;animation:fadeUp .3s var(--ease) backwards;outline:none;}
    .kcard:hover,.kcard:focus-visible{transform:translateY(-1px);box-shadow:0 6px 16px rgba(26,35,50,.09);border-color:var(--acm);}
    .kcard-drag{opacity:.45;transform:rotate(1.5deg) scale(.98);}
    .kcard-moved{animation:popIn .45s var(--ease);}
    @keyframes popIn{0%{transform:scale(.94);box-shadow:0 0 0 3px var(--ctm)}60%{transform:scale(1.02)}100%{transform:none;box-shadow:none}}
    .kc-top{display:flex;align-items:center;gap:6px;margin-bottom:4px;}
    .kc-num{font-weight:700;font-size:12px;}
    .kc-amt{margin-left:auto;font-weight:700;font-size:13px;font-variant-numeric:tabular-nums;}
    .kc-cli{font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .kc-meta{font-size:11px;color:var(--fg3);display:flex;gap:4px;flex-wrap:wrap;}
    .kc-foot{display:flex;align-items:center;gap:5px;margin-top:8px;flex-wrap:wrap;}
    .kc-tag{font-size:10px;font-weight:600;color:var(--fg2);background:var(--surface2);border-radius:5px;padding:2px 6px;white-space:nowrap;}
    .kc-tag.kc-pag{color:var(--green);background:var(--greenl);}
    .kc-tag.tipo-delivery{color:var(--accent);background:var(--acl);}
    .kc-tag.tipo-mesa{color:#7C3AED;background:#F3E8FF;}
    .kc-next{margin-left:auto;display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:var(--accent);background:var(--acl);border:none;border-radius:6px;padding:3px 8px;cursor:pointer;font-family:inherit;transition:background .15s,gap .2s var(--ease);white-space:nowrap;}
    .kc-next:hover{background:#DCE7FF;gap:7px;}
    .kempty{display:flex;flex-direction:column;align-items:center;gap:6px;color:var(--fg3);font-size:12px;padding:26px 10px;border:1.5px dashed var(--border);border-radius:10px;text-align:center;}
    .tbl .sub{font-size:11px;color:var(--fg3);}
    .row-off td{opacity:.6;}
    .del-btn:hover{color:var(--red);border-color:#FECACA;background:var(--redl);}
    @media (max-width:1200px){ .stat-row.s5{grid-template-columns:repeat(3,1fr);} }
    @media (max-width:860px){
        .stat-row.s5{grid-template-columns:1fr 1fr;}
        .board{grid-template-columns:repeat(4,82vw);scroll-snap-type:x mandatory;}
        .kcol{scroll-snap-align:start;max-height:none;}
    }
</style>
@endpush

@push('scripts')
    @include('admin.pedidos-script')
@endpush
