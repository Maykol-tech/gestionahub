@extends('layouts.app')

@section('titulo', 'Punto de venta')
@section('subtitulo', 'Venta rápida con IGV, cobro y boleta o factura electrónica')

@section('acciones')
    <a href="{{ route('admin.comprobantes') }}" class="btn btn-g" style="display:inline-flex;align-items:center;gap:6px;"><x-icon name="layers" size="15" /> Comprobantes</a>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .pos{display:grid;grid-template-columns:minmax(0,1fr) 390px;gap:16px;align-items:start;}
        .pos-hd{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;}
        .pos-hd .inp-ico{flex:1;min-width:220px;}
        .pos-hd .inp{height:42px;font-size:14px;}
        .kbd{font-size:10px;font-weight:700;color:var(--fg3);border:1px solid var(--border);border-bottom-width:2px;border-radius:5px;padding:1px 5px;background:#fff;}
        .prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;max-height:calc(100vh - 330px);min-height:280px;overflow:auto;padding:2px;}
        .prod{position:relative;border:1px solid var(--border);border-radius:14px;padding:12px;background:#fff;text-align:left;cursor:pointer;font-family:inherit;transition:transform .18s var(--ease),box-shadow .18s var(--ease),border-color .18s;}
        .prod:hover:not(:disabled){transform:translateY(-3px);box-shadow:0 10px 24px rgba(26,35,50,.10);border-color:var(--acm);}
        .prod:active:not(:disabled){transform:scale(.97);}
        .prod:disabled{opacity:.45;cursor:not-allowed;}
        .prod.sel{border-color:var(--accent);box-shadow:0 0 0 3px var(--acl);}
        .prod-av{width:38px;height:38px;border-radius:10px;background:var(--acl);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;margin-bottom:8px;overflow:hidden;}
        .prod-av img{width:100%;height:100%;object-fit:cover;}
        .prod-nom{font-weight:600;font-size:13px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:34px;}
        .prod-pre{font-size:14px;font-weight:800;margin-top:6px;font-variant-numeric:tabular-nums;}
        .prod-stk{position:absolute;top:10px;right:10px;}
        .prod-qty{position:absolute;top:-7px;left:-7px;min-width:22px;height:22px;border-radius:99px;background:var(--cta);color:#fff;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 6px;box-shadow:0 4px 10px rgba(244,123,32,.4);}
        .cart{position:sticky;top:12px;}
        .cart-bd{padding:4px 16px 0;max-height:250px;overflow:auto;}
        .linea{display:flex;align-items:center;gap:8px;padding:10px 0;border-bottom:1px dashed var(--border);font-size:13px;}
        .linea:last-child{border-bottom:none;}
        .qty{display:flex;align-items:center;border:1px solid var(--border);border-radius:9px;overflow:hidden;}
        .qty button{width:26px;height:28px;border:none;background:#fff;cursor:pointer;font-size:15px;color:var(--fg2);}
        .qty button:hover:not(:disabled){background:var(--surface2);}
        .qty input{width:38px;height:28px;border:none;border-left:1px solid var(--border);border-right:1px solid var(--border);text-align:center;font:inherit;font-weight:700;font-size:13px;}
        .qty input:focus{outline:none;background:var(--acl);}
        .tot{padding:12px 16px;border-top:1px solid var(--border);background:var(--surface3);}
        .tot-r{display:flex;justify-content:space-between;font-size:12px;color:var(--fg2);padding:2px 0;font-variant-numeric:tabular-nums;}
        .tot-t{display:flex;justify-content:space-between;align-items:baseline;margin-top:6px;padding-top:8px;border-top:1px dashed var(--border);}
        .tot-t b{font-size:28px;font-weight:800;letter-spacing:-.6px;font-variant-numeric:tabular-nums;}
        .sec{padding:12px 16px;border-top:1px solid var(--border);}
        .sec-ttl{font-size:11px;font-weight:700;color:var(--fg3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
        .segm{display:grid;grid-template-columns:repeat(3,1fr);background:var(--surface2);border-radius:10px;padding:3px;gap:3px;}
        .segm button{border:none;background:none;border-radius:8px;padding:7px 4px;font:inherit;font-size:12px;font-weight:700;color:var(--fg2);cursor:pointer;transition:all .18s var(--ease);}
        .segm button.on{background:#fff;color:var(--fg);box-shadow:0 2px 6px rgba(26,35,50,.10);}
        .metodos{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;}
        .metodo{border:1.5px solid var(--border);border-radius:12px;padding:9px 4px 7px;background:#fff;font:inherit;font-size:11px;font-weight:700;color:var(--fg2);cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:all .18s var(--ease);}
        .metodo .mi{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px;}
        .metodo:hover{border-color:var(--acm);}
        .metodo.on{border-color:var(--accent);background:var(--acl);color:var(--fg);box-shadow:0 4px 12px rgba(75,124,245,.18);}
        .m-efectivo .mi{background:#16A34A;} .m-tarjeta .mi{background:#2563EB;} .m-yape .mi{background:#7E22CE;} .m-plin .mi{background:#0D9488;}
        .rapidos{display:flex;gap:5px;flex-wrap:wrap;margin-top:8px;}
        .rapidos button{border:1px solid var(--border);background:#fff;border-radius:99px;padding:4px 10px;font:inherit;font-size:11px;font-weight:700;color:var(--fg2);cursor:pointer;}
        .rapidos button:hover{border-color:var(--accent);color:var(--accent);}
        .vuelto{display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding:8px 10px;border-radius:10px;background:#F0FDF4;color:#15803D;font-size:13px;font-weight:700;}
        .vuelto.falta{background:#FEF2F2;color:#B91C1C;}
        .cobrar{width:100%;height:50px;font-size:15px;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px;}
        .ok-ico{width:72px;height:72px;border-radius:50%;background:#DCFCE7;color:#16A34A;display:flex;align-items:center;justify-content:center;margin:4px auto 12px;}
        .ok-total{font-size:32px;font-weight:800;letter-spacing:-.8px;text-align:center;}
        .ok-kv{display:flex;justify-content:space-between;font-size:13px;padding:6px 0;border-bottom:1px dashed var(--border);}
        .ok-kv:last-child{border-bottom:none;}
        .vacio-cart{padding:26px 10px;text-align:center;color:var(--fg3);font-size:13px;}
        .vacio-cart .ic{opacity:.5;margin-bottom:6px;}
        @media (max-width:1100px){.pos{grid-template-columns:1fr;}.cart{position:static;}.prod-grid{max-height:none;}}
    </style>
@endpush

@section('contenido')
<div x-data="ghPos(@js([
        'productos' => $productos,
        'categorias' => $categorias,
        'metodos' => $metodos,
        'comprobantes' => $comprobantes,
        'tasa' => $tasaIgv,
        'facturacion' => $facturacion,
        'stats' => $stats,
        'ventas' => $ventas,
        'urls' => [
            'cobrar' => route('admin.pos.cobrar'),
            'ventas' => route('admin.pos.ventas'),
            'anular' => url('admin/pos/ventas/__ID__/anular'),
        ],
    ]))" x-init="iniciar()" @keydown.window="atajos($event)">

    {{-- KPIs del dia --}}
    <div class="stat-row">
        <div class="stat">
            <div class="stat-lbl">Ventas de hoy <span class="stat-ico" style="background:var(--acl);color:var(--accent);"><x-icon name="clipboard" size="14" /></span></div>
            <div class="stat-val" x-text="stats.ventas_hoy">{{ $stats['ventas_hoy'] }}</div>
            <div class="stat-ch neutral">en mostrador</div>
        </div>
        <div class="stat">
            <div class="stat-lbl">Cobrado hoy <span class="stat-ico" style="background:var(--greenl);color:var(--green);"><x-icon name="cash" size="14" /></span></div>
            <div class="stat-val" x-text="soles(stats.total_hoy)">S/ {{ number_format($stats['total_hoy'], 2) }}</div>
            <div class="stat-ch neutral" x-text="resumenMetodos()"></div>
        </div>
        <div class="stat">
            <div class="stat-lbl">IGV del día <span class="stat-ico" style="background:var(--ambl);color:var(--amber);"><x-icon name="layers" size="14" /></span></div>
            <div class="stat-val" x-text="soles(stats.igv_hoy)">S/ {{ number_format($stats['igv_hoy'], 2) }}</div>
            <div class="stat-ch neutral">18% incluido en precios</div>
        </div>
        <div class="stat">
            <div class="stat-lbl">Ticket promedio <span class="stat-ico" style="background:var(--ctl);color:var(--cta);"><x-icon name="trending" size="14" /></span></div>
            <div class="stat-val" x-text="soles(stats.ticket_promedio)">S/ {{ number_format($stats['ticket_promedio'], 2) }}</div>
            <div class="stat-ch neutral">por venta</div>
        </div>
    </div>

    <div class="pos">
        {{-- ============ Productos ============ --}}
        <div class="panel" style="padding:16px;">
            <div class="pos-hd">
                <div class="inp-ico">
                    <x-icon name="search" size="16" />
                    <input class="inp" x-ref="buscar" x-model="buscar" @keydown.enter.prevent="agregarPrimero()" @keydown.down.prevent="mover(1)" @keydown.up.prevent="mover(-1)"
                           placeholder="Buscar por nombre o código…" aria-label="Buscar producto" autocomplete="off">
                    <span class="inp-fin"><span class="kbd">F2</span></span>
                </div>
            </div>
            <div class="chips" style="margin-bottom:12px;" x-show="categorias.length" x-cloak>
                <button type="button" class="chip" :class="{ on: categoria === null }" @click="categoria = null">Todos <span class="n" x-text="productos.length"></span></button>
                <template x-for="c in categorias" :key="c.id">
                    <button type="button" class="chip" :class="{ on: categoria === c.id }" @click="categoria = c.id" x-text="c.nombre"></button>
                </template>
            </div>

            <div class="prod-grid" x-ref="grid">
                <template x-for="(p, i) in filtrados" :key="p.id">
                    <button type="button" class="prod" :class="{ sel: i === cursor && buscar }" :disabled="disponible(p) <= 0" @click="agregar(p, $event)" :title="p.nombre">
                        <span class="prod-qty" x-show="enCarrito(p.id)" x-text="enCarrito(p.id)"></span>
                        <div class="prod-av">
                            <template x-if="p.imagen"><img :src="p.imagen" alt="" loading="lazy"></template>
                            <template x-if="!p.imagen"><span x-text="p.nombre.charAt(0).toUpperCase()"></span></template>
                        </div>
                        <span class="badge prod-stk" :class="disponible(p) <= 3 ? 'b-a' : 'b-n'" x-text="disponible(p)"></span>
                        <div class="prod-nom" x-text="p.nombre"></div>
                        <div class="prod-pre" x-text="soles(p.precio)"></div>
                    </button>
                </template>
            </div>
            <div class="vacio-cart" x-show="!filtrados.length" x-cloak>
                <x-icon name="search" size="28" /><div>No hay productos con stock que coincidan.</div>
            </div>
        </div>

        {{-- ============ Venta actual ============ --}}
        <div class="panel cart" style="padding:0;">
            <div class="panel-hd" style="padding:14px 16px;">
                <span class="panel-ttl">Venta actual</span>
                <span class="badge b-g" x-show="unidades" x-text="unidades + (unidades === 1 ? ' unidad' : ' unidades')"></span>
                <button type="button" class="icon-btn" style="margin-left:auto;" x-show="carrito.length" x-cloak @click="vaciar()" title="Vaciar venta" aria-label="Vaciar venta"><x-icon name="trash" size="15" /></button>
            </div>

            <div class="cart-bd" x-ref="lineas">
                <div class="vacio-cart" x-show="!carrito.length">
                    <x-icon name="package" size="30" /><div>Toca un producto o búscalo y presiona <span class="kbd">Enter</span>.</div>
                </div>
                <template x-for="l in carrito" :key="l.producto_id">
                    <div class="linea" :data-linea="l.producto_id">
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="l.nombre"></div>
                            <div style="font-size:11px;color:var(--fg3);" x-text="soles(l.precio) + ' c/u'"></div>
                        </div>
                        <div class="qty">
                            <button type="button" @click="cambiar(l, -1)" aria-label="Quitar uno">−</button>
                            <input type="text" inputmode="numeric" :value="l.cantidad" @change="fijar(l, $event.target.value)" @focus="$event.target.select()" aria-label="Cantidad">
                            <button type="button" @click="cambiar(l, 1)" :disabled="l.cantidad >= stockDe(l.producto_id)" aria-label="Agregar uno">+</button>
                        </div>
                        <div style="width:72px;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;" x-text="soles(l.precio * l.cantidad)"></div>
                    </div>
                </template>
            </div>

            <div class="tot">
                <div class="tot-r"><span>Op. gravada</span><span x-text="soles(desglose.gravada)"></span></div>
                <div class="tot-r"><span>IGV (18%)</span><span x-text="soles(desglose.igv)"></span></div>
                <div class="tot-t"><span style="font-size:13px;font-weight:700;color:var(--fg2);">Total a cobrar</span><b x-ref="total" x-text="soles(desglose.total)"></b></div>
            </div>

            {{-- Comprobante --}}
            <div class="sec">
                <div class="sec-ttl" style="display:flex;justify-content:space-between;">
                    <span>Comprobante</span>
                    <span class="badge b-v" x-show="facturacion.simulado && tipo !== 'ticket'" x-cloak title="Configura Nubefact en Comprobantes para enviar a SUNAT">Modo simulado</span>
                </div>
                <div class="segm">
                    <template x-for="(nombre, clave) in comprobantes" :key="clave">
                        <button type="button" :class="{ on: tipo === clave }" @click="elegirTipo(clave)" x-text="nombre"></button>
                    </template>
                </div>

                <div x-show="tipo !== 'ticket'" x-cloak x-transition.opacity style="margin-top:10px;">
                    <div class="field" style="margin-bottom:8px;">
                        <div class="inp-ico">
                            <x-icon name="card" size="15" />
                            <input class="inp" x-model="documento" inputmode="numeric" maxlength="11" @input="validarCampo('documento')"
                                   :class="{ err: errores.cliente_documento, ok: docValido && documento }"
                                   :placeholder="tipo === 'factura' ? 'RUC (11 dígitos)' : 'DNI (opcional, obligatorio desde S/ 700)'" :aria-label="tipo === 'factura' ? 'RUC' : 'DNI'">
                        </div>
                        <div class="ferr" x-text="primero('cliente_documento')"></div>
                    </div>
                    <div class="field" style="margin-bottom:8px;">
                        <div class="inp-ico">
                            <x-icon name="users" size="15" />
                            <input class="inp" x-model="cliente" maxlength="150" @input="validarCampo('cliente')" :class="{ err: errores.cliente_nombre }"
                                   :placeholder="tipo === 'factura' ? 'Razón social' : 'Nombre del cliente (opcional)'" aria-label="Cliente">
                        </div>
                        <div class="ferr" x-text="primero('cliente_nombre')"></div>
                    </div>
                    <div class="field" style="margin-bottom:0;" x-show="tipo === 'factura'">
                        <div class="inp-ico">
                            <x-icon name="map-pin" size="15" />
                            <input class="inp" x-model="direccion" maxlength="255" placeholder="Dirección fiscal (opcional)" aria-label="Dirección">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Metodo de pago --}}
            <div class="sec">
                <div class="sec-ttl">Método de pago</div>
                <div class="metodos">
                    <template x-for="(nombre, clave) in metodos" :key="clave">
                        <button type="button" class="metodo" :class="['m-' + clave, { on: metodo === clave }]" @click="elegirMetodo(clave)">
                            <span class="mi" x-text="nombre.charAt(0)"></span><span x-text="nombre"></span>
                        </button>
                    </template>
                </div>

                <div x-show="metodo === 'efectivo'" x-cloak style="margin-top:10px;">
                    <div class="inp-ico">
                        <x-icon name="cash" size="15" />
                        <input class="inp" x-ref="recibido" type="number" min="0" step="0.10" x-model.number="recibido" placeholder="Efectivo recibido"
                               :class="{ err: errores.recibido || falta > 0 }" aria-label="Efectivo recibido" @keydown.enter.prevent="cobrar()">
                    </div>
                    <div class="rapidos">
                        <button type="button" @click="recibido = desglose.total">Exacto</button>
                        <template x-for="b in billetes" :key="b"><button type="button" @click="recibido = b" x-text="'S/ ' + b"></button></template>
                    </div>
                    <div class="vuelto" :class="{ falta: falta > 0 }" x-show="recibido !== null && recibido !== '' && desglose.total > 0">
                        <span x-text="falta > 0 ? 'Falta' : 'Vuelto'"></span><span x-text="soles(falta > 0 ? falta : vuelto)"></span>
                    </div>
                    <div class="ferr" x-text="primero('recibido')"></div>
                </div>
                <div x-show="metodo === 'yape' || metodo === 'plin'" x-cloak style="margin-top:10px;font-size:12px;color:var(--fg2);" class="nota">
                    <x-icon name="phone" size="14" /> Verifica la notificación del pago en el celular del negocio antes de cobrar.
                </div>
            </div>

            <div class="sec" style="padding-bottom:16px;">
                <div class="ferr" style="margin:0 0 8px;" x-text="primero('items')"></div>
                <button class="btn btn-p cobrar" type="button" :disabled="!carrito.length || guardando || falta > 0" @click="cobrar()">
                    <x-icon name="check" size="17" />
                    <span x-text="guardando ? 'Cobrando…' : 'Cobrar ' + soles(desglose.total)"></span>
                    <span class="kbd" style="background:rgba(255,255,255,.2);color:#fff;border-color:rgba(255,255,255,.35);">F9</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============ Ventas del dia ============ --}}
    <div class="panel" style="padding:0;margin-top:16px;">
        <div class="panel-hd" style="padding:14px 16px;">
            <span class="panel-ttl">Ventas de hoy</span>
            <span class="panel-sub" x-text="ventas.length + ' registradas'"></span>
            <button type="button" class="icon-btn" style="margin-left:auto;" @click="refrescar()" title="Actualizar" aria-label="Actualizar"><x-icon name="refresh" size="15" /></button>
        </div>
        <div class="tbl-wrap" x-show="ventas.length">
            <table class="tbl">
                <thead><tr><th>Hora</th><th>Venta</th><th>Comprobante</th><th>Cliente</th><th>Pago</th><th class="num">Total</th><th>Estado</th><th></th></tr></thead>
                <tbody x-ref="tabla">
                    <template x-for="v in ventas" :key="v.id">
                        <tr>
                            <td x-text="v.hora"></td>
                            <td style="font-weight:700;" x-text="v.numero"></td>
                            <td>
                                <template x-if="v.comprobante">
                                    <span>
                                        <span class="badge" :class="v.comprobante.badge" x-text="v.comprobante.numero"></span>
                                        <span class="badge b-v" x-show="v.comprobante.simulado" style="margin-left:2px;">simulado</span>
                                    </span>
                                </template>
                                <template x-if="!v.comprobante"><span class="badge b-n" x-text="comprobantes[v.tipo_comprobante] || 'Nota de venta'"></span></template>
                            </td>
                            <td x-text="v.cliente || '—'"></td>
                            <td><span class="badge" :class="badgeMetodo(v.metodo)" x-text="v.metodo_nombre"></span></td>
                            <td class="num" style="font-weight:700;" x-text="soles(v.total)"></td>
                            <td><span class="badge" :class="v.estado === 'anulada' ? 'b-r' : 'b-ok'" x-text="v.estado === 'anulada' ? 'Anulada' : 'Pagada'"></span></td>
                            <td style="text-align:right;white-space:nowrap;">
                                <a class="icon-btn" :href="v.ticket_url" target="_blank" rel="noopener" data-recargar="1" title="Imprimir ticket" aria-label="Imprimir ticket"><x-icon name="download" size="14" /></a>
                                <button type="button" class="icon-btn" x-show="v.estado !== 'anulada'" @click="pedirAnular(v)" title="Anular venta" aria-label="Anular venta"><x-icon name="x" size="14" /></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="vacio-cart" x-show="!ventas.length"><x-icon name="clipboard" size="28" /><div>Aún no hay ventas hoy. ¡La primera está a un clic!</div></div>
    </div>

    {{-- ============ Modal: venta cobrada ============ --}}
    <div class="modal-bg" x-show="exito" x-cloak @keydown.escape.window="exito && nuevaVenta()" style="align-items:center;">
        <div class="modal" style="max-width:400px;" @click.outside="nuevaVenta()">
            <div class="modal-bd" style="padding:24px;">
                <div class="ok-ico" x-ref="okIco"><x-icon name="check" size="36" stroke="3" /></div>
                <div style="text-align:center;font-size:13px;color:var(--fg3);" x-text="exito ? 'Venta ' + exito.venta.numero + ' cobrada' : ''"></div>
                <div class="ok-total" x-text="exito ? soles(exito.venta.total) : ''"></div>
                <div style="margin-top:14px;">
                    <div class="ok-kv"><span>Método</span><b x-text="exito && exito.venta.metodo_nombre"></b></div>
                    <div class="ok-kv" x-show="exito && exito.venta.metodo === 'efectivo'"><span>Vuelto</span><b style="color:#15803D;" x-text="exito && soles(exito.venta.vuelto)"></b></div>
                    <div class="ok-kv"><span>Op. gravada / IGV</span><span x-text="exito && (soles(exito.venta.gravada) + ' / ' + soles(exito.venta.igv))"></span></div>
                    <div class="ok-kv" x-show="exito && exito.venta.comprobante">
                        <span x-text="exito && exito.venta.comprobante && exito.venta.comprobante.tipo"></span>
                        <span class="badge" :class="exito && exito.venta.comprobante && exito.venta.comprobante.badge" x-text="exito && exito.venta.comprobante && exito.venta.comprobante.numero"></span>
                    </div>
                </div>
                <div class="nota" style="margin-top:12px;color:#B45309;background:#FFFBEB;" x-show="exito && exito.aviso" x-text="exito && exito.aviso"></div>
            </div>
            <div class="modal-ft" style="flex-wrap:wrap;justify-content:center;gap:6px;">
                <a class="btn btn-g" :href="exito ? exito.venta.ticket_url : '#'" target="_blank" rel="noopener" data-recargar="1"><x-icon name="download" size="14" /> Ticket</a>
                <a class="btn btn-g" x-show="exito && exito.venta.comprobante && exito.venta.comprobante.pdf" :href="exito && exito.venta.comprobante ? exito.venta.comprobante.pdf : '#'" target="_blank" rel="noopener" data-recargar="1"><x-icon name="eye" size="14" /> Comprobante</a>
                <button class="btn btn-p" type="button" @click="nuevaVenta()" x-ref="nueva">Nueva venta</button>
            </div>
        </div>
    </div>

    {{-- ============ Modal: anular venta ============ --}}
    <div class="modal-bg" x-show="anulando" x-cloak @keydown.escape.window="anulando = null" style="align-items:center;">
        <form class="modal" style="max-width:440px;" @submit.prevent="anular()" @click.outside="anulando = null" novalidate>
            <div class="modal-hd">
                <div class="modal-ico" style="background:var(--redl);color:var(--red);"><x-icon name="alert" size="18" /></div>
                <div><div class="modal-ttl" x-text="anulando ? 'Anular ' + anulando.numero : ''"></div><div class="modal-sub">Se devolverá el stock de los productos.</div></div>
            </div>
            <div class="modal-bd">
                <div class="nota" x-show="anulando && anulando.comprobante && anulando.comprobante.estado === 'aceptado'" style="margin-bottom:12px;">
                    <x-icon name="layers" size="14" /> <span>Se emitirá una <b>nota de crédito</b> por devolución total de <span x-text="anulando && anulando.comprobante ? anulando.comprobante.numero : ''"></span>.</span>
                </div>
                <label class="lbl" for="pos-motivo">Motivo <span class="req">*</span></label>
                <div class="inp-ico">
                    <x-icon name="pen" size="15" />
                    <input class="inp" id="pos-motivo" x-ref="motivo" x-model="motivo" maxlength="200" placeholder="Ej. El cliente devolvió el producto" :class="{ err: errorMotivo, ok: motivo.trim().length >= 3 }" @input="errorMotivo = ''">
                </div>
                <div class="ferr" x-text="errorMotivo"></div>
            </div>
            <div class="modal-ft">
                <button class="btn btn-g" type="button" @click="anulando = null">Cancelar</button>
                <button class="btn btn-p" type="submit" style="background:var(--red);border-color:var(--red);" :disabled="guardando" x-text="guardando ? 'Anulando…' : 'Anular venta'"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
window.ghPos = function (cfg) {
    const r2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
    return {
        productos: cfg.productos, categorias: cfg.categorias, metodos: cfg.metodos, comprobantes: cfg.comprobantes,
        facturacion: cfg.facturacion, stats: cfg.stats, ventas: cfg.ventas,
        buscar: '', categoria: null, cursor: 0,
        carrito: [], metodo: 'efectivo', recibido: null,
        tipo: 'ticket', documento: '', cliente: '', direccion: '',
        errores: {}, guardando: false, exito: null,
        anulando: null, motivo: '', errorMotivo: '',

        iniciar() {
            this.$nextTick(() => this.$refs.buscar && this.$refs.buscar.focus());
            this.$watch('buscar', () => { this.cursor = 0; });
            this.$watch('desglose', (n, v) => { if (v && n.total !== v.total) this.pulso(this.$refs.total); });
        },

        soles(n) { return gh.soles(n); },
        primero(c) { return this.errores[c] ? this.errores[c][0] : ''; },

        get filtrados() {
            const q = gh.norm(this.buscar.trim());
            return this.productos.filter(p =>
                (this.categoria === null || p.categoria_id === this.categoria)
                && (!q || gh.norm(p.nombre).includes(q) || gh.norm(p.sku || '').includes(q))
            );
        },
        get unidades() { return this.carrito.reduce((a, l) => a + l.cantidad, 0); },

        // Mismo calculo que el servidor y SUNAT: precio con IGV incluido, redondeo por linea
        get desglose() {
            let gravada = 0, igv = 0, total = 0;
            for (const l of this.carrito) {
                const t = r2(l.precio * l.cantidad);
                const v = r2(t / (1 + cfg.tasa));
                gravada += v; igv += r2(t - v); total += t;
            }
            return { gravada: r2(gravada), igv: r2(igv), total: r2(total) };
        },
        get falta() { return this.metodo === 'efectivo' && this.recibido !== null && this.recibido !== '' ? Math.max(0, r2(this.desglose.total - Number(this.recibido))) : 0; },
        get vuelto() { return Math.max(0, r2(Number(this.recibido || 0) - this.desglose.total)); },
        get billetes() {
            const t = this.desglose.total, out = [];
            for (const b of [10, 20, 50, 100, 200]) if (b > t && out.length < 4) out.push(b);
            return out;
        },
        get docValido() {
            const d = this.documento.replace(/\D/g, '');
            return this.tipo === 'factura' ? (d.length === 11 && /^(10|15|17|20)/.test(d)) : (d === '' || d.length === 8 || d.length === 11);
        },

        stockDe(id) { const p = this.productos.find(x => x.id === id); return p ? p.stock : 0; },
        enCarrito(id) { const l = this.carrito.find(x => x.producto_id === id); return l ? l.cantidad : 0; },
        disponible(p) { return p.stock - this.enCarrito(p.id); },

        agregar(p, ev) {
            const actual = this.carrito.find(x => x.producto_id === p.id);
            if (this.disponible(p) <= 0) { Alpine.store('toasts').push('No hay más stock de ' + p.nombre, 'error'); return; }
            if (actual) actual.cantidad++;
            else this.carrito.push({ producto_id: p.id, nombre: p.nombre, precio: p.precio, cantidad: 1 });
            delete this.errores.items;
            if (ev && ev.currentTarget && typeof anime === 'function') anime({ targets: ev.currentTarget, scale: [.94, 1], duration: 380, easing: 'easeOutBack' });
            this.$nextTick(() => {
                const fila = this.$refs.lineas.querySelector(`[data-linea="${p.id}"]`);
                if (fila) { fila.scrollIntoView({ block: 'nearest' }); if (typeof anime === 'function') anime({ targets: fila, backgroundColor: ['#EEF3FF', 'rgba(0,0,0,0)'], duration: 700, easing: 'easeOutQuad' }); }
            });
        },
        agregarPrimero() {
            const p = this.filtrados[this.cursor];
            if (p) { this.agregar(p); this.buscar = ''; }
        },
        mover(d) { this.cursor = Math.max(0, Math.min(this.filtrados.length - 1, this.cursor + d)); },
        cambiar(l, d) { this.fijar(l, l.cantidad + d); },
        fijar(l, valor) {
            const n = Math.floor(Number(valor));
            if (!n || n <= 0) { this.carrito = this.carrito.filter(x => x.producto_id !== l.producto_id); return; }
            const max = this.stockDe(l.producto_id);
            if (n > max) Alpine.store('toasts').push('Solo hay ' + max + ' en stock', 'error');
            l.cantidad = Math.min(n, max);
        },
        vaciar() { this.carrito = []; this.recibido = null; this.errores = {}; },

        elegirTipo(t) {
            this.tipo = t; this.errores = {};
            if (t === 'factura') this.$nextTick(() => this.$el.querySelector('[aria-label="RUC"]')?.focus());
        },
        elegirMetodo(m) {
            this.metodo = m; delete this.errores.recibido;
            if (m === 'efectivo') this.$nextTick(() => this.$refs.recibido && this.$refs.recibido.focus());
        },

        validarCampo(campo) {
            const e = { ...this.errores };
            if (campo === 'documento') {
                delete e.cliente_documento;
                const d = this.documento.replace(/\D/g, '');
                if (this.documento !== d) this.documento = d;
                if (this.tipo === 'factura' && d.length >= 11 && !this.docValido) e.cliente_documento = ['El RUC tiene 11 dígitos y empieza con 10 o 20.'];
                if (this.tipo === 'boleta' && d.length > 8 && d.length < 11) e.cliente_documento = ['El DNI tiene 8 dígitos y el RUC 11.'];
            }
            if (campo === 'cliente') delete e.cliente_nombre;
            this.errores = e;
        },
        validarTodo() {
            const e = {};
            const d = this.documento.replace(/\D/g, '');
            if (this.tipo === 'factura') {
                if (!this.docValido) e.cliente_documento = ['Para factura ingresa un RUC válido de 11 dígitos.'];
                if (!this.cliente.trim()) e.cliente_nombre = ['Para factura escribe la razón social.'];
            }
            if (this.tipo === 'boleta') {
                if (d && !this.docValido) e.cliente_documento = ['El DNI tiene 8 dígitos y el RUC 11.'];
                if (this.desglose.total >= 700 && d.length !== 8) e.cliente_documento = ['Desde S/ 700 la boleta necesita el DNI (8 dígitos).'];
            }
            if (this.metodo === 'efectivo' && this.falta > 0) e.recibido = ['El efectivo recibido no alcanza.'];
            this.errores = e;
            return !Object.keys(e).length;
        },

        async cobrar() {
            if (!this.carrito.length || this.guardando) return;
            if (!this.validarTodo()) { this.sacudir(); return; }
            this.guardando = true;
            const r = await gh.api(cfg.urls.cobrar, { method: 'POST', body: {
                items: this.carrito.map(l => ({ producto_id: l.producto_id, cantidad: l.cantidad })),
                metodo_pago: this.metodo,
                recibido: this.metodo === 'efectivo' ? (this.recibido === null || this.recibido === '' ? this.desglose.total : this.recibido) : null,
                tipo_comprobante: this.tipo,
                cliente_nombre: this.cliente.trim() || null,
                cliente_documento: this.documento || null,
                cliente_direccion: this.direccion.trim() || null,
            } });
            this.guardando = false;
            if (r.ok) {
                // Descuenta el stock en pantalla y actualiza KPIs y ventas
                for (const l of this.carrito) { const p = this.productos.find(x => x.id === l.producto_id); if (p) p.stock -= l.cantidad; }
                this.stats = r.data.stats;
                this.ventas = [r.data.venta, ...this.ventas];
                this.exito = { venta: r.data.venta, aviso: r.data.aviso };
                Alpine.store('toasts').push(r.data.message);
                this.$nextTick(() => {
                    if (typeof anime === 'function') anime({ targets: this.$refs.okIco, scale: [0, 1], rotate: ['-25deg', '0deg'], duration: 700, delay: 120, easing: 'easeOutElastic(1, .6)' });
                    this.$refs.nueva && this.$refs.nueva.focus();
                    window.ghAnim && ghAnim.filas(this.$el);
                });
            } else if (r.status === 422 && r.data.errors) {
                this.errores = r.data.errors; this.sacudir();
            } else if (r.status !== 423) {
                Alpine.store('toasts').push(r.data.message || 'No se pudo cobrar la venta.', 'error');
            }
        },

        nuevaVenta() {
            if (!this.exito) return;
            this.exito = null; this.vaciar();
            this.tipo = 'ticket'; this.documento = ''; this.cliente = ''; this.direccion = ''; this.metodo = 'efectivo';
            this.productos = this.productos.filter(p => p.stock > 0);
            this.$nextTick(() => this.$refs.buscar.focus());
        },

        pedirAnular(v) {
            this.anulando = v; this.motivo = ''; this.errorMotivo = '';
            this.$nextTick(() => this.$refs.motivo.focus());
        },
        async anular() {
            if (this.motivo.trim().length < 3) { this.errorMotivo = 'Describe el motivo (mínimo 3 letras).'; return; }
            this.guardando = true;
            const v = this.anulando;
            const r = await gh.api(cfg.urls.anular.replace('__ID__', v.id), { method: 'POST', body: { motivo: this.motivo.trim() } });
            this.guardando = false;
            if (r.ok) {
                this.ventas = this.ventas.map(x => x.id === v.id ? r.data.venta : x);
                this.stats = r.data.stats;
                this.anulando = null;
                Alpine.store('toasts').push(r.data.message);
                gh.ir(location.pathname, false); // recarga el catalogo con el stock devuelto
            } else if (r.status === 422) {
                this.errorMotivo = (r.data.errors && r.data.errors.motivo && r.data.errors.motivo[0]) || r.data.message;
            } else if (r.status !== 423) {
                Alpine.store('toasts').push(r.data.message || 'No se pudo anular la venta.', 'error');
            }
        },

        async refrescar() {
            const r = await gh.api(cfg.urls.ventas);
            if (r.ok) { this.ventas = r.data.ventas; this.stats = r.data.stats; this.$nextTick(() => window.ghAnim && ghAnim.filas(this.$el)); }
        },

        atajos(e) {
            if (this.exito || this.anulando) return;
            if (e.key === 'F2') { e.preventDefault(); this.$refs.buscar.focus(); }
            if (e.key === 'F9') { e.preventDefault(); this.cobrar(); }
        },

        resumenMetodos() {
            const m = this.stats.por_metodo || {};
            const partes = Object.keys(m).map(k => (this.metodos[k] || k) + ' ' + gh.soles(m[k]));
            return partes.length ? partes.join(' · ') : 'sin cobros todavía';
        },
        badgeMetodo(m) { return { efectivo: 'b-ok', tarjeta: 'b-g', yape: 'b-v', plin: 'b-t' }[m] || 'b-n'; },
        pulso(el) { if (el && typeof anime === 'function') anime({ targets: el, scale: [1.08, 1], duration: 360, easing: 'easeOutQuad' }); },
        sacudir() {
            const el = this.$el.querySelector('.cart');
            if (el && typeof anime === 'function') anime({ targets: el, translateX: [0, -8, 8, -5, 5, 0], duration: 420, easing: 'easeInOutSine' });
        },
    };
};
gh.ready(() => Alpine.data('ghPos', window.ghPos));
</script>
@endpush
@endsection
