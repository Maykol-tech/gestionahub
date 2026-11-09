@extends('layouts.app')

@section('titulo', 'Productos')

@section('subtitulo')
    <span x-data x-text="$store.productos.activos + ' publicados'">{{ collect($productos)->where('activo', true)->count() }} publicados</span>
@endsection

@section('acciones')
    <button class="btn btn-g" x-data @click="$dispatch('exportar-productos')"><x-icon name="download" size="15" /> Exportar</button>
    <button class="btn btn-p" x-data @click="$dispatch('nuevo-producto')"><x-icon name="plus" size="15" stroke="2.4" /> <span x-text="$store.productos.vista === 'categorias' ? 'Nueva categoría' : 'Nuevo producto'">Nuevo producto</span></button>
@endsection

@section('contenido')
<div x-data="productosPage(@js($productos), @js($categorias))" @nuevo-producto.window="vista === 'categorias' ? abrirCat() : abrir()" @exportar-productos.window="exportar()">

    {{-- GEST-20: alerta de stock minimo --}}
    <div class="alerta-stock fade-in" x-show="bajos.length" x-cloak>
        <span class="as-ico"><x-icon name="alert" size="18" /></span>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;color:var(--fg);" x-text="bajos.length === 1 ? '1 producto llegó a su stock mínimo' : bajos.length + ' productos llegaron a su stock mínimo'"></div>
            <div class="as-lista">
                <template x-for="p in bajos.slice(0, 4)" :key="p.id">
                    <button type="button" class="as-item" @click="reponer(p)">
                        <span x-text="p.nombre"></span>
                        <b :class="p.stock <= 0 ? 'r' : 'a'" x-text="p.tiene_variantes ? p.variantes_bajas + ' var. bajas' : p.stock + ' und.'"></b>
                    </button>
                </template>
                <span x-show="bajos.length > 4" style="font-size:12px;color:var(--fg3);align-self:center;" x-text="'y ' + (bajos.length - 4) + ' más'"></span>
            </div>
        </div>
        <button class="btn btn-g btn-sm" @click="vista = 'productos'; filtro = 'bajo'; pagina = 1">Ver todos</button>
    </div>

    {{-- Indicadores --}}
    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Productos <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="package" size="14" /></span></div>
            <div class="stat-val" x-text="productos.length"></div>
            <div class="stat-ch neutral" x-text="productos.filter(p => p.tiene_variantes).length + ' con variantes'"></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Categorías <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="layers" size="14" /></span></div>
            <div class="stat-val" x-text="categorias.length"></div>
            <div class="stat-ch neutral" x-text="productos.filter(p => !p.categoria_id).length + ' productos sin categoría'"></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Stock bajo <span class="stat-ico" style="background:var(--redl);color:var(--red)"><x-icon name="alert" size="14" /></span></div>
            <div class="stat-val" x-text="contar('bajo')"></div>
            <div class="stat-ch" :class="contar('bajo') ? 'dn' : 'neutral'" x-text="contar('bajo') ? 'Requieren reposición' : 'Todo en orden'"></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Valor de inventario <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="cash" size="14" /></span></div>
            <div class="stat-val" x-text="gh.soles(valorInventario)"></div>
            <div class="stat-ch neutral">Precio de venta Ã— stock</div>
        </div>
    </div>

    <div class="panel fade-in" style="animation-delay:.18s">
        <div class="tabs">
            <button class="tab" :class="{ on: vista === 'productos' }" @click="vista = 'productos'">Productos <span class="n" x-text="productos.length"></span></button>
            <button class="tab" :class="{ on: vista === 'categorias' }" @click="vista = 'categorias'">Categorías <span class="n" x-text="categorias.length"></span></button>
        </div>

        {{-- ===== PRODUCTOS ===== --}}
        <div x-show="vista === 'productos'">
            <div class="panel-hd">
                <div class="chips">
                    <button class="chip" :class="{ on: filtro === 'todos' }" @click="filtro = 'todos'; pagina = 1">Todos<span class="n" x-text="productos.length"></span></button>
                    <button class="chip" :class="{ on: filtro === 'activos' }" @click="filtro = 'activos'; pagina = 1">Publicados<span class="n" x-text="contar('activos')"></span></button>
                    <button class="chip" :class="{ on: filtro === 'bajo' }" @click="filtro = 'bajo'; pagina = 1">Stock bajo<span class="n" x-text="contar('bajo')"></span></button>
                    <button class="chip" :class="{ on: filtro === 'pausado' }" @click="filtro = 'pausado'; pagina = 1">Pausados<span class="n" x-text="contar('pausado')"></span></button>
                </div>
                <div class="filtros-der">
                    <select class="inp" x-model="categoria" @change="pagina = 1" style="width:180px;">
                        <option value="">Todas las categorías</option>
                        <template x-for="cat in categorias" :key="cat.id"><option :value="cat.id" x-text="cat.nombre"></option></template>
                    </select>
                    <div class="search">
                        <x-icon name="search" size="15" />
                        <input class="inp" placeholder="Buscar productoâ€¦" x-model="buscar" @input="pagina = 1">
                    </div>
                </div>
            </div>
            <div class="loadbar" x-show="cargando" x-cloak></div>

            <div class="tbl-wrap" x-show="filtrados.length" x-cloak>
                <table class="tbl">
                    <thead>
                        <tr>
                            <th class="sortable" :class="{ sorted: orden.campo === 'nombre' }" @click="ordenar('nombre')">Producto <x-icon name="sort" size="11" /></th>
                            <th>Categoría</th>
                            <th class="sortable num" :class="{ sorted: orden.campo === 'precio' }" @click="ordenar('precio')">Precio <x-icon name="sort" size="11" /></th>
                            <th class="sortable" :class="{ sorted: orden.campo === 'stock' }" @click="ordenar('stock')" style="width:170px;">Stock <x-icon name="sort" size="11" /></th>
                            <th>Estado</th>
                            <th style="width:170px;text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(p, i) in visibles" :key="p.id">
                            <tr :class="{ 'row-new': p.id === resaltado, 'row-off': !p.activo, 'row-in': entrada }" :style="entrada ? '--d:' + (i * 50) + 'ms' : ''">
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div class="thumb-p" :style="p.imagen_url ? 'background-image:url(' + p.imagen_url + ')' : ''">
                                            <x-icon name="package" size="18" stroke="1.5" x-show="!p.imagen_url" />
                                            <span class="thumb-n" x-show="p.imagenes.length > 1" x-text="p.imagenes.length"></span>
                                        </div>
                                        <div style="min-width:0;">
                                            <div style="font-weight:600;display:flex;align-items:center;gap:6px;">
                                                <span x-text="p.nombre"></span>
                                                <span class="badge b-r" x-show="p.precio_oferta" style="font-size:10px;padding:1px 6px;">Oferta</span>
                                            </div>
                                            <div style="font-size:11px;color:var(--fg3);">
                                                <span x-text="p.sku ? 'SKU ' + p.sku : 'Sin código'"></span>
                                                <template x-if="p.tiene_variantes"><span class="var-tag" x-text="p.variantes.length + (p.variantes.length === 1 ? ' variante' : ' variantes')"></span></template>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="cat-tag" x-text="p.categoria || 'Sin categoría'"></span></td>
                                <td class="num">
                                    <div style="font-weight:700;" x-text="(p.tiene_variantes && p.variantes.length > 1 ? 'desde ' : '') + gh.soles(p.precio_oferta ?? (p.precio_desde ?? p.precio))"></div>
                                    <div class="pold" x-show="p.precio_oferta" x-text="gh.soles(p.precio)"></div>
                                </td>
                                <td>
                                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                                        <span style="font-weight:600;font-variant-numeric:tabular-nums;" x-text="p.stock + ' und.'"></span>
                                        <span style="color:var(--fg3);" x-text="p.tiene_variantes ? 'por variante' : 'mín. ' + p.stock_minimo"></span>
                                    </div>
                                    <div class="sbar"><span :class="p.estado === 'sin_stock' ? 'r' : (p.estado === 'bajo' ? 'a' : '')" :style="'width:' + barra(p) + '%'"></span></div>
                                </td>
                                <td><span class="badge" :class="badgeEstado(p.estado)"><span class="dot"></span><span x-text="textoEstado(p.estado)"></span></span></td>
                                <td>
                                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                                        <button class="btn btn-p btn-sm" x-show="p.estado === 'sin_stock' || p.estado === 'bajo'" @click="reponer(p)">Reponer</button>
                                        <button class="icon-btn sm" @click="abrir(p)" title="Editar"><x-icon name="pen" size="14" /></button>
                                        <button class="icon-btn sm" @click="abrir(p, 'galeria')" title="Galería de imágenes"><x-icon name="image" size="14" /></button>
                                        <button class="icon-btn sm" @click="alternar(p)" :title="p.activo ? 'Pausar' : 'Publicar'">
                                            <x-icon name="pause" size="14" x-show="p.activo" />
                                            <x-icon name="play" size="14" x-show="!p.activo" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="empty" x-show="!filtrados.length" x-cloak>
                <x-icon name="package" size="34" />
                <div style="font-weight:600;color:var(--fg2);" x-text="productos.length ? 'Ningún producto coincide con el filtro' : 'Aún no tienes productos'"></div>
                <button class="btn btn-p" style="margin-top:6px;" x-show="!productos.length" @click="abrir()"><x-icon name="plus" size="15" /> Crear el primero</button>
            </div>

            <div class="panel-ft" x-show="filtrados.length" x-cloak>
                <span x-text="'Mostrando ' + desde + 'â€“' + hasta + ' de ' + filtrados.length + ' productos'"></span>
                <div class="pager">
                    <button @click="pagina--; animar()" :disabled="pagina === 1" aria-label="Anterior"><x-icon name="chev-l" size="14" /></button>
                    <template x-for="n in paginas" :key="n"><button :class="{ on: n === pagina }" @click="pagina = n; animar()" x-text="n"></button></template>
                    <button @click="pagina++; animar()" :disabled="pagina === paginas" aria-label="Siguiente"><x-icon name="chev-r" size="14" /></button>
                </div>
            </div>
        </div>

        {{-- ===== CATEGORIAS (GEST-18) ===== --}}
        <div x-show="vista === 'categorias'" x-cloak>
            <div class="tbl-wrap" x-show="categorias.length">
                <table class="tbl">
                    <thead><tr><th>Categoría</th><th class="num">Productos</th><th>Estado</th><th style="width:120px;text-align:right;">Acciones</th></tr></thead>
                    <tbody>
                        <template x-for="(c, i) in categorias" :key="c.id">
                            <tr class="row-in" :class="{ 'row-new': c.id === resaltadoCat }" :style="'--d:' + (i * 40) + 'ms'">
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <span class="cat-ico"><x-icon name="layers" size="15" /></span>
                                        <span style="font-weight:600;" x-text="c.nombre"></span>
                                    </div>
                                </td>
                                <td class="num">
                                    <a href="#" class="link" @click.prevent="vista = 'productos'; categoria = String(c.id); filtro = 'todos'; pagina = 1" x-text="c.productos + (c.productos === 1 ? ' producto' : ' productos')"></a>
                                </td>
                                <td><span class="badge" :class="c.activo ? 'b-ok' : 'b-n'"><span class="dot"></span><span x-text="c.activo ? 'Visible' : 'Oculta'"></span></span></td>
                                <td>
                                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                                        <button class="icon-btn sm" @click="abrirCat(c)" title="Editar"><x-icon name="pen" size="14" /></button>
                                        <button class="icon-btn sm del" @click="confirmar = { tipo: 'categoria', item: c }" title="Eliminar"><x-icon name="trash" size="14" /></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="empty" x-show="!categorias.length">
                <x-icon name="layers" size="34" />
                <div style="font-weight:600;color:var(--fg2);">Aún no tienes categorías</div>
                <div>Agrupa tus productos (bebidas, postres, combosâ€¦) para ordenarlos en tu tienda.</div>
                <button class="btn btn-p" style="margin-top:6px;" @click="abrirCat()"><x-icon name="plus" size="15" /> Crear categoría</button>
            </div>
        </div>
    </div>

    @include('admin.productos-modales')
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .filtros-der{margin-left:auto;display:flex;gap:8px;align-items:center;}
        .thumb-p{position:relative;width:42px;height:42px;border-radius:8px;background:var(--surface2) center/cover no-repeat;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--fg3);flex-shrink:0;transition:transform .2s var(--ease);}
        .thumb-n{position:absolute;right:-5px;bottom:-5px;min-width:17px;height:17px;border-radius:99px;background:var(--fg);color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid #fff;padding:0 3px;}
        .tbl tbody tr:hover .thumb-p{transform:scale(1.05);}
        .row-off td{color:var(--fg3);}
        .row-off .thumb-p{opacity:.55;}
        .cat-tag{font-size:12px;color:var(--fg2);background:var(--surface2);border-radius:6px;padding:2px 8px;white-space:nowrap;}
        .var-tag{margin-left:6px;font-weight:600;color:var(--accent);background:var(--acl);border-radius:5px;padding:0 6px;}
        .cat-ico{width:30px;height:30px;border-radius:8px;background:var(--acl);color:var(--accent);display:flex;align-items:center;justify-content:center;}
        .link{color:var(--accent);font-weight:600;}
        .link:hover{text-decoration:underline;}
        .pold{font-size:11px;color:var(--fg3);text-decoration:line-through;}
        .sbar{height:4px;border-radius:99px;background:var(--surface2);margin-top:5px;overflow:hidden;}
        .sbar span{display:block;height:100%;border-radius:99px;background:var(--green);transition:width .5s var(--ease);}
        .sbar span.a{background:var(--amber);} .sbar span.r{background:var(--red);}
        .icon-btn.del:hover{background:var(--redl);color:var(--red);border-color:#FECACA;}
        .alerta-stock{display:flex;align-items:center;gap:12px;padding:12px 14px;margin-bottom:14px;border:1px solid #FCD9A8;background:#FFF8EE;border-radius:10px;font-size:13px;}
        .as-ico{width:36px;height:36px;border-radius:9px;background:var(--ambl);color:var(--amber);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .as-lista{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;}
        .as-item{display:inline-flex;gap:6px;align-items:center;font:inherit;font-size:12px;background:#fff;border:1px solid var(--border);border-radius:99px;padding:3px 10px;cursor:pointer;color:var(--fg2);transition:border-color .15s,transform .15s var(--ease);}
        .as-item:hover{border-color:var(--amber);transform:translateY(-1px);}
        .as-item b.a{color:var(--amber);} .as-item b.r{color:var(--red);}
        @media (max-width:860px){
            .filtros-der{width:100%;margin-left:0;}
            .filtros-der select{width:45% !important;}
            .filtros-der .search{flex:1;}
            .alerta-stock{flex-wrap:wrap;}
        }
    </style>
@endpush

@push('scripts')
    @include('admin.productos-script')
@endpush
