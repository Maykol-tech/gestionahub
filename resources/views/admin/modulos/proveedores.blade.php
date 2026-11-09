@extends('layouts.app')

@section('titulo', 'Proveedores')
@section('subtitulo', 'Tus proveedores y órdenes de compra')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .estado-chip { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; white-space:nowrap; }
        .acciones { display:flex; gap:6px; justify-content:flex-end; align-items:center; flex-wrap:wrap; }
        .campos-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .fila-item { display:grid; grid-template-columns:1.2fr 1fr 80px 110px auto; gap:8px; align-items:center; margin-bottom:8px; }
        .total-prev { font-size:14px; background:var(--surface-2, #F6F8FB); border-radius:10px; padding:10px 12px; display:flex; justify-content:space-between; }
        @media (max-width: 720px) { .campos-2 { grid-template-columns:1fr; } .fila-item { grid-template-columns:1fr 1fr; } }
    </style>
@endpush

@section('contenido')
<div x-data="ordenesCompra(@js($proveedores), @js($insumos), @js($ordenes))" @keydown.escape.window="cerrar()">

    {{-- Indicadores --}}
    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Proveedores</div><div class="stat-val">{{ $stats['proveedores'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Ã“rdenes abiertas</div><div class="stat-val">{{ $stats['abiertas'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Compras recibidas (mes)</div><div class="stat-val">S/ {{ number_format($stats['gasto_mes'], 2) }}</div></div>
    </div>

    {{-- Ordenes de compra --}}
    <div class="panel fade-in" style="animation-delay:.1s">
        <div class="panel-hd">
            <span style="font-weight:700;">Ã“rdenes de compra</span>
            <button class="btn btn-p" type="button" style="margin-left:auto;" @click="abrirNueva()" :disabled="!proveedores.length">
                <x-icon name="plus" size="15" stroke="2.4" /> Nueva orden
            </button>
        </div>
        <div class="tbl-wrap" x-show="ordenes.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th class="num">Total</th>
                        <th>Estado</th>
                        <th style="width:330px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(o, i) in ordenes" :key="o.id">
                        <tr class="row-in" :style="'--d:' + (i * 35) + 'ms'">
                            <td>
                                <div style="font-weight:600;" x-text="o.numero"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="o.items + (o.items === 1 ? ' producto' : ' productos')"></div>
                            </td>
                            <td x-text="o.proveedor"></td>
                            <td x-text="o.fecha"></td>
                            <td class="num" x-text="money(o.total)"></td>
                            <td>
                                <span class="estado-chip" :style="'background:' + estados[o.estado].fondo + ';color:' + estados[o.estado].color" x-text="estados[o.estado].nombre"></span>
                            </td>
                            <td>
                                <div class="acciones">
                                    <button class="btn btn-g btn-sm" type="button" x-show="o.estado === 'borrador'" x-cloak @click="cambiar(o, 'enviada')">Marcar enviada</button>
                                    <button class="btn btn-p btn-sm" type="button" x-show="o.estado === 'enviada'" x-cloak @click="cambiar(o, 'recibida')">Recibir mercadería</button>
                                    <button class="btn btn-g btn-sm" type="button" x-show="o.estado === 'borrador' || o.estado === 'enviada'" x-cloak @click="cambiar(o, 'cancelada')">Cancelar</button>
                                    <button class="icon-btn sm" type="button" x-show="o.estado === 'borrador' || o.estado === 'cancelada'" x-cloak @click="eliminar(o)" aria-label="Eliminar orden"><x-icon name="x" size="14" /></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="empty" x-show="!ordenes.length" x-cloak>
            <x-icon name="home" size="34" />
            <div style="font-weight:600;color:var(--fg2);">Aún no hay órdenes de compra</div>
            <div style="font-size:13px;color:var(--fg3);max-width:420px;text-align:center;">Crea una orden para un proveedor. Al recibirla, el stock de los insumos vinculados sube solo.</div>
        </div>
    </div>

    {{-- Proveedores (mismo CRUD que el resto de modulos) --}}
    <h2 style="font-size:15px;font-weight:700;margin:24px 0 10px;">Proveedores</h2>
    @include('admin.crud', ['cfg' => $cfgProveedores])

    {{-- Ventana: nueva orden --}}
    <div class="modal-bg" x-show="modal === 'orden'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" style="max-width:640px;" @submit.prevent="guardar()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="home" size="18" /></div>
                <div>
                    <div class="modal-ttl">Nueva orden de compra</div>
                    <div class="modal-sub">Se guarda como borrador. Puedes enviarla y recibirla luego.</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="campos-2">
                    <div class="field">
                        <label class="lbl" for="oc-proveedor">Proveedor *</label>
                        <select class="inp" id="oc-proveedor" x-model="f.proveedor_id" :class="{ err: errores.proveedor_id }">
                            <template x-for="p in proveedores" :key="p.id"><option :value="p.id" x-text="p.nombre"></option></template>
                        </select>
                        <div class="ferr" x-show="errores.proveedor_id" x-text="errores.proveedor_id ? errores.proveedor_id[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="oc-fecha">Fecha *</label>
                        <input class="inp" id="oc-fecha" type="date" x-model="f.fecha" :class="{ err: errores.fecha }">
                        <div class="ferr" x-show="errores.fecha" x-text="errores.fecha ? errores.fecha[0] : ''"></div>
                    </div>
                </div>

                <div class="field">
                    <label class="lbl">Productos *</label>
                    <template x-for="(it, idx) in f.items" :key="idx">
                        <div class="fila-item">
                            <select class="inp" x-model="it.insumo_id" @change="elegirInsumo(it)" aria-label="Insumo vinculado (opcional)">
                                <option value="">Sin insumo</option>
                                <template x-for="s in insumos" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template>
                            </select>
                            <input class="inp" maxlength="160" placeholder="Descripción" x-model="it.descripcion" aria-label="Descripción">
                            <input class="inp" type="number" min="0.001" step="0.001" x-model.number="it.cantidad" aria-label="Cantidad">
                            <input class="inp" type="number" min="0" step="0.01" x-model.number="it.precio_unitario" aria-label="Precio unitario (S/)">
                            <button type="button" class="icon-btn sm" @click="quitarItem(idx)" :disabled="f.items.length === 1" aria-label="Quitar producto"><x-icon name="x" size="14" /></button>
                        </div>
                    </template>
                    <div class="ferr" x-show="errorItems" x-text="errorItems"></div>
                    <button type="button" class="btn btn-g btn-sm" @click="agregarItem()">+ Agregar producto</button>
                </div>

                <div class="total-prev">
                    <span>Total de la orden</span>
                    <strong x-text="money(total)"></strong>
                </div>

                <div class="field" style="margin-top:12px;">
                    <label class="lbl" for="oc-notas">Notas</label>
                    <textarea class="inp" id="oc-notas" rows="2" maxlength="500" x-model="f.notas"></textarea>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardandoâ€¦' : 'Crear orden'"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.ordenesCompra = window.ordenesCompra || function (proveedores, insumos, ordenes) {
        const estados = {
            borrador: { nombre: 'Borrador', color: '#475569', fondo: '#E2E8F0' },
            enviada: { nombre: 'Enviada', color: '#1D4ED8', fondo: '#DBEAFE' },
            recibida: { nombre: 'Recibida', color: '#15803D', fondo: '#DCFCE7' },
            cancelada: { nombre: 'Cancelada', color: '#B91C1C', fondo: '#FEE2E2' },
        };
        const url = @js(url('admin/proveedores/ordenes'));
        const hoy = () => { const d = new Date(); return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };
        const vacio = () => ({
            proveedor_id: proveedores[0] ? proveedores[0].id : '',
            fecha: hoy(), notas: '',
            items: [{ insumo_id: '', descripcion: '', cantidad: 1, precio_unitario: 0 }],
        });

        return {
            proveedores, insumos, ordenes, estados,
            modal: null, f: vacio(), errores: {}, guardando: false,

            money(n) { return 'S/ ' + Number(n || 0).toFixed(2); },
            get total() { return this.f.items.reduce((a, it) => a + (Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0), 0); },
            get errorItems() {
                const k = Object.keys(this.errores).find(x => x === 'items' || x.startsWith('items.'));
                return k ? this.errores[k][0] : '';
            },

            abrirNueva() { this.f = vacio(); this.errores = {}; this.modal = 'orden'; },
            cerrar() { this.modal = null; },
            agregarItem() { this.f.items.push({ insumo_id: '', descripcion: '', cantidad: 1, precio_unitario: 0 }); },
            quitarItem(i) { if (this.f.items.length > 1) this.f.items.splice(i, 1); },

            // Al elegir un insumo, completa la descripcion y el costo de referencia.
            elegirInsumo(it) {
                const s = this.insumos.find(x => x.id == it.insumo_id);
                if (s) { it.descripcion = s.nombre; it.precio_unitario = s.costo_unitario; }
            },

            async guardar() {
                this.guardando = true; this.errores = {};
                const r = await gh.api(url, { method: 'POST', body: this.f });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.cerrar(); gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo crear la orden.', 'error'); }
            },

            async cambiar(o, estado) {
                if (estado === 'recibida' && !confirm('Al recibir la orden se sumará la mercadería al stock de los insumos vinculados. Â¿Continuar?')) return;
                if (estado === 'cancelada' && !confirm('Â¿Cancelar la orden ' + o.numero + '?')) return;
                const r = await gh.api(url + '/' + o.id + '/estado', { method: 'PATCH', body: { estado } });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo cambiar la orden.', 'error'); }
            },

            async eliminar(o) {
                if (!confirm('Â¿Eliminar la orden ' + o.numero + '?')) return;
                const r = await gh.api(url + '/' + o.id, { method: 'DELETE' });
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname, false); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar la orden.', 'error'); }
            },
        };
    };
    gh.ready(() => Alpine.data('ordenesCompra', window.ordenesCompra));
</script>
@endpush
@endsection
