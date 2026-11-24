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
        .prog-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:10px; }
        .prog-hint { font-size:11px; color:var(--fg3); margin-top:3px; }
        .resumen-prog { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
        .resumen-prog span { font-size:12px; background:var(--acl); color:var(--accent); border-radius:999px; padding:4px 10px; font-weight:600; }
        .mov-pos { color:var(--green); font-weight:700; } .mov-neg { color:var(--red); font-weight:700; }
        .boletin-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:10px; margin:12px 0; }
        .boletin-stats div { border:1px solid var(--bd); border-radius:10px; padding:10px 12px; }
        .boletin-stats b { display:block; font-size:20px; }
        .boletin-stats small { color:var(--fg3); font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:700; }
    </style>
@endpush

@section('contenido')
<div x-data="fidelizacion(@js($clientes), @js($recompensas), @js($niveles->keyBy('clave')))" @crud-fidelizacion.window="recompensas = $event.detail.filter(r => r.activo)" @keydown.escape.window="cerrar()">

    {{-- Indicadores --}}
    <x-ia modulo="fidelizacion" />
    <div class="stat-row">
        <div class="stat fade-in"><div class="stat-lbl">Clientes con puntos</div><div class="stat-val">{{ $stats['clientes'] }}</div></div>
        <div class="stat fade-in" style="animation-delay:.05s"><div class="stat-lbl">Puntos vigentes</div><div class="stat-val" x-text="formato(totales.saldo)">{{ number_format($stats['puntos_vigentes']) }}</div></div>
        <div class="stat fade-in" style="animation-delay:.1s"><div class="stat-lbl">Puntos emitidos</div><div class="stat-val">{{ number_format($stats['puntos_emitidos']) }}</div></div>
        <div class="stat fade-in" style="animation-delay:.15s"><div class="stat-lbl">Canjes realizados</div><div class="stat-val">{{ $stats['canjes'] }}</div></div>
    </div>

    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#15803D;font-weight:600;">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px 16px;margin-bottom:12px;color:#B91C1C;">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div>
    @endif

    {{-- Resumen del programa (GEST-75) --}}
    <div class="resumen-prog fade-in">
        @if ($programa['activo'])
            <span>{{ rtrim(rtrim(number_format($programa['puntos_por_sol'], 2), '0'), '.') }} {{ $programa['puntos_por_sol'] == 1 ? 'punto' : 'puntos' }} por cada S/ 1</span>
            <span>1 punto = S/ {{ rtrim(rtrim(number_format($programa['valor_punto'], 3), '0'), '.') }}</span>
            <span>Canje hasta {{ $programa['tope_pct'] }}% del total</span>
            <span>{{ $programa['vence_meses'] ? 'Vencen tras ' . $programa['vence_meses'] . ' meses sin compras' : 'Los puntos no vencen' }}</span>
        @else
            <span style="background:var(--redl);color:var(--red);">Programa pausado: no se suman ni canjean puntos</span>
        @endif
        <a href="{{ route('admin.clientes.ranking') }}" class="btn btn-g btn-sm" style="margin-left:auto;">Ranking de clientes</a>
    </div>

    {{-- Niveles --}}
    <div class="g-3" style="margin-bottom:16px;">
        @foreach ($niveles as $n)
            <div class="nivel-card fade-in" style="animation-delay: {{ $loop->index * .06 }}s">
                <span class="nivel-chip" style="background:{{ $n['fondo'] }};color:{{ $n['color'] }}">{{ $n['nombre'] }}</span>
                <div style="font-size:13px;color:var(--fg2);margin-top:10px;">
                    Desde <strong>{{ number_format($n['desde']) }}</strong> puntos ganados · gana <strong>×{{ number_format($n['multiplicador'], 1) }}</strong> puntos por compra
                </div>
                @if ($n['beneficio'])<div style="font-size:13px;margin-top:8px;">🎁 {{ $n['beneficio'] }}</div>@endif
                <div style="font-size:12px;color:var(--fg3);margin-top:8px;">{{ $n['clientes'] }} {{ $n['clientes'] === 1 ? 'cliente' : 'clientes' }} en este nivel</div>
            </div>
        @endforeach
    </div>

    {{-- Configurar programa y niveles (GEST-75) --}}
    <details class="panel fade-in" style="padding:16px;margin-bottom:16px;" @if ($errors->has('niveles') || $errors->has('fid_puntos_por_sol') || $errors->has('fid_valor_punto') || $errors->has('fid_tope_pct') || $errors->has('fid_vence_meses')) open @endif>
        <summary style="cursor:pointer;font-weight:700;">Configurar programa de puntos</summary>
        <form method="POST" action="{{ route('admin.fidelizacion.niveles') }}" style="margin-top:14px;">
            @csrf
            <label style="display:flex;gap:8px;align-items:center;font-weight:600;margin-bottom:12px;">
                <input type="hidden" name="fid_activo" value="0">
                <input type="checkbox" name="fid_activo" value="1" @checked($programa['activo'])> Programa activo
            </label>
            <div class="prog-grid" style="margin-bottom:16px;">
                <div><label class="lbl" for="fid-pps">Puntos por cada S/ 1</label><input class="inp" id="fid-pps" type="number" step="0.1" min="0.1" max="100" name="fid_puntos_por_sol" value="{{ old('fid_puntos_por_sol', $programa['puntos_por_sol']) }}" required><div class="prog-hint">Ej.: 1 = S/ 50 dan 50 puntos</div></div>
                <div><label class="lbl" for="fid-vp">Valor de 1 punto (S/)</label><input class="inp" id="fid-vp" type="number" step="0.001" min="0.001" max="100" name="fid_valor_punto" value="{{ old('fid_valor_punto', $programa['valor_punto']) }}" required><div class="prog-hint">Ej.: 0.05 = 100 puntos son S/ 5</div></div>
                <div><label class="lbl" for="fid-tope">Tope de canje (% del total)</label><input class="inp" id="fid-tope" type="number" step="1" min="0" max="100" name="fid_tope_pct" value="{{ old('fid_tope_pct', $programa['tope_pct']) }}" required><div class="prog-hint">Máximo que se paga con puntos</div></div>
                <div><label class="lbl" for="fid-vence">Vencen tras (meses sin compras)</label><input class="inp" id="fid-vence" type="number" step="1" min="0" max="60" name="fid_vence_meses" value="{{ old('fid_vence_meses', $programa['vence_meses']) }}" required><div class="prog-hint">0 = nunca vencen</div></div>
            </div>
            <div style="font-weight:700;margin-bottom:8px;">Niveles</div>
            @foreach ($niveles as $i => $n)
                <input type="hidden" name="niveles[{{ $i }}][clave]" value="{{ $n['clave'] }}">
                <div class="prog-grid" style="margin-bottom:10px;">
                    <div><label class="lbl">Nombre</label><input class="inp" name="niveles[{{ $i }}][nombre]" maxlength="30" value="{{ $n['nombre'] }}" required></div>
                    <div><label class="lbl">Desde (puntos ganados)</label><input class="inp" type="number" min="0" name="niveles[{{ $i }}][desde]" value="{{ $n['desde'] }}" required {{ $n['clave'] === 'bronce' ? 'readonly' : '' }}></div>
                    <div><label class="lbl">Multiplicador</label><input class="inp" type="number" step="0.1" min="1" max="5" name="niveles[{{ $i }}][multiplicador]" value="{{ $n['multiplicador'] }}" required></div>
                    <div><label class="lbl">Beneficio</label><input class="inp" name="niveles[{{ $i }}][beneficio]" maxlength="120" value="{{ $n['beneficio'] }}" placeholder="Ej.: delivery gratis"></div>
                </div>
            @endforeach
            <button class="btn btn-p" type="submit">Guardar programa</button>
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
                        <th style="width:270px;"></th>
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
                                    <button class="btn btn-g btn-sm" @click="!recompensas.length ? $store.toasts.push('Primero crea una recompensa en la sección Recompensas de abajo', 'info') : (c.saldo <= 0 ? $store.toasts.push(c.nombre + ' aún no tiene puntos para canjear', 'info') : abrirCanje(c))" :class="{ 'btn-off': !recompensas.length || c.saldo <= 0 }">Canjear</button>
                                    <button class="btn btn-g btn-sm" @click="abrirAjuste(c)">Ajustar</button>
                                    <button class="btn btn-g btn-sm" @click="verHistorial(c)">Historial</button>
                                    <a class="btn btn-g btn-sm" :href="'{{ url('admin/fidelizacion/clientes') }}/' + c.id + '/tarjeta'" target="_blank" rel="noopener" title="Abrir la tarjeta digital para enviársela">Tarjeta QR</a>
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
            <div style="font-size:13px;color:var(--fg3);max-width:420px;text-align:center;">Los clientes ganan puntos cuando un pedido se paga o se entrega, o cuando eliges al cliente en el Punto de venta.</div>
        </div>
    </div>

    {{-- Boletín a clientes inactivos (GEST-78) --}}
    <div class="panel fade-in" style="padding:18px;margin-top:20px;" id="boletin">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
            <div>
                <div style="font-weight:700;">Reactivar clientes inactivos</div>
                <div style="font-size:13px;color:var(--fg3);">Cada lunes se escribe por WhatsApp a quienes no compran hace {{ $boletin['dias'] }} días. A nadie se le escribe dos veces en 30 días.</div>
            </div>
            <form method="POST" action="{{ route('admin.fidelizacion.boletin.enviar') }}" onsubmit="return confirm('¿Enviar el boletín ahora a {{ $boletinResumen['pendientes'] }} clientes inactivos?')">
                @csrf
                <button class="btn btn-p btn-sm" type="submit" @disabled(! $boletinResumen['pendientes'])>Enviar ahora ({{ $boletinResumen['pendientes'] }})</button>
            </form>
        </div>
        <div class="boletin-stats">
            <div><small>Por avisar</small><b>{{ $boletinResumen['pendientes'] }}</b></div>
            <div><small>Mensajes enviados</small><b>{{ $boletinResumen['enviados'] }}</b></div>
            <div><small>Volvieron a comprar</small><b style="color:var(--green);">{{ $boletinResumen['volvieron'] }}</b></div>
            <div><small>Tasa de regreso</small><b>{{ $boletinResumen['tasa'] }}%</b></div>
        </div>
        <form method="POST" action="{{ route('admin.fidelizacion.boletin') }}">
            @csrf
            <label style="display:flex;gap:8px;align-items:center;font-weight:600;margin-bottom:10px;">
                <input type="hidden" name="boletin_activo" value="0">
                <input type="checkbox" name="boletin_activo" value="1" @checked($boletin['activo'])> Enviar automáticamente cada semana
            </label>
            <div class="prog-grid" style="margin-bottom:10px;">
                <div><label class="lbl" for="bol-dias">Días sin comprar</label><input class="inp" id="bol-dias" type="number" min="7" max="365" name="boletin_dias" value="{{ old('boletin_dias', $boletin['dias']) }}" required></div>
                <div><label class="lbl" for="bol-cupon">Cupón (opcional)</label><input class="inp" id="bol-cupon" name="boletin_cupon" maxlength="40" value="{{ old('boletin_cupon', $boletin['cupon']) }}" placeholder="VUELVE10"></div>
            </div>
            <label class="lbl" for="bol-texto">Mensaje · usa {nombre} y {cupon}</label>
            <textarea class="inp" id="bol-texto" name="boletin_texto" rows="3" maxlength="1000" placeholder="¡Hola {nombre}! Te extrañamos. Vuelve esta semana y usa el cupón {cupon}.">{{ old('boletin_texto', $boletin['texto']) }}</textarea>
            <button class="btn btn-g" type="submit" style="margin-top:10px;">Guardar boletín</button>
        </form>
        @if ($boletinResumen['ultimos']->isNotEmpty())
            <div class="tbl-wrap" style="margin-top:14px;">
                <table class="tbl">
                    <thead><tr><th>Fecha</th><th>Cliente</th><th>WhatsApp</th><th>¿Volvió?</th></tr></thead>
                    <tbody>
                        @foreach ($boletinResumen['ultimos'] as $u)
                            <tr>
                                <td>{{ $u['fecha'] }}</td>
                                <td>{{ $u['nombre'] }}</td>
                                <td>{!! $u['enviado'] ? '<span style="color:var(--green);font-weight:600;">Enviado</span>' : '<span style="color:var(--fg3);">Sin enviar</span>' !!}</td>
                                <td>{{ $u['volvio'] ? 'Sí, el ' . $u['volvio'] : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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

    {{-- Ventana: historial de puntos --}}
    <div class="modal-bg" x-show="modal === 'historial'" x-cloak
         x-transition:enter="m-enter" x-transition:enter-start="m-from" x-transition:enter-end="m-to"
         x-transition:leave="m-leave" x-transition:leave-start="m-to" x-transition:leave-end="m-from"
         @mousedown.self="cerrar()">
        <div class="modal" style="max-width:620px;">
            <div class="modal-hd">
                <div class="modal-ico"><x-icon name="clock" size="18" /></div>
                <div>
                    <div class="modal-ttl">Historial de puntos</div>
                    <div class="modal-sub" x-text="seleccionado ? seleccionado.nombre + ' · saldo ' + formato(seleccionado.saldo) + ' puntos' : ''"></div>
                </div>
                <button type="button" class="icon-btn sm" style="margin-left:auto;" @click="cerrar()" aria-label="Cerrar"><x-icon name="x" size="16" /></button>
            </div>
            <div class="modal-bd" style="max-height:60vh;overflow:auto;">
                <div x-show="cargando" style="color:var(--fg3);">Cargando…</div>
                <table class="tbl" x-show="!cargando && historial.length">
                    <thead><tr><th>Fecha</th><th>Detalle</th><th class="num">Puntos</th><th class="num">Saldo</th></tr></thead>
                    <tbody>
                        <template x-for="(m, i) in historial" :key="i">
                            <tr>
                                <td style="white-space:nowrap;" x-text="m.fecha"></td>
                                <td x-text="m.descripcion || m.tipo"></td>
                                <td class="num" :class="m.puntos >= 0 ? 'mov-pos' : 'mov-neg'" x-text="(m.puntos > 0 ? '+' : '') + formato(m.puntos)"></td>
                                <td class="num" x-text="m.saldo === null ? '—' : formato(m.saldo)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="!cargando && !historial.length" style="color:var(--fg3);">Sin movimientos todavía.</div>
            </div>
        </div>
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
    window.fidelizacion = window.fidelizacion || function (clientes, recompensas, niveles) {
        return {
            clientes, recompensas, niveles, historial: [], cargando: false,

            async verHistorial(c) {
                this.seleccionado = c; this.historial = []; this.cargando = true; this.modal = 'historial';
                const r = await gh.api('{{ url('admin/fidelizacion/clientes') }}/' + c.id + '/movimientos');
                this.cargando = false;
                if (r.ok) this.historial = r.data.movimientos;
                else Alpine.store('toasts').push('No se pudo cargar el historial.', 'error');
            },
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
