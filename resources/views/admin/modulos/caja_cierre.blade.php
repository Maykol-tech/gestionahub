@extends('layouts.app')

@section('titulo', 'Cierre de caja')
@section('subtitulo', 'Arqueo del efectivo del día y resumen de ventas y gastos')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .resumen-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:12px; margin-bottom:16px; }
        .resumen-item { border:1px solid var(--border); border-radius:12px; padding:14px; background:#fff; }
        .resumen-item small { display:block; font-size:12px; color:var(--fg3); margin-bottom:4px; }
        .resumen-item b { font-size:18px; font-variant-numeric: tabular-nums; }
        .arqueo { display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start; }
        .arqueo-total { border-radius:12px; padding:16px; background:var(--surface-2, #F6F8FB); }
        .arqueo-total div { display:flex; justify-content:space-between; font-size:14px; padding:4px 0; }
        .dif-ok { color:#15803D; } .dif-mal { color:#B91C1C; }
        .fecha-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        @media (max-width: 720px) { .arqueo { grid-template-columns:1fr; } }
    </style>
@endpush

@section('contenido')
<div x-data="cierreCaja(@js((float) $sugerido), @js($resumen))">

    {{-- Dia --}}
    <div class="panel fade-in" style="padding:16px;margin-bottom:16px;">
        <form method="GET" action="{{ route('admin.caja.cierre') }}" class="fecha-form">
            <label class="lbl" for="c-fecha" style="margin:0;">Día</label>
            <input class="inp" id="c-fecha" type="date" name="fecha" value="{{ $dia }}" style="max-width:200px;">
            <button class="btn btn-g" type="submit">Ver día</button>
            <span style="font-size:13px;color:var(--fg3);margin-left:auto;">{{ $dia_label }} Â· {{ $resumen['pedidos'] }} {{ $resumen['pedidos'] === 1 ? 'pedido pagado' : 'pedidos pagados' }}</span>
        </form>
    </div>

    {{-- Resumen del dia --}}
    <div class="resumen-grid">
        <div class="resumen-item fade-in"><small>Ventas en efectivo</small><b>S/ {{ number_format($resumen['ventas_efectivo'], 2) }}</b></div>
        <div class="resumen-item fade-in" style="animation-delay:.05s"><small>Ventas digitales (Yape, Plin, tarjeta)</small><b>S/ {{ number_format($resumen['ventas_digitales'], 2) }}</b></div>
        <div class="resumen-item fade-in" style="animation-delay:.1s"><small>Gastos en efectivo</small><b>S/ {{ number_format($resumen['gastos_efectivo'], 2) }}</b></div>
        <div class="resumen-item fade-in" style="animation-delay:.15s"><small>Gastos digitales</small><b>S/ {{ number_format($resumen['gastos_digitales'], 2) }}</b></div>
    </div>

    {{-- Apertura de caja con registro --}}
    @if ($apertura)
        <div class="panel fade-in" style="padding:12px 16px;margin-bottom:16px;color:#15803D;">
            Caja abierta con S/ {{ number_format($apertura['monto_inicial'], 2) }} por {{ $apertura['abierto_por'] }}.
        </div>
    @elseif (! $cierre)
        <form method="POST" action="{{ route('admin.caja.apertura.guardar') }}" class="panel fade-in" style="padding:16px;margin-bottom:16px;">
            @csrf
            <input type="hidden" name="fecha" value="{{ $dia }}">
            <label class="lbl" for="ap-monto">Apertura de caja: monto inicial (S/)</label>
            <div class="fecha-form">
                <input class="inp" id="ap-monto" type="number" min="0" step="0.01" name="monto_inicial" value="{{ $sugerido }}" required style="max-width:200px;">
                <button class="btn btn-p" type="submit">Registrar apertura</button>
            </div>
            @error('monto_inicial')<div style="color:#B91C1C;font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
        </form>
    @endif

    @if ($cierre)
        {{-- Caja ya cerrada --}}
        <div class="panel fade-in" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <span class="estado-chip" style="background:#DCFCE7;color:#15803D;font-weight:700;padding:3px 10px;border-radius:99px;font-size:12px;">Caja cerrada</span>
                <span style="font-size:13px;color:var(--fg3);">por {{ $cierre['cerrado_por'] }}</span>
            </div>
            <div class="arqueo">
                <div class="arqueo-total">
                    <div><span>Monto inicial</span><span>S/ {{ number_format($cierre['monto_inicial'], 2) }}</span></div>
                    <div><span>+ Ventas en efectivo</span><span>S/ {{ number_format($cierre['ventas_efectivo'], 2) }}</span></div>
                    <div><span>âˆ’ Gastos en efectivo</span><span>S/ {{ number_format($cierre['gastos_efectivo'], 2) }}</span></div>
                    <div style="font-weight:700;border-top:1px dashed var(--border);margin-top:6px;padding-top:8px;"><span>Esperado</span><span>S/ {{ number_format($cierre['esperado'], 2) }}</span></div>
                    <div style="font-weight:700;"><span>Contado</span><span>S/ {{ number_format($cierre['contado'], 2) }}</span></div>
                    <div style="font-weight:800;"><span>Diferencia</span><span class="{{ $cierre['diferencia'] == 0 ? 'dif-ok' : 'dif-mal' }}">S/ {{ number_format($cierre['diferencia'], 2) }}</span></div>
                </div>
                <div>
                    <div style="font-size:13px;color:var(--fg2);">Notas</div>
                    <div style="font-size:14px;margin-top:4px;">{{ $cierre['notas'] ?: 'Sin notas.' }}</div>
                </div>
            </div>
        </div>
    @else
        {{-- Arqueo: formulario de cierre --}}
        <form class="panel fade-in" style="padding:18px;" @submit.prevent="cerrar()" novalidate>
            <h2 style="font-size:15px;font-weight:700;margin:0 0 12px;">Arqueo y cierre</h2>
            <div class="arqueo">
                <div>
                    <div class="field">
                        <label class="lbl" for="c-inicial">Monto inicial en caja (S/)</label>
                        <input class="inp" id="c-inicial" type="number" min="0" step="0.01" x-model.number="monto_inicial" :class="{ err: errores.monto_inicial }">
                        <div class="ferr" x-show="errores.monto_inicial" x-text="errores.monto_inicial ? errores.monto_inicial[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="c-contado">Efectivo contado al cerrar (S/) *</label>
                        <input class="inp" id="c-contado" type="number" min="0" step="0.01" x-model.number="contado" :class="{ err: errores.contado }">
                        <div class="ferr" x-show="errores.contado" x-text="errores.contado ? errores.contado[0] : ''"></div>
                    </div>
                    <div class="field">
                        <label class="lbl" for="c-notas">Notas</label>
                        <textarea class="inp" id="c-notas" rows="2" maxlength="500" x-model="notas"></textarea>
                    </div>
                    <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Cerrando¦' : 'Cerrar caja'"></button>
                </div>
                <div class="arqueo-total">
                    <div><span>Monto inicial</span><span x-text="money(monto_inicial)"></span></div>
                    <div><span>+ Ventas en efectivo</span><span x-text="money(resumen.ventas_efectivo)"></span></div>
                    <div><span>âˆ’ Gastos en efectivo</span><span x-text="money(resumen.gastos_efectivo)"></span></div>
                    <div style="font-weight:700;border-top:1px dashed var(--border);margin-top:6px;padding-top:8px;"><span>Esperado</span><span x-text="money(esperado)"></span></div>
                    <div style="font-weight:700;"><span>Contado</span><span x-text="money(contado)"></span></div>
                    <div style="font-weight:800;"><span>Diferencia</span><span :class="diferencia === 0 ? 'dif-ok' : 'dif-mal'" x-text="(diferencia > 0 ? '+' : '') + money(diferencia)"></span></div>
                </div>
            </div>
        </form>
    @endif

    {{-- Historial --}}
    <div class="panel fade-in" style="margin-top:16px;">
        <div class="panel-hd"><span style="font-weight:700;">Cierres anteriores</span></div>
        <div class="tbl-wrap" x-show="historial.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr><th>Fecha</th><th class="num">Esperado</th><th class="num">Contado</th><th class="num">Diferencia</th><th>Cerrado por</th></tr>
                </thead>
                <tbody>
                    @foreach ($historial as $h)
                        <tr>
                            <td>{{ $h['fecha'] }}</td>
                            <td class="num">S/ {{ number_format($h['esperado'], 2) }}</td>
                            <td class="num">S/ {{ number_format($h['contado'], 2) }}</td>
                            <td class="num"><span class="{{ $h['diferencia'] == 0 ? 'dif-ok' : 'dif-mal' }}">S/ {{ number_format($h['diferencia'], 2) }}</span></td>
                            <td>{{ $h['cerrado_por'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="empty" x-show="!historial.length" x-cloak>
            <div style="font-size:13px;color:var(--fg3);">Aún no hay cierres. El primero aparecerá aquí.</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.cierreCaja = window.cierreCaja || function (sugerido, resumen) {
        const url = @js(route('admin.caja.cierre.guardar'));
        const dia = @js($dia);
        return {
            resumen,
            monto_inicial: sugerido, contado: null, notas: '', guardando: false, errores: {},
            money(n) { return 'S/ ' + Number(n || 0).toFixed(2); },
            get esperado() { return Math.round((Number(this.monto_inicial) + this.resumen.ventas_efectivo - this.resumen.gastos_efectivo) * 100) / 100; },
            get diferencia() { return Math.round(((Number(this.contado) || 0) - this.esperado) * 100) / 100; },

            async cerrar() {
                this.guardando = true; this.errores = {};
                const r = await gh.api(url, { method: 'POST', body: { fecha: dia, monto_inicial: this.monto_inicial, contado: this.contado, notas: this.notas } });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); gh.ir(location.pathname + location.search, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; Alpine.store('toasts').push(r.data.message || 'Revisa los datos.', 'error'); }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo cerrar la caja.', 'error'); }
            },
        };
    };
    gh.ready(() => Alpine.data('cierreCaja', window.cierreCaja));
</script>
@endpush
@endsection
