@extends('layouts.app')

@section('titulo', 'Fidelización')
@section('subtitulo', 'Puntos por compra, canjes y niveles de tus clientes')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .nivel-chip { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; }
        .nivel-card { border:1px solid var(--border); border-radius:12px; padding:16px; background:#fff; transition: transform .2s var(--ease), box-shadow .2s var(--ease); }
        .nivel-card:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(26,35,50,.09); }
        .saldo { font-weight:800; font-variant-numeric: tabular-nums; }
        .acciones { display:flex; gap:6px; justify-content:flex-end; }
    </style>
@endpush

@section('contenido')
<div x-data="fidelizacion(@js($clientes), @js($recompensas))" @keydown.escape.window="cerrar()">

    {{-- Indicadores --}}
    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Clientes con puntos</div><div class="stat-val">{{ $stats['clientes'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Puntos vigentes</div><div class="stat-val" x-text="formato(totales.saldo)">{{ number_format($stats['puntos_vigentes']) }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Puntos emitidos</div><div class="stat-val">{{ number_format($stats['puntos_emitidos']) }}</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Canjes realizados</div><div class="stat-val">{{ $stats['canjes'] }}</div></div>
    </div>

    {{-- Niveles --}}
    <div class="g-3" style="margin-bottom:16px;">
        @foreach ($niveles as $n)
            <div class="nivel-card fade-in" style="animation-delay: {{ $loop->index * .06 }}s">
                <span class="nivel-chip" style="background:{{ $n['fondo'] }};color:{{ $n['color'] }}">{{ $n['nombre'] }}</span>
                <div style="font-size:13px;color:var(--fg2);margin-top:10px;">
                    Desde <strong>{{ number_format($n['desde']) }}</strong> puntos ganados · gana <strong>×{{ number_format($n['multiplicador'], 1) }}</strong> puntos por compra
                </div>
                <div style="font-size:12px;color:var(--fg3);margin-top:8px;">{{ $n['clientes'] }} {{ $n['clientes'] === 1 ? 'cliente' : 'clientes' }} en este nivel</div>
            </div>
        @endforeach
    </div>

    {{-- Configurar niveles (GEST-75) --}}
    <details class="panel fade-in" style="padding:16px;margin-bottom:16px;">
        <summary style="cursor:pointer;font-weight:700;">Configurar niveles</summary>
        @if (session('ok'))
            <div style="margin-top:10px;color:#15803D;">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div style="margin-top:10px;color:#B91C1C;">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div>
        @endif
        <form method="POST" action="{{ route('admin.fidelizacion.niveles') }}" style="margin-top:12px;">
            @csrf
            @foreach ($niveles as $i => $n)
                <input type="hidden" name="niveles[{{ $i }}][clave]" value="{{ $n['clave'] }}">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:10px;">
                    <div><label class="lbl">Nombre</label><input class="inp" name="niveles[{{ $i }}][nombre]" maxlength="30" value="{{ $n['nombre'] }}" required></div>
                    <div><label class="lbl">Desde (puntos ganados)</label><input class="inp" type="number" min="0" name="niveles[{{ $i }}][desde]" value="{{ $n['desde'] }}" required {{ $n['clave'] === 'bronce' ? 'readonly' : '' }}></div>
                    <div><label class="lbl">Multiplicador</label><input class="inp" type="number" step="0.1" min="1" max="5" name="niveles[{{ $i }}][multiplicador]" value="{{ $n['multiplicador'] }}" required></div>
                </div>
            @endforeach
            <button class="btn btn-p" type="submit">Guardar niveles</button>
        </form>
    </details>

    {{-- Clientes y puntos --}}
    <div class="panel fade-in" style="animation-delay:.12s">
        <div class="panel-hd">
            <div class="search" style="flex:1;min-width:180px;max-width:360px;">
                <x-icon name="search" size="15" />
                <input class="inp" placeholder="Buscar cliente…" x-model="buscar" aria-label="Buscar cliente">
            </div>
            <span style="font-size:12px;color:var(--fg3);margin-left:auto;" x-text="filtrados.length + ' clientes'"></span>
        </div>

        <div class="tbl-wrap" x-show="filtrados.length" x-cloak>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Nivel</th>
                        <th class="num">Puntos disponibles</th>
                        <th class="num">Puntos ganados</th>
                        <th style="width:200px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(c, i) in filtrados" :key="c.id">
                        <tr class="row-in" :style="'--d:' + (i * 35) + 'ms'">
                            <td>
                                <div style="font-weight:600;" x-text="c.nombre"></div>
                                <div style="font-size:11px;color:var(--fg3);" x-text="c.telefono || 'Sin teléfono'"></div>
                            </td>
                            <td>
                                <span class="nivel-chip" :style="'background:' + niveles[c.nivel].fondo + ';color:' + niveles[c.nivel].color" x-text="niveles[c.nivel].nombre"></span>
                            </td>
                            <td class="num"><span class="saldo" x-text="formato(c.saldo)"></span></td>
                            <td class="num" style="color:var(--fg2);" x-text="formato(c.historicos)"></td>
                            <td>
                                <div class="acciones">
                                    <button class="btn btn-g btn-sm" @click="abrirCanje(c)" :disabled="!recompensas.length || c.saldo <= 0">Canjear</button>
                                    <button class="btn btn-g btn-sm" @click="abrirAjuste(c)">Ajustar</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="empty" x-show="!filtrados.length" x-cloak>
            <x-icon name="heart" size="34" />
            <div style="font-weight:600;color:var(--fg2);" x-text="clientes.length ? 'Ningún cliente coincide con tu búsqueda' : 'Aún no hay clientes con puntos'"></div>
            <div style="font-size:13px;color:var(--fg3);max-width:420px;text-align:center;">Los clientes ganan puntos cuando un pedido se paga o se entrega.</div>
        </div>
    </div>

    {{-- Recompensas (mismo CRUD que el resto de modulos) --}}
    <h2 style="font-size:15px;font-weight:700;margin:24px 0 10px;">Recompensas</h2>
    @include('admin.crud', ['cfg' => $cfgRecompensas])

    {{-- Ventana: canjear recompensa --}}
    <div class="modal-bg" x-show="modal === 'canje'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" @submit.prevent="canjear()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="heart" size="18" /></div>
                <div>
                    <div class="modal-ttl">Canjear recompensa</div>
                    <div class="modal-sub" x-text="seleccionado ? seleccionado.nombre + ' · saldo ' + formato(seleccionado.saldo) + ' puntos' : ''"></div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="f-recompensa">Recompensa *</label>
                    <select class="inp" id="f-recompensa" x-model="recompensaId" :class="{ err: errores.recompensa_id }">
                        <template x-for="r in recompensas" :key="r.id">
                            <option :value="r.id" x-text="r.nombre + ' · ' + formato(r.puntos_requeridos) + ' puntos'"></option>
                        </template>
                    </select>
                    <div class="ferr" x-show="errores.recompensa_id" x-text="errores.recompensa_id ? errores.recompensa_id[0] : ''"></div>
                </div>
                <p style="font-size:13px;color:var(--fg2);margin:0;" x-show="recompensaId">
                    Quedará con <strong x-text="formato(saldoTras())"></strong> puntos.
                </p>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Canjeando…' : 'Confirmar canje'"></button>
            </div>
        </form>
    </div>

    {{-- Ventana: ajustar puntos --}}
    <div class="modal-bg" x-show="modal === 'ajuste'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <form class="modal" @submit.prevent="ajustar()" novalidate>
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="pen" size="18" /></div>
                <div>
                    <div class="modal-ttl">Ajustar puntos</div>
                    <div class="modal-sub" x-text="seleccionado ? seleccionado.nombre : ''"></div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="f-puntos">Puntos (usa negativo para restar) *</label>
                    <input class="inp" id="f-puntos" type="number" step="1" x-model.number="ajuste.puntos" :class="{ err: errores.puntos }">
                    <div class="ferr" x-show="errores.puntos" x-text="errores.puntos ? errores.puntos[0] : ''"></div>
                </div>
                <div class="field">
                    <label class="lbl" for="f-motivo">Motivo *</label>
                    <input class="inp" id="f-motivo" maxlength="200" x-model="ajuste.motivo" :class="{ err: errores.motivo }">
                    <div class="ferr" x-show="errores.motivo" x-text="errores.motivo ? errores.motivo[0] : ''"></div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn btn-g" @click="cerrar()">Cancelar</button>
                <button type="submit" class="btn btn-p" :disabled="guardando" x-text="guardando ? 'Guardando…' : 'Aplicar ajuste'"></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.fidelizacion = window.fidelizacion || function (clientes, recompensas) {
        const niveles = {
            bronce: { nombre: 'Bronce', color: '#B45309', fondo: '#FEF3C7' },
            plata: { nombre: 'Plata', color: '#475569', fondo: '#E2E8F0' },
            oro: { nombre: 'Oro', color: '#A16207', fondo: '#FEF9C3' },
        };
        return {
            clientes, recompensas, niveles,
            buscar: '', modal: null, seleccionado: null, recompensaId: null,
            ajuste: { puntos: 0, motivo: '' }, errores: {}, guardando: false,

            get filtrados() {
                const q = gh.norm(this.buscar);
                return q ? this.clientes.filter(c => gh.norm(c.nombre).includes(q) || gh.norm(c.telefono).includes(q)) : this.clientes;
            },
            get totales() { return { saldo: this.clientes.reduce((a, c) => a + c.saldo, 0) }; },
            formato(n) { return Number(n || 0).toLocaleString('es-PE'); },

            abrirCanje(c) {
                this.seleccionado = c; this.errores = {};
                const asequibles = this.recompensas.filter(r => r.puntos_requeridos <= c.saldo);
                this.recompensaId = (asequibles[0] || this.recompensas[0] || {}).id || null;
                this.modal = 'canje';
            },
            abrirAjuste(c) {
                this.seleccionado = c; this.errores = {};
                this.ajuste = { puntos: 0, motivo: '' };
                this.modal = 'ajuste';
            },
            cerrar() { this.modal = null; },
            saldoTras() {
                const r = this.recompensas.find(x => x.id == this.recompensaId);
                return this.seleccionado && r ? this.seleccionado.saldo - r.puntos_requeridos : 0;
            },

            async canjear() {
                this.guardando = true; this.errores = {};
                const r = await gh.api('{{ route('admin.fidelizacion.canjear') }}', { method: 'POST', body: { cliente_id: this.seleccionado.id, recompensa_id: this.recompensaId } });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.cerrar(); gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo canjear.', 'error'); }
            },

            async ajustar() {
                this.guardando = true; this.errores = {};
                const r = await gh.api('{{ route('admin.fidelizacion.ajustar') }}', { method: 'POST', body: { cliente_id: this.seleccionado.id, ...this.ajuste } });
                this.guardando = false;
                if (r.ok) { Alpine.store('toasts').push(r.data.message); this.cerrar(); gh.ir(location.pathname, false); }
                else if (r.status === 422 && r.data.errors) { this.errores = r.data.errors; }
                else { Alpine.store('toasts').push(r.data.message || 'No se pudo ajustar.', 'error'); }
            },
        };
    };
    gh.ready(() => Alpine.data('fidelizacion', window.fidelizacion));
</script>
@endpush
@endsection
