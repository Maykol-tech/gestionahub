@extends('layouts.app')

@section('titulo', 'Punto de venta')
@section('subtitulo', 'Venta rápida de mostrador con cobro y descuento de stock')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .pos { display:grid; grid-template-columns: 1fr 360px; gap:16px; align-items:start; }
        .prod-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px,1fr)); gap:10px; }
        .prod { border:1px solid var(--border); border-radius:12px; padding:12px; background:#fff; text-align:left; cursor:pointer; transition: transform .15s var(--ease), box-shadow .15s var(--ease); }
        .prod:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(26,35,50,.09); }
        .prod:disabled { opacity:.45; cursor:not-allowed; transform:none; box-shadow:none; }
        .linea { display:flex; align-items:center; gap:8px; padding:8px 0; border-top:1px dashed var(--border); font-size:13px; }
        .metodos { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; }
        .metodo { border:1px solid var(--border); border-radius:10px; padding:8px; background:#fff; font-size:12px; font-weight:700; cursor:pointer; }
        .metodo.on { background:var(--brand, #2B6EF6); border-color:var(--brand, #2B6EF6); color:#fff; }
        .total { font-size:26px; font-weight:800; font-variant-numeric: tabular-nums; }
        @media (max-width: 900px) { .pos { grid-template-columns:1fr; } }
    </style>
@endpush

@section('contenido')
<div x-data="pos(@js($productos), @js($metodos))" x-init="iniciar()">

    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Ventas de hoy</div><div class="stat-val">{{ $stats['ventas_hoy'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Cobrado hoy</div><div class="stat-val">S/ {{ number_format($stats['total_hoy'], 2) }}</div></div>
    </div>

    <div class="pos">
        {{-- Productos --}}
        <div class="panel fade-in" style="padding:16px;">
            <div class="search" style="margin-bottom:12px;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar productoâ€¦" x-model="buscar" aria-label="Buscar producto" x-ref="buscar">
            </div>
            <div class="prod-grid">
                <template x-for="(p, i) in filtrados" :key="p.id">
                    <button type="button" class="prod row-in" :style="'--d:' + (i * 25) + 'ms'" :disabled="enCarritoStock(p) >= p.stock" @click="agregar(p)">
                        <div style="font-weight:600;font-size:14px;" x-text="p.nombre"></div>
                        <div style="font-size:13px;color:var(--fg2);margin-top:4px;" x-text="money(p.precio)"></div>
                        <div style="font-size:11px;color:var(--fg3);margin-top:2px;" x-text="'Stock ' + (p.stock - enCarritoStock(p))"></div>
                    </button>
                </template>
            </div>
            <div class="empty" x-show="!filtrados.length" x-cloak>
                <div style="font-size:13px;color:var(--fg3);">No hay productos con stock que coincidan.</div>
            </div>
        </div>

        {{-- Cobro --}}
        <div class="panel fade-in" style="padding:16px;position:sticky;top:16px;">
            <div style="font-weight:700;margin-bottom:6px;">Venta actual</div>
            <div x-show="!carrito.length" style="font-size:13px;color:var(--fg3);padding:10px 0;">Toca un producto para agregarlo.</div>

            <template x-for="l in carrito" :key="l.producto_id">
                <div class="linea">
                    <div style="flex:1;">
                        <div style="font-weight:600;" x-text="l.nombre"></div>
                        <div style="font-size:11px;color:var(--fg3);" x-text="money(l.precio) + ' c/u'"></div>
                    </div>
                    <button class="icon-btn sm" type="button" @click="quitar(l)" aria-label="Quitar uno">âˆ’</button>
                    <span style="min-width:24px;text-align:center;font-weight:700;" x-text="l.cantidad"></span>
                    <button class="icon-btn sm" type="button" @click="agregar(l)" :disabled="enCarritoStock({ id: l.producto_id }) >= stockDe(l.producto_id)" aria-label="Agregar uno">+</button>
                </div>
            </template>

            <div style="border-top:1px solid var(--border);margin-top:10px;padding-top:10px;">
                <div style="display:flex;justify-content:space-between;align-items:baseline;">
                    <span style="font-size:13px;color:var(--fg2);">Total</span>
                    <span class="total" x-text="money(total)"></span>
                </div>
            </div>

            <div style="margin-top:12px;">
                <div style="font-size:12px;font-weight:700;color:var(--fg2);margin-bottom:6px;">Método de pago</div>
                <div class="metodos">
                    <template x-for="(nombre, clave) in metodos" :key="clave">
                        <button type="button" class="metodo" :class="{ on: metodo === clave }" @click="metodo = clave" x-text="nombre"></button>
                    </template>
                </div>
            </div>

            <div class="field" style="margin-top:12px;" x-show="metodo === 'efectivo'" x-cloak>
                <label class="lbl" for="pos-recibido">Efectivo recibido (S/)</label>
                <input class="inp" id="pos-recibido" type="number" min="0" step="0.01" x-model.number="recibido" :class="{ err: errores.recibido }">
                <div class="ferr" x-show="errores.recibido" x-text="errores.recibido ? errores.recibido[0] : ''"></div>
                <div style="font-size:13px;margin-top:6px;" x-show="recibido >= total && total > 0">Vuelto: <strong x-text="money(recibido - total)"></strong></div>
            </div>

            <div class="field" style="margin-top:12px;">
                <label class="lbl" for="pos-cliente">Cliente (opcional)</label>
                <input class="inp" id="pos-cliente" maxlength="120" x-model="cliente">
            </div>

            <div class="ferr" x-show="errores.items" x-text="errores.items ? errores.items[0] : ''"></div>

            <button class="btn btn-p" type="button" style="width:100%;margin-top:12px;" :disabled="!carrito.length || guardando" @click="cobrar()"
                    x-text="guardando ? 'Cobrandoâ€¦' : 'Cobrar ' + money(total)"></button>
            <button class="btn btn-g" type="button" style="width:100%;margin-top:8px;" x-show="carrito.length" x-cloak @click="vaciar()">Vaciar venta</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.pos = window.pos || function (productos, metodos) {
        const url = @js(url('admin/pos/cobrar'));
        return {
            productos, metodos,
            buscar: '', carrito: [], metodo: 'efectivo', recibido: null, cliente: '',
            errores: {}, guardando: false,

            iniciar() { this.$nextTick(() => this.$refs.buscar && this.$refs.buscar.focus()); },
            money(n) { return 'S/ ' + Number(n || 0).toFixed(2); },
            get filtrados() {
                const q = gh.norm(this.buscar);
                return q ? this.productos.filter(p => gh.norm(p.nombre).includes(q)) : this.productos;
            },
            get total() { return Math.round(this.carrito.reduce((a, l) => a + l.precio * l.cantidad, 0) * 100) / 100; },
            stockDe(id) { const p = this.productos.find(x => x.id === id); return p ? p.stock : 0; },
            enCarritoStock(p) { const l = this.carrito.find(x => x.producto_id === p.id); return l ? l.cantidad : 0; },

            agregar(p) {
                const id = p.producto_id ?? p.id;
                const actual = this.carrito.find(x => x.producto_id === id);
                if (actual) { actual.cantidad++; return; }
                const prod = this.productos.find(x => x.id === id);
                this.carrito.push({ producto_id: id, nombre: prod.nombre, precio: prod.precio, cantidad: 1 });
            },
            quitar(l) {
                l.cantidad--;
                if (l.cantidad <= 0) this.carrito = this.carrito.filter(x => x.producto_id !== l.producto_id);
            },
            vaciar() { this.carrito = []; this.recibido = null; this.errores = {}; },

            async cobrar() {
                this.guardando = true; this.errores = {};
                const body = {
                    items: this.carrito.map(l => ({ producto_id: l.producto_id, cantidad: l.cantidad })),
                    metodo_pago: this.metodo,
                    recibido: this.metodo === 'efectivo' ? (this.recibido ?? this.total) : null,
                    cliente_nombre: this.cliente || null,
                };
                const r = await gh.api(url, { method: 'POST', body });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.vaciar(); gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo cobrar la venta.', 'error'); }
            },
        };
    };
    gh.ready(() => Alpine.data('pos', window.pos));
</script>
@endpush
@endsection
