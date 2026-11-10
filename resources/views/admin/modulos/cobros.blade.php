@extends('layouts.app')

@section('titulo', 'Cobros y Pagos')

@section('subtitulo')
    <span x-data x-text="$store.cobros.pendientes + ' por cobrar'">{{ collect($cobros)->where('pagado', false)->count() }} por cobrar</span>
@endsection

@section('acciones')
    <button class="btn btn-g" x-data @click="$dispatch('exportar-cobros')"><x-icon name="download" size="15" /> Exportar</button>
@endsection

@section('contenido')
<div x-data="cobrosPage(@js($cobros), @js($puedeMarcar))" @exportar-cobros.window="exportar()">

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
            <div class="stat-lbl">Pago parcial <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="layers" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($parcial, 2) }}</div>
            <div class="stat-ch neutral">Sin pagos parciales registrados</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Tasa de cobro <span class="stat-ico" style="background:var(--ctl);color:var(--cta)"><x-icon name="trending" size="14" /></span></div>
            <div class="stat-val" x-text="tasa() + '%'">0%</div>
            <div class="stat-ch neutral">Del monto total de pedidos</div>
        </div>
    </div>

    <div class="panel fade-in" style="animation-delay:.18s">
        <div class="panel-hd" style="padding-bottom:0;border-bottom:none;">
            <div>
                <div class="panel-ttl">Pedidos y cobros</div>
                <div class="panel-sub">Ultimos 300 pedidos, sin contar cancelados</div>
            </div>
            <div class="search" style="margin-left:auto;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar pedido o cliente¦" x-model="buscar">
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
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
@endpush

@push('scripts')
<script>
    gh.ready(() => {
        Alpine.store('cobros', { pendientes: 0 });

        Alpine.data('cobrosPage', (cobros, puedeMarcar) => ({
            cobros, puedeMarcar, tab: 'pendientes', buscar: '', marcando: null, resaltado: null, entrada: true,
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
                gh.csv('cobros.csv', [['Pedido', 'Cliente', 'Fecha', 'Método', 'Monto', 'Pago'],
                    ...this.filtrados.map(c => [c.numero, c.cliente, c.fecha, c.metodo, c.total, c.pagado ? 'Cobrado' : 'Pendiente'])]);
                Alpine.store('toasts').push('Se descargó cobros.csv', 'info');
            },
        }));
    });
</script>
@endpush
