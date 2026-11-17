@extends('layouts.app')

@section('titulo', 'Comprobantes electrónicos')
@section('subtitulo', 'Boletas, facturas y notas de crédito (SUNAT vía Nubefact)')

@section('acciones')
    <a href="{{ route('admin.pos') }}" class="btn btn-p" style="display:inline-flex;align-items:center;gap:6px;"><x-icon name="cash" size="15" /> Ir al POS</a>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .cfg-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;}
        .cfg-grid .full{grid-column:1/-1;}
        .amb{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
        .amb label{border:1.5px solid var(--border);border-radius:12px;padding:10px 12px;cursor:pointer;font-size:12px;transition:all .18s var(--ease);display:flex;gap:8px;align-items:flex-start;}
        .amb label:hover{border-color:var(--acm);}
        .amb label.on{border-color:var(--accent);background:var(--acl);}
        .amb input{margin-top:2px;accent-color:var(--accent);}
        .amb b{display:block;font-size:13px;color:var(--fg);}
        .pend{display:flex;align-items:center;gap:10px;padding:10px 16px;border-top:1px dashed var(--border);font-size:13px;}
        .pend:first-child{border-top:none;}
        .filtros{display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);}
        .filtros .inp-ico{flex:1;min-width:200px;}
        .filtros select.inp{width:auto;}
        .acciones{display:flex;gap:4px;justify-content:flex-end;}
        .msg-cel{font-size:11px;color:var(--fg3);max-width:260px;white-space:normal;margin-top:3px;}
        .alerta{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:12px;margin-bottom:12px;font-size:13px;}
        .alerta.ok{background:#F0FDF4;color:#166534;box-shadow:inset 0 0 0 1px #BBF7D0;}
        .alerta.mal{background:#FEF2F2;color:#991B1B;box-shadow:inset 0 0 0 1px #FECACA;}
        @media (max-width:900px){.cfg-grid,.amb{grid-template-columns:1fr;}}
    </style>
@endpush

@section('contenido')
<div x-data="ghComprobantes()">
    @if (session('ok'))
        <div class="alerta ok fade-in"><x-icon name="check-circle" size="18" /> <span>{{ session('ok') }}</span></div>
    @endif
    @if (session('error') || $errors->any())
        <div class="alerta mal fade-in"><x-icon name="alert" size="18" />
            <div>@if (session('error'))<div>{{ session('error') }}</div>@endif @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        </div>
    @endif

    <div class="stat-row">
        <div class="stat"><div class="stat-lbl">Emitidos este mes <span class="stat-ico" style="background:var(--greenl);color:var(--green);"><x-icon name="check" size="14" /></span></div><div class="stat-val">{{ $stats['emitidos'] }}</div><div class="stat-ch neutral">boletas y facturas aceptadas</div></div>
        <div class="stat"><div class="stat-lbl">Facturado este mes <span class="stat-ico" style="background:var(--acl);color:var(--accent);"><x-icon name="cash" size="14" /></span></div><div class="stat-val">S/ {{ number_format($stats['facturado'], 2) }}</div><div class="stat-ch neutral">IGV incluido</div></div>
        <div class="stat"><div class="stat-lbl">Notas de crédito <span class="stat-ico" style="background:var(--ambl);color:var(--amber);"><x-icon name="refresh" size="14" /></span></div><div class="stat-val">{{ $stats['notas'] }}</div><div class="stat-ch neutral">este mes</div></div>
        <div class="stat"><div class="stat-lbl">Con error <span class="stat-ico" style="background:var(--redl);color:var(--red);"><x-icon name="alert" size="14" /></span></div><div class="stat-val">{{ $stats['con_error'] }}</div><div class="stat-ch {{ $stats['con_error'] ? 'dn' : 'neutral' }}">{{ $stats['con_error'] ? 'revisa y reintenta' : 'todo en orden' }}</div></div>
    </div>

    {{-- ============ Configuracion ============ --}}
    @php $amb = old('ambiente', $config->ambiente ?? 'simulado'); @endphp
    <div class="panel" style="padding:0;margin-bottom:16px;" x-data="{ abierto: {{ $config && ! $errors->hasAny(['ruta', 'token', 'serie_factura', 'serie_boleta']) ? 'false' : 'true' }}, amb: @js($amb) }">
        <button type="button" class="panel-hd" style="width:100%;border:none;background:none;cursor:pointer;font-family:inherit;padding:14px 16px;text-align:left;" @click="abierto = !abierto" :aria-expanded="abierto">
            <span class="panel-ttl">Conexión de facturación</span>
            <span class="badge {{ ($config->ambiente ?? 'simulado') === 'produccion' ? 'b-ok' : (($config->ambiente ?? 'simulado') === 'demo' ? 'b-g' : 'b-v') }}">{{ $ambientes[$config->ambiente ?? 'simulado'] ?? 'Simulado' }}</span>
            @if ($config && ! $config->activo)<span class="badge b-r">Emisión desactivada</span>@endif
            <span style="margin-left:auto;transition:transform .2s;" :style="abierto ? 'transform:rotate(180deg)' : ''"><x-icon name="chev-d" size="16" /></span>
        </button>
        <form method="POST" action="{{ route('admin.comprobantes.config') }}" x-show="abierto" style="padding:0 16px 16px;" data-validar novalidate>
            @csrf
            <div class="amb" style="margin-bottom:14px;">
                @foreach ($ambientes as $clave => $texto)
                    <label :class="{ on: amb === '{{ $clave }}' }">
                        <input type="radio" name="ambiente" value="{{ $clave }}" x-model="amb" @checked($amb === $clave)>
                        <span><b>{{ $texto }}</b>
                            @if ($clave === 'simulado') Prueba el flujo completo sin token. Los comprobantes no tienen valor tributario.
                            @elseif ($clave === 'demo') Envía a la API de pruebas de Nubefact con tu token.
                            @else Comprobantes reales ante SUNAT. Requiere https y token. @endif
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="cfg-grid">
                <div class="field full" x-show="amb !== 'simulado'">
                    <label class="lbl" for="ruta">URL de la API de Nubefact <span class="req">*</span></label>
                    <div class="inp-ico"><x-icon name="globe" size="15" />
                        <input class="inp @error('ruta') err @enderror" id="ruta" name="ruta" type="url" autocomplete="off" value="{{ old('ruta', $config->ruta ?? '') }}" placeholder="https://api.nubefact.com/api/v1/xxxxxxxx" :required="amb !== 'simulado'">
                    </div>
                    <div class="ferr">@error('ruta'){{ $message }}@enderror</div>
                </div>
                <div class="field" x-show="amb !== 'simulado'">
                    <label class="lbl" for="token">Token {{ $config && $config->getRawOriginal('token') ? '(guardado: déjalo vacío para conservarlo)' : '' }}</label>
                    <div class="inp-ico"><x-icon name="lock" size="15" />
                        <input class="inp @error('token') err @enderror" id="token" name="token" type="password" autocomplete="new-password" placeholder="••••••••••">
                    </div>
                    <div class="ferr">@error('token'){{ $message }}@enderror</div>
                </div>
                <div class="field">
                    <label class="lbl" for="serie_boleta">Serie de boleta <span class="req">*</span></label>
                    <div class="inp-ico"><x-icon name="tag" size="15" />
                        <input class="inp @error('serie_boleta') err @enderror" id="serie_boleta" name="serie_boleta" value="{{ old('serie_boleta', $config->serie_boleta ?? 'B001') }}" maxlength="4" required pattern="[Bb][A-Za-z0-9]{3}" title="Empieza con B y tiene 4 caracteres (ej. B001)." style="text-transform:uppercase;">
                    </div>
                    <div class="ferr">@error('serie_boleta'){{ $message }}@enderror</div>
                </div>
                <div class="field">
                    <label class="lbl" for="serie_factura">Serie de factura <span class="req">*</span></label>
                    <div class="inp-ico"><x-icon name="tag" size="15" />
                        <input class="inp @error('serie_factura') err @enderror" id="serie_factura" name="serie_factura" value="{{ old('serie_factura', $config->serie_factura ?? 'F001') }}" maxlength="4" required pattern="[Ff][A-Za-z0-9]{3}" title="Empieza con F y tiene 4 caracteres (ej. F001)." style="text-transform:uppercase;">
                    </div>
                    <div class="ferr">@error('serie_factura'){{ $message }}@enderror</div>
                </div>
                <label class="full" style="display:flex;align-items:center;gap:8px;font-size:13px;">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', $config->activo ?? true)) style="width:16px;height:16px;accent-color:var(--accent);"> Emisión activa (el POS podrá emitir boletas y facturas)
                </label>
            </div>
            <button class="btn btn-p" type="submit" style="margin-top:14px;">Guardar conexión</button>
        </form>
    </div>

    {{-- ============ Pendientes de comprobante ============ --}}
    @if ($ventasPendientes->isNotEmpty() || $pedidosPendientes->isNotEmpty())
        <div class="g-11" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div class="panel" style="padding:0;">
                <div class="panel-hd" style="padding:14px 16px;"><span class="panel-ttl">Ventas POS sin comprobante</span><span class="panel-sub">últimos 7 días</span></div>
                @forelse ($ventasPendientes as $v)
                    <div class="pend">
                        <div style="flex:1;"><b>{{ $v->numero }}</b> · {{ $v->created_at->timezone('America/Lima')->format('d/m H:i') }}<div style="font-size:11px;color:var(--fg3);">{{ $v->cliente_nombre ?: 'Cliente varios' }}</div></div>
                        <b>S/ {{ number_format($v->total, 2) }}</b>
                        <button type="button" class="btn btn-g" @click="emitir(@js(['url' => route('admin.comprobantes.emitir_venta', $v->id), 'titulo' => 'Venta ' . $v->numero, 'total' => (float) $v->total, 'nombre' => $v->cliente_nombre, 'documento' => $v->cliente_documento]))">Emitir</button>
                    </div>
                @empty
                    <div class="pend" style="color:var(--fg3);">Todas las ventas tienen comprobante.</div>
                @endforelse
            </div>
            <div class="panel" style="padding:0;">
                <div class="panel-hd" style="padding:14px 16px;"><span class="panel-ttl">Pedidos pagados sin comprobante</span></div>
                @forelse ($pedidosPendientes as $p)
                    <div class="pend">
                        <div style="flex:1;"><b>Pedido {{ $p->numero }}</b><div style="font-size:11px;color:var(--fg3);">{{ $p->cliente->nombre ?? 'Cliente' }}</div></div>
                        <b>S/ {{ number_format($p->total, 2) }}</b>
                        <button type="button" class="btn btn-g" @click="emitir(@js(['url' => route('admin.comprobantes.emitir', $p->id), 'titulo' => 'Pedido ' . $p->numero, 'total' => (float) $p->total, 'nombre' => $p->cliente->nombre ?? '', 'documento' => '']))">Emitir</button>
                    </div>
                @empty
                    <div class="pend" style="color:var(--fg3);">No hay pedidos pendientes.</div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- ============ Listado ============ --}}
    <div class="panel" style="padding:0;">
        <form method="GET" action="{{ route('admin.comprobantes') }}" class="filtros">
            <div class="inp-ico"><x-icon name="search" size="15" />
                <input class="inp" name="q" value="{{ $filtros['q'] }}" placeholder="Buscar por cliente, DNI/RUC o número (B001-25)">
            </div>
            <select class="inp" name="tipo" onchange="this.form.requestSubmit()" aria-label="Tipo">
                <option value="">Todos los tipos</option>
                @foreach ($tipos as $k => $t)<option value="{{ $k }}" @selected($filtros['tipo'] === $k)>{{ $t }}</option>@endforeach
            </select>
            <select class="inp" name="estado" onchange="this.form.requestSubmit()" aria-label="Estado">
                <option value="">Todos los estados</option>
                @foreach ($estados as $e)<option value="{{ $e }}" @selected($filtros['estado'] === $e)>{{ ucfirst($e) }}</option>@endforeach
            </select>
            <button class="btn btn-g" type="submit">Filtrar</button>
            <a class="btn btn-g" href="{{ route('admin.exportar.comprobantes', array_filter($filtros)) }}" data-recargar="1" style="display:inline-flex;align-items:center;gap:6px;"><x-icon name="download" size="14" /> Excel</a>
            @if ($filtros['q'] || $filtros['tipo'] || $filtros['estado'])<a class="btn btn-g" href="{{ route('admin.comprobantes') }}">Limpiar</a>@endif
        </form>

        @if ($comprobantes->isEmpty())
            <x-vacio titulo="Aún no hay comprobantes" texto="Cobra una venta en el POS eligiendo Boleta o Factura y aparecerá aquí." />
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr><th>Número</th><th>Tipo</th><th>Cliente</th><th>Fecha</th><th class="num">Total</th><th>Estado</th><th style="text-align:right;">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($comprobantes as $c)
                        <tr>
                            <td style="font-weight:700;">{{ $c->numero_completo }}
                                @if ($c->referencia)<div class="msg-cel">Modifica {{ $c->referencia->numero_completo }}</div>@endif
                            </td>
                            <td><span class="badge {{ $c->tipo === '01' ? 'b-g' : ($c->tipo === '03' ? 'b-t' : 'b-a') }}">{{ $c->nombre_tipo }}</span></td>
                            <td>{{ $c->cliente_nombre }}<div class="msg-cel">{{ $c->cliente_documento ?: 'Sin documento' }}</div></td>
                            <td>{{ ($c->emitido_at ?? $c->created_at)->timezone('America/Lima')->format('d/m/Y H:i') }}</td>
                            <td class="num" style="font-weight:700;">S/ {{ number_format($c->total, 2) }}</td>
                            <td>
                                <span class="badge {{ $c->badge }}"><span class="dot"></span>{{ ucfirst($c->estado) }}</span>
                                @if ($c->simulado)<span class="badge b-v">simulado</span>@endif
                                @if ($c->mensaje && ! $c->simulado)<div class="msg-cel">{{ \Illuminate\Support\Str::limit($c->mensaje, 90) }}</div>@endif
                            </td>
                            <td>
                                <div class="acciones">
                                    <a class="icon-btn" href="{{ route('admin.comprobantes.ver', $c->id) }}" target="_blank" rel="noopener" data-recargar="1" title="Ver / imprimir"><x-icon name="eye" size="14" /></a>
                                    @if ($c->enlace_xml)<a class="icon-btn" href="{{ $c->enlace_xml }}" target="_blank" rel="noopener" title="XML"><x-icon name="download" size="14" /></a>@endif
                                    @if ($c->estado === 'aceptado' && in_array($c->tipo, ['01', '03']))
                                        <div style="position:relative;" x-data="{ open: false }" @click.outside="open = false">
                                            <button type="button" class="icon-btn" @click="open = !open" title="Más acciones" aria-label="Más acciones"><x-icon name="chev-d" size="14" /></button>
                                            <div class="dropdown" style="width:210px;right:0;" x-show="open" x-cloak x-transition.origin.top.right.duration.150ms>
                                                <button type="button" class="dd-item" style="width:100%;border:none;background:none;cursor:pointer;font-family:inherit;text-align:left;align-items:center;" @click="open = false; nota(@js(['url' => route('admin.comprobantes.nota', $c->id), 'numero' => $c->numero_completo, 'total' => (float) $c->total]))"><x-icon name="refresh" size="14" /><span class="dd-ttl">Nota de crédito</span></button>
                                                <button type="button" class="dd-item" style="width:100%;border:none;background:none;cursor:pointer;font-family:inherit;text-align:left;align-items:center;color:var(--red);" @click="open = false; anular(@js(['url' => route('admin.comprobantes.anular', $c->id), 'numero' => $c->numero_completo]))"><x-icon name="x" size="14" /><span class="dd-ttl">Anular (baja)</span></button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $comprobantes->links('vendor.pagination.gestionahub') }}
        @endif
    </div>

    {{-- ============ Modal: emitir ============ --}}
    <div class="modal-bg" x-show="modal === 'emitir'" x-cloak @keydown.escape.window="modal = null" style="align-items:center;">
        <form class="modal" style="max-width:460px;" method="POST" :action="sel.url" @click.outside="modal = null" data-validar novalidate>
            @csrf
            <div class="modal-hd"><div class="modal-ico"><x-icon name="layers" size="18" /></div>
                <div><div class="modal-ttl" x-text="'Emitir comprobante · ' + (sel.titulo || '')"></div><div class="modal-sub" x-text="'Total ' + gh.soles(sel.total)"></div></div></div>
            <div class="modal-bd">
                <div class="segm" style="display:grid;grid-template-columns:1fr 1fr;background:var(--surface2);border-radius:10px;padding:3px;gap:3px;margin-bottom:12px;">
                    <button type="button" class="chip" :class="{ on: tipo === '03' }" @click="tipo = '03'">Boleta</button>
                    <button type="button" class="chip" :class="{ on: tipo === '01' }" @click="tipo = '01'">Factura</button>
                </div>
                <input type="hidden" name="tipo" :value="tipo">
                <div class="field">
                    <label class="lbl" x-text="tipo === '01' ? 'RUC *' : 'DNI (obligatorio desde S/ 700)'"></label>
                    <div class="inp-ico"><x-icon name="card" size="15" />
                        <template x-if="tipo === '01'"><input class="inp" name="documento" x-model="sel.documento" inputmode="numeric" maxlength="11" required data-ruc placeholder="20123456789"></template>
                        <template x-if="tipo === '03'"><input class="inp" name="documento" x-model="sel.documento" inputmode="numeric" maxlength="8" :required="sel.total >= 700" data-dni placeholder="12345678"></template>
                    </div>
                    <div class="ferr"></div>
                </div>
                <div class="field">
                    <label class="lbl" x-text="tipo === '01' ? 'Razón social *' : 'Nombre del cliente'"></label>
                    <div class="inp-ico"><x-icon name="users" size="15" /><input class="inp" name="nombre" x-model="sel.nombre" maxlength="150" :required="tipo === '01'"></div>
                    <div class="ferr"></div>
                </div>
                <div class="field" x-show="tipo === '01'" style="margin-bottom:0;">
                    <label class="lbl">Dirección fiscal</label>
                    <div class="inp-ico"><x-icon name="map-pin" size="15" /><input class="inp" name="direccion" maxlength="255"></div>
                </div>
            </div>
            <div class="modal-ft"><button class="btn btn-g" type="button" @click="modal = null">Cancelar</button><button class="btn btn-p" type="submit">Emitir</button></div>
        </form>
    </div>

    {{-- ============ Modal: nota de credito ============ --}}
    <div class="modal-bg" x-show="modal === 'nota'" x-cloak @keydown.escape.window="modal = null" style="align-items:center;">
        <form class="modal" style="max-width:460px;" method="POST" :action="sel.url" @click.outside="modal = null" data-validar novalidate>
            @csrf
            <div class="modal-hd"><div class="modal-ico" style="background:var(--ambl);color:var(--amber);"><x-icon name="refresh" size="18" /></div>
                <div><div class="modal-ttl" x-text="'Nota de crédito · ' + (sel.numero || '')"></div><div class="modal-sub" x-text="'Comprobante por ' + gh.soles(sel.total)"></div></div></div>
            <div class="modal-bd">
                <div class="field">
                    <label class="lbl" for="nc-codigo">Motivo SUNAT</label>
                    <select class="inp" id="nc-codigo" name="codigo" x-model="codigo">
                        @foreach ($motivosNota as $k => $label)<option value="{{ $k }}">{{ $k }} · {{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="field" x-show="codigo === '07' || codigo === '09'">
                    <label class="lbl" for="nc-monto">Monto a devolver o descontar (S/, IGV incluido) *</label>
                    <div class="inp-ico"><x-icon name="cash" size="15" /><input class="inp" id="nc-monto" name="monto" type="number" step="0.01" min="0.01" :max="sel.total" :required="codigo === '07' || codigo === '09'" :disabled="!(codigo === '07' || codigo === '09')"></div>
                    <div class="ferr"></div>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label class="lbl" for="nc-motivo">Detalle del motivo *</label>
                    <div class="inp-ico"><x-icon name="pen" size="15" /><input class="inp" id="nc-motivo" name="motivo" maxlength="200" minlength="3" required placeholder="Ej. Devolución de producto en mal estado"></div>
                    <div class="ferr"></div>
                </div>
            </div>
            <div class="modal-ft"><button class="btn btn-g" type="button" @click="modal = null">Cancelar</button><button class="btn btn-p" type="submit">Emitir nota de crédito</button></div>
        </form>
    </div>

    {{-- ============ Modal: anular ============ --}}
    <div class="modal-bg" x-show="modal === 'anular'" x-cloak @keydown.escape.window="modal = null" style="align-items:center;">
        <form class="modal" style="max-width:440px;" method="POST" :action="sel.url" @click.outside="modal = null" data-validar novalidate>
            @csrf
            <div class="modal-hd"><div class="modal-ico" style="background:var(--redl);color:var(--red);"><x-icon name="alert" size="18" /></div>
                <div><div class="modal-ttl" x-text="'Anular ' + (sel.numero || '')"></div><div class="modal-sub">Comunicación de baja ante SUNAT. No se puede deshacer.</div></div></div>
            <div class="modal-bd">
                <div class="field" style="margin-bottom:0;">
                    <label class="lbl" for="an-motivo">Motivo de la anulación *</label>
                    <div class="inp-ico"><x-icon name="pen" size="15" /><input class="inp" id="an-motivo" name="motivo" maxlength="200" minlength="3" required placeholder="Ej. Error en los datos del cliente"></div>
                    <div class="ferr"></div>
                </div>
            </div>
            <div class="modal-ft"><button class="btn btn-g" type="button" @click="modal = null">Cancelar</button><button class="btn btn-p" type="submit" style="background:var(--red);border-color:var(--red);">Anular comprobante</button></div>
        </form>
    </div>
</div>

@push('scripts')
<script>
window.ghComprobantes = function () {
    return {
        modal: null, sel: {}, tipo: '03', codigo: '01',
        emitir(d) { this.sel = { ...d, documento: d.documento || '', nombre: d.nombre || '' }; this.tipo = (d.documento || '').length === 11 ? '01' : '03'; this.modal = 'emitir'; },
        nota(d) { this.sel = d; this.codigo = '01'; this.modal = 'nota'; },
        anular(d) { this.sel = d; this.modal = 'anular'; },
    };
};
gh.ready(() => Alpine.data('ghComprobantes', window.ghComprobantes));
</script>
@endpush
@endsection
