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

    {{-- Tarjetas del día, como el prototipo --}}
    <div class="caja-kpis">
        <div class="caja-kpi k-res fade-in">
            <div class="ck-lbl">Resultado del día</div>
            <div class="ck-val">{{ $resultado < 0 ? '− ' : '' }}S/ {{ number_format(abs($resultado), 2) }}</div>
            <div class="ck-sub">Ingresos − Egresos</div>
        </div>
        <div class="caja-kpi k-ing fade-in" style="animation-delay:.05s">
            <div class="ck-lbl">Ingresos</div>
            <div class="ck-val">S/ {{ number_format($ingresos, 2) }}</div>
            <div class="ck-sub">{{ count($movimientos) - $cantGastos }} {{ count($movimientos) - $cantGastos === 1 ? 'cobro' : 'cobros' }} · {{ $canales }} {{ $canales === 1 ? 'método' : 'métodos' }}@if ($porCobrar > 0) · S/ {{ number_format($porCobrar, 2) }} por cobrar @endif</div>
        </div>
        <div class="caja-kpi k-egr fade-in" style="animation-delay:.1s">
            <div class="ck-lbl">Egresos</div>
            <div class="ck-val">S/ {{ number_format($egresos, 2) }}</div>
            <div class="ck-sub">{{ $gastosPorCategoria->isNotEmpty() ? $gastosPorCategoria->take(2)->pluck('texto')->join(' + ') : 'Sin gastos registrados' }}</div>
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
            @if ($gastosPorCategoria->isNotEmpty())
                <div class="panel-hd" style="border-top:1px solid var(--border);"><div class="panel-ttl">Resumen de gastos</div></div>
                @foreach ($gastosPorCategoria as $g)
                    <div class="kv" style="display:flex;justify-content:space-between;">
                        <span>{{ $g['texto'] }}</span>
                        <span style="font-weight:700;color:var(--red);font-variant-numeric:tabular-nums;">S/ {{ number_format($g['monto'], 2) }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Resumen para el cierre (prototipo) --}}
    <div class="cierre-hd fade-in">
        <div class="panel-ttl" style="display:flex;align-items:center;gap:6px;"><x-icon name="lock" size="15" /> Cierre de caja</div>
        <a class="btn btn-p" style="background:#1A2332;border-color:#1A2332;" href="{{ route('admin.caja.cierre', ['fecha' => $fecha]) }}">{{ $cierre ? 'Ver cierre del día' : 'Cerrar caja del día' }}</a>
    </div>
    <div class="panel fade-in cierre-res" style="animation-delay:.28s">
        <div class="panel-hd" style="background:var(--surface-2, #F6F8FB);">
            <div class="panel-ttl" style="text-transform:uppercase;letter-spacing:.04em;font-size:12px;">Resumen para cierre · {{ \Illuminate\Support\Carbon::parse($fecha)->translatedFormat('j M Y') }}</div>
            <div class="panel-sub">Actualizado: {{ now()->format('g:i a') }}</div>
        </div>
        <div class="cr-metodos">
            <div class="cr-m m-dig"><span><x-icon name="phone" size="12" /> Yape/Plin</span><b>S/ {{ number_format($resumenCierre['digital'], 2) }}</b></div>
            <div class="cr-m m-tar"><span><x-icon name="card" size="12" /> Tarjeta</span><b>S/ {{ number_format($resumenCierre['tarjeta'], 2) }}</b></div>
            <div class="cr-m m-efe"><span><x-icon name="cash" size="12" /> Efectivo</span><b>S/ {{ number_format($resumenCierre['efectivo'], 2) }}</b></div>
            @if ($resumenCierre['otros'] > 0)
                <div class="cr-m m-efe"><span>Otros</span><b>S/ {{ number_format($resumenCierre['otros'], 2) }}</b></div>
            @endif
        </div>
        <div class="cr-tot">
            <div><span>Total ingresos brutos</span><b style="color:var(--green)">S/ {{ number_format($ingresos, 2) }}</b></div>
            <div><span>Total egresos del día</span><b style="color:var(--red)">− S/ {{ number_format($egresos, 2) }}</b></div>
            <div class="cr-neta"><span>Ganancia neta del día</span><b style="color:{{ $resultado < 0 ? 'var(--red)' : 'var(--green)' }}">{{ $resultado < 0 ? '− ' : '' }}S/ {{ number_format(abs($resultado), 2) }}</b></div>
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
        .caja-kpis { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
        .caja-kpi { border-radius:14px; padding:18px 18px 16px; transition: transform .2s var(--ease), box-shadow .2s var(--ease); }
        .caja-kpi:hover { transform: translateY(-2px); box-shadow:0 10px 26px rgba(26,35,50,.09); }
        .caja-kpi .ck-lbl { font-size:11px; font-weight:600; opacity:.85; }
        .caja-kpi .ck-val { font-size:28px; font-weight:800; margin:6px 0 8px; font-variant-numeric:tabular-nums; letter-spacing:-.01em; }
        .caja-kpi .ck-sub { font-size:11px; opacity:.8; }
        .k-res { background:var(--accent, #4B7CF5); color:#fff; }
        .k-ing { background:#EEF3FE; color:#64748B; } .k-ing .ck-val { color:var(--accent, #4B7CF5); }
        .k-egr { background:#FDE2E2; color:#64748B; } .k-egr .ck-val { color:#DC2626; }
        .cierre-hd { display:flex; align-items:center; justify-content:space-between; gap:10px; margin:20px 0 10px; }
        .cr-metodos { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; padding:14px 16px; }
        .cr-m { border-radius:10px; padding:14px; text-align:center; display:grid; gap:4px; }
        .cr-m span { font-size:11px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:4px; }
        .cr-m b { font-size:20px; font-variant-numeric:tabular-nums; }
        .m-dig { background:#DCFCE7; color:#15803D; } .m-tar { background:#EEF3FE; color:var(--accent, #4B7CF5); } .m-efe { background:#F1F5F9; color:var(--fg); }
        .cr-tot { padding:4px 16px 14px; border-top:1px solid var(--border); margin:0 16px; padding-left:0; padding-right:0; }
        .cr-tot > div { display:flex; justify-content:space-between; padding:7px 0; font-size:14px; color:var(--fg2); }
        .cr-tot b { font-variant-numeric:tabular-nums; }
        .cr-tot .cr-neta { border-top:1px solid var(--border); margin-top:6px; padding-top:12px; font-size:16px; font-weight:800; color:var(--fg); }
        .cr-tot .cr-neta b { font-size:20px; }
        @media (max-width: 760px) { .caja-kpis { grid-template-columns:1fr; } }
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
