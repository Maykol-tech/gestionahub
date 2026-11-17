@extends('layouts.app')

@section('titulo', 'Caja del día')
@section('subtitulo', $fechaTexto)

@section('acciones')
    <input type="date" class="inp inp-date" value="{{ $fecha }}" max="{{ now()->toDateString() }}"
           onchange="this.value && gh.ir('{{ route('admin.modulo', 'caja') }}?fecha=' + this.value)" aria-label="Fecha de caja">
    <button class="btn btn-g" x-data @click="$dispatch('exportar-caja')"><x-icon name="download" size="15" /> Exportar</button>
    <a class="btn btn-g" href="{{ route('admin.caja.cierre', ['fecha' => $fecha]) }}"><x-icon name="lock" size="15" /> Apertura y cierre</a>
    <button class="btn btn-p" x-data @click="$dispatch('nuevo-gasto')"><x-icon name="plus" size="15" stroke="2.4" /> Registrar gasto</button>
@endsection

@section('contenido')
<div x-data="cajaPage(@js($movimientos), @js($fecha))" @exportar-caja.window="exportar()" @nuevo-gasto.window="abrirGasto()" @keydown.escape.window="modal = false">

    {{-- Estado de la caja (GEST-67) --}}
    <div class="caja-estado fade-in {{ $cierre ? 'cerrada' : ($apertura ? 'abierta' : 'sin-abrir') }}">
        <span class="ce-dot"></span>
        @if ($cierre)
            <div><strong>Caja cerrada</strong> por {{ $cierre->cerrado_por ?? '—' }} · contado S/ {{ number_format((float) $cierre->contado, 2) }} · diferencia
                <b style="color:{{ (float) $cierre->diferencia < 0 ? 'var(--red)' : 'var(--green)' }}">S/ {{ number_format((float) $cierre->diferencia, 2) }}</b></div>
        @elseif ($apertura)
            <div><strong>Caja abierta</strong> por {{ $apertura->abierto_por ?? '—' }} con S/ {{ number_format((float) $apertura->monto_inicial, 2) }}</div>
        @else
            <div><strong>La caja de este día aún no se abrió.</strong> Registra el monto inicial para poder cuadrar el efectivo al cierre.</div>
        @endif
        <a class="btn btn-sm {{ $cierre ? 'btn-g' : 'btn-p' }}" style="margin-left:auto;" href="{{ route('admin.caja.cierre', ['fecha' => $fecha]) }}">{{ $cierre ? 'Ver cierre' : ($apertura ? 'Cerrar caja' : 'Abrir caja') }}</a>
    </div>

    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Ingresos <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="trending" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($ingresos, 2) }}</div>
            <div class="stat-ch neutral">{{ count($movimientos) - $cantGastos }} {{ count($movimientos) - $cantGastos === 1 ? 'cobro' : 'cobros' }} (pedidos, POS y manuales)</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Egresos <span class="stat-ico" style="background:var(--redl);color:var(--red)"><x-icon name="cash" size="14" /></span></div>
            <div class="stat-val">S/ {{ number_format($egresos, 2) }}</div>
            <div class="stat-ch neutral">{{ $cantGastos ? $cantGastos . ($cantGastos === 1 ? ' gasto registrado' : ' gastos registrados') : 'Sin gastos registrados' }}</div>
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
                    <thead><tr><th style="width:70px;">Hora</th><th>Concepto</th><th>Método</th><th class="num">Monto</th><th style="width:44px;"></th></tr></thead>
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
                                    x-text="(m.tipo === 'egreso' ? '− ' : '+ ') + gh.soles(m.monto)"></td>
                                <td><button type="button" class="icon-btn sm" x-show="m.gasto_id" @click="eliminarGasto(m)" title="Eliminar gasto" aria-label="Eliminar gasto"><x-icon name="trash" size="14" /></button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="!filtrados.length" x-cloak>
                <x-vacio icono="inbox" titulo="Aún no hay movimientos registrados"
                         texto="Los pedidos y ventas del POS cobrados en esta fecha aparecen aquí solos. Los gastos se registran con el botón Registrar gasto." />
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
    {{-- Ventana: registrar gasto (GEST-66) --}}
    <div class="modal-bg" x-show="modal" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="modal = false">
        <form class="modal" @submit.prevent="guardarGasto()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico" style="background:var(--redl);color:var(--red);"><x-icon name="cash" size="18" /></div>
                <div>
                    <div class="modal-ttl">Registrar gasto</div>
                    <div class="modal-sub">Se descuenta del resultado del día y, si es en efectivo, del arqueo de caja.</div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="modal = false" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="g-concepto">Concepto *</label>
                    <input class="inp" id="g-concepto" maxlength="150" x-model="g.concepto" placeholder="Compra de pollo, pago de luz…" :class="{ err: errores.concepto }">
                    <div class="ferr" x-show="errores.concepto" x-text="errores.concepto ? errores.concepto[0] : ''"></div>
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label class="lbl" for="g-categoria">Categoría *</label>
                        <select class="inp" id="g-categoria" x-model="g.categoria">
                            @foreach ($gastosCategorias as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="lbl" for="g-monto">Monto (S/) *</label>
                        <input class="inp" id="g-monto" type="number" step="0.01" min="0.01" x-model="g.monto" placeholder="0.00" :class="{ err: errores.monto }">
                        <div class="ferr" x-show="errores.monto" x-text="errores.monto ? errores.monto[0] : ''"></div>
                    </div>
                </div>
                <div class="field">
                    <label class="lbl">Método de pago *</label>
                    <div class="seg">
                        @foreach ($gastosMetodos as $k => $v)
                            <button type="button" class="seg-b" :class="{ on: g.metodo === '{{ $k }}' }" @click="g.metodo = '{{ $k }}'">{{ $v }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label class="lbl" for="g-fecha">Fecha *</label>
                        <input class="inp" id="g-fecha" type="date" max="{{ now()->toDateString() }}" x-model="g.fecha" :class="{ err: errores.fecha }">
                        <div class="ferr" x-show="errores.fecha" x-text="errores.fecha ? errores.fecha[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="g-comp">Comprobante</label>
                        <input class="inp" id="g-comp" maxlength="120" x-model="g.comprobante" placeholder="F001-123 (opcional)">
                    </div>
                </div>
                <div class="field">
                    <label class="lbl" for="g-notas">Notas</label>
                    <textarea class="inp" id="g-notas" rows="2" maxlength="500" x-model="g.notas"></textarea>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="modal = false">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Registrar gasto'"></button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .caja-estado { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:14px; margin-bottom:14px; border:1px solid var(--bd); background:var(--card, #fff); font-size:14px; }
        .caja-estado .ce-dot { width:10px; height:10px; border-radius:50%; flex:none; }
        .caja-estado.abierta { border-color:color-mix(in srgb, var(--green) 35%, var(--bd)); background:color-mix(in srgb, var(--green) 6%, var(--card, #fff)); }
        .caja-estado.abierta .ce-dot { background:var(--green); box-shadow:0 0 0 4px color-mix(in srgb, var(--green) 20%, transparent); animation:ce-pulso 2s infinite; }
        .caja-estado.sin-abrir { border-color:color-mix(in srgb, var(--amber) 40%, var(--bd)); background:color-mix(in srgb, var(--amber) 7%, var(--card, #fff)); }
        .caja-estado.sin-abrir .ce-dot { background:var(--amber); }
        .caja-estado.cerrada .ce-dot { background:var(--fg3); }
        @keyframes ce-pulso { 50% { box-shadow:0 0 0 8px color-mix(in srgb, var(--green) 0%, transparent); } }
        .seg { display:flex; flex-wrap:wrap; gap:6px; }
        .seg-b { border:1px solid var(--bd); background:none; border-radius:10px; padding:8px 12px; font:inherit; font-size:13px; font-weight:600; color:var(--fg2); cursor:pointer; transition:all .15s; }
        .seg-b.on { background:var(--accent); border-color:var(--accent); color:#fff; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media (max-width:600px) { .grid-2 { grid-template-columns:1fr; } }
    </style>
@endpush

@push('scripts')
<script>
    gh.ready(() => {
        Alpine.data('cajaPage', (movimientos, fecha) => ({
            movimientos, tab: 'todos', modal: false, guardando: false, errores: {}, g: {},
            abrirGasto() {
                this.g = { concepto: '', categoria: 'insumos', monto: '', metodo: 'efectivo', fecha, comprobante: '', notas: '' };
                this.errores = {}; this.modal = true;
                this.$nextTick(() => document.getElementById('g-concepto')?.focus());
            },
            async guardarGasto() {
                this.guardando = true; this.errores = {};
                const r = await gh.api(@js(route('admin.gastos.guardar')), { method: 'POST', body: this.g });
                this.guardando = false;
                if (!r.ok) { this.errores = r.data.errors || {}; Alpine.store('toasts').push(r.data.message || 'Revisa los campos', 'error'); return; }
                this.modal = false;
                Alpine.store('toasts').push('Gasto registrado: ' + gh.soles(r.data.gasto.monto));
                gh.ir(location.pathname + '?fecha=' + this.g.fecha);
            },
            async eliminarGasto(m) {
                if (!confirm('¿Eliminar el gasto «' + m.concepto + '»?')) return;
                const r = await gh.api(@js(url('admin/gastos')) + '/' + m.gasto_id, { method: 'DELETE' });
                if (!r.ok) { Alpine.store('toasts').push(r.data.message || 'No se pudo eliminar', 'error'); return; }
                Alpine.store('toasts').push('Gasto eliminado');
                gh.ir(location.pathname + location.search);
            },
            get filtrados() { return this.tab === 'todos' ? this.movimientos : this.movimientos.filter(m => m.tipo === this.tab); },
            contar(t) { return this.movimientos.filter(m => m.tipo === t).length; },
            exportar() {
                if (!this.movimientos.length) { Alpine.store('toasts').push('No hay movimientos para exportar', 'info'); return; }
                // Los egresos van en negativo para que el total sea el neto del dia
                gh.excel('Caja {{ $fecha }}', [['Hora'], ['Concepto'], ['Detalle'], ['Método'], ['Tipo'], ['Monto', 'moneda']],
                    this.movimientos.map(m => [m.hora, m.concepto, m.detalle, m.metodo_texto, m.tipo, m.tipo === 'egreso' ? -Math.abs(m.monto) : m.monto]));
            },
        }));
    });
</script>
@endpush
