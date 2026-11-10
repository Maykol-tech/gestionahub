@extends('layouts.app')

@section('titulo', 'Caja del día')
@section('subtitulo', $fechaTexto)

@section('acciones')
    <input type="date" class="inp inp-date" value="{{ $fecha }}" max="{{ now()->toDateString() }}"
           onchange="this.value && gh.ir('{{ route('admin.modulo', 'caja') }}?fecha=' + this.value)" aria-label="Fecha de caja">
    <button class="btn btn-g" x-data @click="$dispatch('exportar-caja')"><x-icon name="download" size="15" /> Exportar</button>
    <button class="btn btn-p" x-data @click="$store.toasts.push('El registro de gastos estará disponible pronto', 'info')"><x-icon name="plus" size="15" stroke="2.4" /> Registrar gasto</button>
@endsection

@section('contenido')
<div x-data="cajaPage(@js($movimientos))" @exportar-caja.window="exportar()">

    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Ingresos <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="trending" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($ingresos, 2) }}</div>
            <div class="stat-ch neutral">{{ count($movimientos) }} {{ count($movimientos) === 1 ? 'pedido cobrado' : 'pedidos cobrados' }}</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Egresos <span class="stat-ico" style="background:var(--redl);color:var(--red)"><x-icon name="cash" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($egresos, 2) }}</div>
            <div class="stat-ch neutral">Sin gastos registrados</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Resultado <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="chart" size="14" /></span></div>
            <div class="stat-val" style="color:{{ $resultado < 0 ? 'var(--red)' : 'var(--fg)' }}">S/ {{ number_format($resultado, 2) }}</div>
            <div class="stat-ch neutral">Ingresos menos egresos</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.15s">
            <div class="stat-lbl">Por cobrar <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="clock" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($porCobrar, 2) }}</div>
            <div class="stat-ch neutral">Pedidos del día sin cobrar</div>
        </div>
    </div>

    <div class="g-21">
        <div class="panel fade-in" style="animation-delay:.18s">
            <div class="panel-hd" style="padding-bottom:0;border-bottom:none;">
                <div>
                    <div class="panel-ttl">Movimientos</div>
                    <div class="panel-sub">{{ $esHoy ? 'Hoy' : $fechaTexto }}</div>
                </div>
            </div>
            <div class="tabs">
                <button class="tab" :class="{ on: tab === 'todos' }" @click="tab = 'todos'">Todos<span class="n" x-text="movimientos.length"></span></button>
                <button class="tab" :class="{ on: tab === 'ingreso' }" @click="tab = 'ingreso'">Ingresos<span class="n" x-text="contar('ingreso')"></span></button>
                <button class="tab" :class="{ on: tab === 'egreso' }" @click="tab = 'egreso'">Egresos<span class="n" x-text="contar('egreso')"></span></button>
            </div>

            <div class="tbl-wrap" x-show="filtrados.length" x-cloak>
                <table class="tbl">
                    <thead><tr><th style="width:70px;">Hora</th><th>Concepto</th><th>Método</th><th class="num">Monto</th></tr></thead>
                    <tbody>
                        <template x-for="(m, i) in filtrados" :key="i">
                            <tr class="row-in" :style="'--d:' + Math.min(i * 50, 500) + 'ms'">
                                <td style="color:var(--fg3);font-variant-numeric:tabular-nums;" x-text="m.hora"></td>
                                <td>
                                    <div style="font-weight:600;" x-text="m.concepto"></div>
                                    <div style="font-size:11px;color:var(--fg3);" x-text="m.detalle"></div>
                                </td>
                                <td><span class="badge b-n" x-text="m.metodo_texto"></span></td>
                                <td class="num" style="font-weight:700;" :style="m.tipo === 'egreso' ? 'color:var(--red)' : 'color:var(--green)'"
                                    x-text="(m.tipo === 'egreso' ? 'ˆ’ ' : '+ ') + gh.soles(m.monto)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="!filtrados.length" x-cloak>
                <x-vacio icono="inbox" titulo="Aún no hay movimientos registrados"
                         texto="Los pedidos cobrados de esta fecha aparecerán aquí automáticamente. El registro de gastos estará disponible pronto." />
            </div>
        </div>

        <div class="panel fade-in" style="animation-delay:.24s">
            <div class="panel-hd"><div class="panel-ttl">Por método de pago</div></div>
            @forelse ($porMetodo as $m)
                <div class="kv" style="display:block;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-weight:600;">{{ $m['texto'] }}</span>
                        <span style="font-weight:700;font-variant-numeric:tabular-nums;">S/ {{ number_format($m['monto'], 2) }}</span>
                    </div>
                    <div style="font-size:11px;color:var(--fg3);">{{ $m['cantidad'] }} {{ $m['cantidad'] === 1 ? 'operación' : 'operaciones' }}</div>
                    <div class="hbar"><span style="width:{{ $ingresos > 0 ? round($m['monto'] * 100 / $ingresos) : 0 }}%"></span></div>
                </div>
            @empty
                <x-vacio icono="card" titulo="Sin cobros en esta fecha" texto="El detalle por efectivo, Yape, Plin y tarjeta se mostrará cuando registres cobros." />
            @endforelse
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
        Alpine.data('cajaPage', (movimientos) => ({
            movimientos, tab: 'todos',
            get filtrados() { return this.tab === 'todos' ? this.movimientos : this.movimientos.filter(m => m.tipo === this.tab); },
            contar(t) { return this.movimientos.filter(m => m.tipo === t).length; },
            exportar() {
                if (!this.movimientos.length) { Alpine.store('toasts').push('No hay movimientos para exportar', 'info'); return; }
                gh.csv('caja-{{ $fecha }}.csv', [['Hora', 'Concepto', 'Detalle', 'Método', 'Tipo', 'Monto'],
                    ...this.movimientos.map(m => [m.hora, m.concepto, m.detalle, m.metodo_texto, m.tipo, m.monto])]);
                Alpine.store('toasts').push('Se descargó caja-{{ $fecha }}.csv', 'info');
            },
        }));
    });
</script>
@endpush
