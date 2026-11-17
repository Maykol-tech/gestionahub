@extends('layouts.app')

@section('titulo', 'Inventario por sucursal')
@section('subtitulo', 'Cuánto hay de cada producto en cada local, y traslados entre ellos')

@section('contenido')
<div x-data="invSucursal(@js($productos), @js($sucursales))">

    @if ($sucursales->isEmpty())
        <div class="panel fade-in" style="padding:22px;display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:28px;">🏪</span>
            <div style="flex:1;min-width:220px;">
                <b>Aún no tienes sucursales</b>
                <div style="font-size:13px;color:var(--fg2);">Todo tu stock está en tu local principal. Cuando agregues una sucursal podrás repartir la mercadería entre locales.</div>
            </div>
            <a class="btn btn-p" href="{{ route('admin.modulo', 'sucursales') }}">Agregar sucursal</a>
        </div>
    @endif

    <div class="panel fade-in">
        <div class="panel-hd">
            <div class="search" style="flex:1;max-width:320px;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar producto…" x-model="buscar">
            </div>
            <label style="display:flex;gap:6px;align-items:center;font-size:13px;color:var(--fg2);cursor:pointer;">
                <input type="checkbox" x-model="soloBajo"> Solo con poco stock en algún local
            </label>
            <button class="btn btn-p" style="margin-left:auto;" @click="abrir(null)" :disabled="!sucursales.length"><x-icon name="truck" size="15" /> Trasladar mercadería</button>
        </div>

        <div class="tbl-wrap">
            <table class="tbl inv-tbl">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="num">Principal</th>
                        <template x-for="s in sucursales" :key="s.id"><th class="num" x-text="s.nombre"></th></template>
                        <th class="num">Total</th>
                        <th style="width:110px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="p in filtrados.slice(0, 200)" :key="p.id">
                        <tr :class="{ 'row-new': p.id === resaltado }">
                            <td>
                                <div style="font-weight:600;" x-text="p.nombre"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="(p.categoria || 'Sin categoría') + (p.sku ? ' · ' + p.sku : '')"></div>
                            </td>
                            <td class="num"><span class="inv-n" :class="clase(p.locales['0'], p)" x-text="p.locales['0']"></span></td>
                            <template x-for="s in sucursales" :key="s.id">
                                <td class="num"><span class="inv-n" :class="clase(p.locales[String(s.id)], p)" x-text="p.locales[String(s.id)]"></span></td>
                            </template>
                            <td class="num" style="font-weight:700;" x-text="p.total"></td>
                            <td style="text-align:right;"><button class="btn btn-g btn-sm" @click="abrir(p)" :disabled="!sucursales.length">Mover</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="empty" x-show="!filtrados.length" x-cloak>
            <x-icon name="package" size="30" />
            <span x-text="productos.length ? 'Ningún producto coincide' : 'Aún no tienes productos'"></span>
        </div>
        <div class="panel-ft">
            <span>El stock de la tienda online y del inicio es el <b>total</b>. Las ventas de caja hechas en una sucursal descuentan de esa sucursal; la tienda online y los pedidos salen del principal. Los productos con variantes se manejan en su ficha.</span>
        </div>
    </div>

    @if ($movimientos->isNotEmpty())
        <div class="sec-ttl" style="margin-top:18px;">Últimos traslados</div>
        <div class="panel tbl-wrap" style="padding:0;">
            <table class="tbl">
                <thead><tr><th>Fecha</th><th>Producto</th><th>De</th><th>A</th><th class="num">Cantidad</th><th>Quién</th></tr></thead>
                <tbody>
                    @foreach ($movimientos as $m)
                        <tr><td>{{ $m['fecha'] }}</td><td>{{ $m['producto'] }}</td><td>{{ $m['desde'] }}</td><td>{{ $m['hacia'] }}</td><td class="num">{{ $m['cantidad'] }}</td><td style="color:var(--fg3);">{{ $m['usuario'] ?: '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Modal de traslado --}}
    <div class="modal-bg" x-show="modal" x-cloak @keydown.escape.window="modal = false" @mousedown.self="modal = false" style="align-items:center;">
        <form class="modal" style="max-width:440px;" @submit.prevent="mover()">
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="truck" size="18" /></div>
                <div><div class="modal-ttl">Trasladar mercadería</div><div class="modal-sub">El total no cambia; solo cambia dónde está.</div></div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = false" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl">Producto</label>
                    <select class="inp" x-model.number="form.producto_id" :class="{ err: errores.producto_id }">
                        <option value="">Elige un producto</option>
                        <template x-for="p in productos" :key="p.id"><option :value="p.id" x-text="p.nombre + ' (' + p.total + ')'"></option></template>
                    </select>
                </div>
                <div class="grid2">
                    <div class="field">
                        <label class="lbl">Sale de</label>
                        <select class="inp" x-model.number="form.desde">
                            <option :value="0">Principal</option>
                            <template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template>
                        </select>
                        <div style="font-size:11px;color:var(--fg3);margin-top:4px;" x-show="elegido" x-text="'Hay ' + hayEn(form.desde) + ' und.'"></div>
                    </div>
                    <div class="field">
                        <label class="lbl">Va a</label>
                        <select class="inp" x-model.number="form.hacia" :class="{ err: errores.hacia }">
                            <option :value="0">Principal</option>
                            <template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template>
                        </select>
                        <div class="ferr" x-show="errores.hacia" x-text="errores.hacia?.[0]"></div>
                    </div>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label class="lbl">Cantidad</label>
                    <div style="display:flex;gap:8px;">
                        <input class="inp" type="number" min="1" x-model.number="form.cantidad" :class="{ err: errores.cantidad }" style="max-width:140px;">
                        <button type="button" class="btn btn-g btn-sm" x-show="elegido && hayEn(form.desde) > 0" @click="form.cantidad = hayEn(form.desde)">Todo</button>
                    </div>
                    <div class="ferr" x-show="errores.cantidad" x-text="errores.cantidad?.[0]"></div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="modal = false">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Moviendo…' : 'Mover'"></button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .inv-tbl th.num, .inv-tbl td.num { text-align:center; }
    .inv-n { display:inline-block; min-width:38px; padding:3px 8px; border-radius:8px; font-weight:700; font-variant-numeric:tabular-nums; background:var(--surface2,#F1F5F9); }
    .inv-n.cero { background:var(--redl); color:var(--red); }
    .inv-n.poco { background:var(--ambl); color:var(--amber); }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
</style>
@endpush

@push('scripts')
<script>
    window.invSucursal = function (productos, sucursales) {
        return {
            productos, sucursales, buscar: '', soloBajo: false, modal: false, guardando: false, resaltado: null,
            form: { producto_id: '', desde: 0, hacia: 0, cantidad: 1 }, errores: {},
            get filtrados() {
                const q = gh.norm(this.buscar.trim());
                return this.productos.filter(p => (!q || gh.norm(p.nombre).includes(q) || gh.norm(p.sku || '').includes(q))
                    && (!this.soloBajo || Object.values(p.locales).some(v => v <= Math.max(1, Math.ceil(p.minimo / (this.sucursales.length + 1))))));
            },
            get elegido() { return this.productos.find(p => p.id === this.form.producto_id); },
            hayEn(local) { return this.elegido ? (this.elegido.locales[String(local)] ?? 0) : 0; },
            clase(n, p) { return n <= 0 ? 'cero' : (n <= Math.max(1, Math.ceil(p.minimo / (this.sucursales.length + 1))) ? 'poco' : ''); },
            abrir(p) {
                this.errores = {};
                this.form = { producto_id: p ? p.id : '', desde: 0, hacia: this.sucursales[0] ? this.sucursales[0].id : 0, cantidad: 1 };
                this.modal = true;
            },
            async mover() {
                this.guardando = true; this.errores = {};
                const r = await gh.api(@js(route('admin.sucursales.inventario.mover')), { method: 'POST', body: this.form });
                this.guardando = false;
                if (r.status === 422) { this.errores = r.data.errors || {}; return; }
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo mover', 'error'); return; }
                const p = this.productos.find(x => x.id === this.form.producto_id);
                if (p && r.data.locales) p.locales = r.data.locales;
                this.resaltado = this.form.producto_id; setTimeout(() => this.resaltado = null, 2400);
                this.modal = false;
                Alpine.store('toasts').push(r.data.message);
            },
        };
    };
    gh.ready(() => Alpine.data('invSucursal', window.invSucursal));
</script>
@endpush
