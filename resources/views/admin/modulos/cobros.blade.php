@extends('layouts.app')

@section('titulo', 'Cobros y Pagos')

@section('subtitulo')
    <span x-data x-text="$store.cobros.pendientes + ' por cobrar'">{{ collect($cobros)->where('pagado', false)->count() }} por cobrar</span>
@endsection

@section('acciones')
    <button class="btn btn-g" x-data @click="$dispatch('exportar-cobros')"><x-icon name="download" size="15" /> Exportar</button>
@endsection

@section('contenido')
<div x-data="cobrosPage(@js($cobros), @js($puedeMarcar), @js($porPagar))" @exportar-cobros.window="exportar()">

    <x-ia modulo="cobros" />
    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Cobrado <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="check-circle" size="14" /></span></div>
            <div class="stat-val" x-text="gh.soles(suma(true))">S/ {{ number_format($cobrado, 2) }}</div>
            <div class="stat-ch neutral" x-text="contar('cobrados') + ' pedidos pagados'"></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Por cobrar <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="clock" size="14" /></span></div>
            <div class="stat-val" x-text="gh.soles(suma(false))">S/ {{ number_format($porCobrar, 2) }}</div>
            <div class="stat-ch" :class="contar('pendientes') ? 'dn' : 'neutral'" x-text="contar('pendientes') ? contar('pendientes') + ' pedidos pendientes de pago' : 'Todo cobrado'"></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Debes a proveedores <span class="stat-ico" style="background:var(--redl);color:var(--red)"><x-icon name="truck" size="14" /></span></div>
            <div class="stat-val" x-text="gh.soles(deuda())">S/ 0.00</div>
            <div class="stat-ch" :class="vencidas() ? 'dn' : 'neutral'" x-text="!porPagar.length ? 'No debes nada' : (vencidas() ? vencidas() + ' ya vencidas' : porPagar.length + ' compras a crédito')"></div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Tasa de cobro <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="trending" size="14" /></span></div>
            <div class="stat-val" x-text="tasa() + '%'">0%</div>
            <div class="stat-ch neutral">Del monto total de pedidos</div>
        </div>
    </div>

    {{-- Cobrado por método de pago (prototipo) --}}
    <div class="met-row fade-in" style="animation-delay:.14s">
        <template x-for="m in porMetodo()" :key="m.nombre">
            <div class="met-card">
                <div class="met-ico" x-text="m.icono"></div>
                <div class="met-nom" x-text="m.nombre"></div>
                <div class="met-val" x-text="gh.soles(m.monto)"></div>
                <div class="met-sub" x-text="m.cantidad + (m.cantidad === 1 ? ' transacción' : ' transacciones') + ' · ' + m.pct + '%'"></div>
            </div>
        </template>
    </div>

    <div class="panel fade-in" style="animation-delay:.18s">
        <div class="panel-hd" style="padding-bottom:0;border-bottom:none;">
            <div>
                <div class="panel-ttl">Pedidos y cobros</div>
                <div class="panel-sub">Últimos 300 pedidos, sin contar cancelados</div>
            </div>
            <div class="search" style="margin-left:auto;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar pedido o cliente…" x-model="buscar">
            </div>
        </div>
        <div class="tabs" style="margin-top:6px;">
            <button class="tab" :class="{ on: tab === 'pendientes' }" @click="tab = 'pendientes'">Por cobrar<span class="n" x-text="contar('pendientes')"></span></button>
            <button class="tab" :class="{ on: tab === 'cobrados' }" @click="tab = 'cobrados'">Cobrados<span class="n" x-text="contar('cobrados')"></span></button>
            <button class="tab" :class="{ on: tab === 'todos' }" @click="tab = 'todos'">Todos<span class="n" x-text="cobros.length"></span></button>
        </div>

        <div class="tbl-wrap" x-show="filtrados.length" x-cloak>
            <table class="tbl">
                <thead><tr><th>Pedido</th><th>Cliente</th><th>Fecha</th><th>Método</th><th class="num">Monto</th><th>Pago</th><th style="text-align:right;">Acciones</th></tr></thead>
                <tbody>
                    <template x-for="(c, i) in filtrados.slice(0, 50)" :key="c.id">
                        <tr :class="{ 'row-new': c.id === resaltado, 'row-in': entrada }" :style="entrada ? '--d:' + Math.min(i * 50, 500) + 'ms' : ''">
                            <td>
                                <div style="font-weight:700;" x-text="'#' + c.numero"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="c.estado_texto"></div>
                            </td>
                            <td x-text="c.cliente"></td>
                            <td style="color:var(--fg3);font-variant-numeric:tabular-nums;" x-text="c.fecha"></td>
                            <td><span class="badge b-n" x-text="c.metodo"></span></td>
                            <td class="num" style="font-weight:700;" x-text="gh.soles(c.total)"></td>
                            <td><span class="badge" :class="c.pagado ? 'b-ok' : 'b-a'"><span class="dot"></span><span x-text="c.pagado ? 'Cobrado' : 'Pendiente'"></span></span></td>
                            <td>
                                <div style="display:flex;gap:6px;justify-content:flex-end;" x-show="!c.pagado">
                                    <a class="btn btn-wa btn-sm" x-show="c.telefono" :href="recordatorio(c)" target="_blank" rel="noopener" title="Recordar pago por WhatsApp"><x-icon name="message" size="13" /></a>
                                    <button class="btn btn-g btn-sm" x-show="puedeMarcar" @click="marcar(c)" :disabled="marcando === c.id">
                                        <x-icon name="check" size="13" stroke="2.4" /> Cobrado
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="!filtrados.length" x-cloak>
            <template x-if="!cobros.length">
                <x-vacio icono="card" titulo="Aún no hay datos registrados" texto="Cuando registres pedidos verás aquí lo cobrado y lo que falta cobrar." />
            </template>
            <template x-if="cobros.length">
                <x-vacio icono="check-circle" titulo="Nada por aquí" texto="No hay pedidos que coincidan con este filtro." />
            </template>
        </div>

        <div class="panel-ft" x-show="filtrados.length > 50" x-cloak>
            <span x-text="'Mostrando 50 de ' + filtrados.length + '. Usa el buscador para encontrar un pedido.'"></span>
        </div>
    </div>

    {{-- Lo que debes a proveedores (compras a crédito) --}}
    <div class="panel fade-in" style="animation-delay:.24s;margin-top:16px;" id="por-pagar">
        <div class="panel-hd">
            <div>
                <div class="panel-ttl">Lo que debes a proveedores</div>
                <div class="panel-sub">Compras a crédito ya recibidas. Se crean al recibir una orden de compra de un proveedor con días de crédito.</div>
            </div>
            <a href="{{ route('admin.modulo', 'proveedores') }}" class="btn btn-g btn-sm" style="margin-left:auto;">Ir a Proveedores</a>
        </div>
        <div class="tbl-wrap" x-show="porPagar.length" x-cloak>
            <table class="tbl">
                <thead><tr><th>Proveedor</th><th>Compra</th><th>Recibida</th><th>Vence</th><th class="num">Monto</th><th style="text-align:right;"></th></tr></thead>
                <tbody>
                    <template x-for="d in porPagar" :key="d.id">
                        <tr>
                            <td style="font-weight:600;" x-text="d.proveedor"></td>
                            <td x-text="d.numero"></td>
                            <td style="color:var(--fg3);" x-text="d.recibida || '—'"></td>
                            <td>
                                <div x-text="d.vence"></div>
                                <span class="badge" :class="d.dias < 0 ? 'b-r' : (d.dias <= 3 ? 'b-a' : 'b-n')"
                                      x-text="d.dias < 0 ? 'Vencida hace ' + (-d.dias) + (d.dias === -1 ? ' día' : ' días') : (d.dias === 0 ? 'Vence hoy' : 'En ' + d.dias + (d.dias === 1 ? ' día' : ' días'))"></span>
                            </td>
                            <td class="num" style="font-weight:700;" x-text="gh.soles(d.total)"></td>
                            <td style="text-align:right;"><button class="btn btn-g btn-sm" @click="abrirPago(d)"><x-icon name="check" size="13" stroke="2.4" /> Ya pagué</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="!porPagar.length" x-cloak>
            <x-vacio icono="check-circle" titulo="No debes nada a tus proveedores" texto="Cuando recibas una compra a crédito aparecerá aquí con su fecha de pago." />
        </div>
    </div>

    {{-- Confirmar pago a proveedor --}}
    <div class="modal-bg" x-show="pagando" x-cloak @keydown.escape.window="pagando = null" @mousedown.self="pagando = null" style="align-items:center;">
        <form class="modal" style="max-width:420px;" @submit.prevent="confirmarPago()">
            <div class="modal-hd">
                <div><div class="modal-ttl">Registrar pago</div><div class="modal-sub" x-text="pagando ? pagando.proveedor + ' · ' + pagando.numero + ' · ' + gh.soles(pagando.total) : ''"></div></div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="pagando = null" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl">¿Cómo pagaste?</label>
                    <select class="inp" x-model="pago.metodo">
                        @foreach ($metodosPago as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px;cursor:pointer;">
                    <input type="checkbox" x-model="pago.registrar_gasto" style="margin-top:2px;">
                    <span>Anotarlo como gasto en la caja de hoy <span style="color:var(--fg3);">(así el cierre de caja cuadra)</span></span>
                </label>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="pagando = null">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardandoPago" x-text="guardandoPago ? 'Guardando…' : 'Confirmar pago'"></button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .met-row { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:16px; }
        .met-card { background:var(--surface, #fff); border:1px solid var(--border); border-radius:14px; padding:14px; text-align:center; transition: transform .2s var(--ease), box-shadow .2s var(--ease); }
        .met-card:hover { transform:translateY(-2px); box-shadow:0 10px 26px rgba(26,35,50,.09); }
        .met-ico { font-size:20px; } .met-nom { font-size:12px; color:var(--fg3); margin-top:4px; }
        .met-val { font-size:20px; font-weight:800; margin:4px 0; font-variant-numeric:tabular-nums; } .met-sub { font-size:11px; color:var(--fg3); }
        @media (max-width: 760px) { .met-row { grid-template-columns:1fr 1fr; } }
    </style>
@endpush

@push('scripts')
<script>
    gh.ready(() => {
        Alpine.store('cobros', { pendientes: 0 });

        Alpine.data('cobrosPage', (cobros, puedeMarcar, porPagar) => ({
            cobros, puedeMarcar, porPagar, tab: 'pendientes', buscar: '', marcando: null, resaltado: null, entrada: true,
            pagando: null, pago: { metodo: 'efectivo', registrar_gasto: true }, guardandoPago: false,
            deuda() { return this.porPagar.reduce((s, d) => s + d.total, 0); },
            porMetodo() {
                const grupos = [['Yape', '📱', ['Yape']], ['Tarjeta', '💳', ['Tarjeta', 'Mercado Pago']], ['Efectivo', '💵', ['Efectivo']], ['Plin', '📲', ['Plin']]];
                const pagados = this.cobros.filter(c => c.pagado), total = pagados.reduce((s, c) => s + c.total, 0) || 1;
                return grupos.map(([nombre, icono, claves]) => {
                    const l = pagados.filter(c => claves.includes(c.metodo)), monto = l.reduce((s, c) => s + c.total, 0);
                    return { nombre, icono, monto, cantidad: l.length, pct: Math.round(monto * 100 / total) };
                });
            },
            vencidas() { return this.porPagar.filter(d => d.dias < 0).length; },
            abrirPago(d) { this.pagando = d; this.pago = { metodo: 'efectivo', registrar_gasto: true }; },
            async confirmarPago() {
                this.guardandoPago = true;
                const r = await gh.api('{{ url('admin/cobros/proveedor') }}/' + this.pagando.id + '/pagado', { method: 'PATCH', body: this.pago });
                this.guardandoPago = false;
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo registrar el pago', 'error'); return; }
                this.porPagar = this.porPagar.filter(x => x.id !== this.pagando.id);
                this.pagando = null;
                Alpine.store('toasts').push(r.data.message);
            },
            init() {
                const contar = () => Alpine.store('cobros').pendientes = this.contar('pendientes');
                this.$watch('cobros', contar); contar();
                if (!this.contar('pendientes') && this.cobros.length) this.tab = 'todos';
                setTimeout(() => this.entrada = false, 900);
            },
            cumple(c, t) { return t === 'todos' || (t === 'cobrados' ? c.pagado : !c.pagado); },
            contar(t) { return this.cobros.filter(c => this.cumple(c, t)).length; },
            suma(pagado) { return this.cobros.filter(c => c.pagado === pagado).reduce((s, c) => s + c.total, 0); },
            tasa() { const t = this.suma(true) + this.suma(false); return t ? Math.round(this.suma(true) * 100 / t) : 0; },
            get filtrados() {
                const q = gh.norm(this.buscar.trim());
                return this.cobros.filter(c => this.cumple(c, this.tab) && (!q || String(c.numero).includes(q) || gh.norm(c.cliente).includes(q)));
            },
            recordatorio(c) {
                const n = (c.telefono || '').replace(/\D/g, '');
                const texto = 'Hola ' + c.cliente + ', te recordamos el pago de tu pedido #' + c.numero + ' por ' + gh.soles(c.total) + '. ¡Gracias!';
                return 'https://wa.me/' + (n.length === 9 ? '51' + n : n) + '?text=' + encodeURIComponent(texto);
            },
            async marcar(c) {
                this.marcando = c.id;
                const r = await gh.api('{{ url('admin/cobros') }}/' + c.id + '/cobrado', { method: 'PATCH' });
                this.marcando = null;
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo actualizar el pedido', 'error'); return; }
                Object.assign(c, r.data.data);
                this.resaltado = c.id; setTimeout(() => this.resaltado = null, 2400);
                Alpine.store('cobros').pendientes = this.contar('pendientes');
                Alpine.store('toasts').push(r.data.message);
            },
            exportar() {
                gh.excel('Cobros', [['Pedido'], ['Cliente'], ['Fecha', 'fecha'], ['Método'], ['Monto', 'moneda'], ['Pago']],
                    this.filtrados.map(c => [c.numero, c.cliente, c.fecha, c.metodo, c.total, c.pagado ? 'Cobrado' : 'Pendiente']));
            },
        }));
    });
</script>
@endpush
