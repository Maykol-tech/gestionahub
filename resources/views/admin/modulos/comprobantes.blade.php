@extends('layouts.app')

@section('titulo', 'Comprobantes electrónicos')
@section('subtitulo', 'Boletas y facturas enviadas a SUNAT vía Nubefact')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .estado-ok { color:#15803D; font-weight:700; }
        .estado-mal { color:#B91C1C; font-weight:700; }
        .estado-pend { color:var(--fg3); font-weight:700; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('contenido')
<div>
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if (session('error') || $errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            {{ session('error') }} @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <div style="font-weight:700;margin-bottom:10px;">Conexión con Nubefact</div>
        <form method="POST" action="{{ route('admin.comprobantes.config') }}">
            @csrf
            <div class="grid-2">
                <div>
                    <label class="lbl" for="ruta">URL de la API</label>
                    <input class="inp" id="ruta" name="ruta" type="url" value="{{ old('ruta', $config->ruta ?? '') }}" required>
                </div>
                <div>
                    <label class="lbl" for="token">Token {{ $config && $config->token ? '(guardado, déjalo vacío para conservarlo)' : '' }}</label>
                    <input class="inp" id="token" name="token" type="password" autocomplete="off">
                </div>
                <div>
                    <label class="lbl" for="serie_factura">Serie de factura</label>
                    <input class="inp" id="serie_factura" name="serie_factura" value="{{ old('serie_factura', $config->serie_factura ?? 'F001') }}" maxlength="4" required>
                </div>
                <div>
                    <label class="lbl" for="serie_boleta">Serie de boleta</label>
                    <input class="inp" id="serie_boleta" name="serie_boleta" value="{{ old('serie_boleta', $config->serie_boleta ?? 'B001') }}" maxlength="4" required>
                </div>
                <div>
                    <label class="lbl" for="ambiente">Ambiente</label>
                    <select class="inp" id="ambiente" name="ambiente">
                        <option value="demo" @selected(($config->ambiente ?? 'demo') === 'demo')>Pruebas (demo)</option>
                        <option value="produccion" @selected(($config->ambiente ?? '') === 'produccion')>Producción</option>
                    </select>
                </div>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;align-self:end;">
                    <input type="checkbox" name="activo" value="1" @checked($config->activo ?? false)> Emisión activa
                </label>
            </div>
            <button class="btn btn-p" type="submit" style="margin-top:12px;">Guardar conexión</button>
        </form>
    </div>

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <div style="font-weight:700;margin-bottom:10px;">Emitir desde un pedido pagado</div>
        @forelse ($pedidosPendientes as $p)
            <form method="POST" action="{{ route('admin.comprobantes.emitir', $p->id) }}" class="grid-2" style="padding:10px 0;border-top:1px dashed var(--border);">
                @csrf
                <div style="grid-column:1 / -1;font-weight:600;">Pedido {{ $p->numero }} · S/ {{ number_format($p->total, 2) }}</div>
                <div>
                    <select class="inp" name="tipo" required>
                        <option value="03">Boleta</option>
                        <option value="01">Factura</option>
                    </select>
                </div>
                <div><input class="inp" name="documento" placeholder="DNI o RUC"></div>
                <div><input class="inp" name="nombre" placeholder="Nombre o razón social"></div>
                <div><button class="btn btn-p" type="submit">Emitir</button></div>
            </form>
        @empty
            <div style="font-size:13px;color:var(--fg3);">No hay pedidos pagados sin comprobante.</div>
        @endforelse
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="panel-hd"><span style="font-weight:700;">Comprobantes emitidos</span></div>
        <div class="tbl-wrap" @if ($comprobantes->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th class="num">Total</th>
                        <th>Estado</th>
                        <th>Enlaces</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comprobantes as $c)
                        <tr>
                            <td>{{ $c->numero_completo }}</td>
                            <td>{{ $c->nombre_tipo }}</td>
                            <td>{{ $c->cliente_nombre }}<div style="font-size:11px;color:var(--fg3);">{{ $c->cliente_documento ?: 'Sin documento' }}</div></td>
                            <td class="num">S/ {{ number_format($c->total, 2) }}</td>
                            <td>
                                <span class="{{ $c->estado === 'aceptado' ? 'estado-ok' : ($c->estado === 'pendiente' ? 'estado-pend' : 'estado-mal') }}">{{ ucfirst($c->estado) }}</span>
                                @if ($c->mensaje)<div style="font-size:11px;color:var(--fg3);">{{ $c->mensaje }}</div>@endif
                            </td>
                            <td>
                                @if ($c->enlace_pdf)<a href="{{ $c->enlace_pdf }}" target="_blank" rel="noopener">PDF</a>@endif
                                @if ($c->enlace_xml)<a href="{{ $c->enlace_xml }}" target="_blank" rel="noopener">XML</a>@endif
                                @if ($c->enlace_cdr)<a href="{{ $c->enlace_cdr }}" target="_blank" rel="noopener">CDR</a>@endif
                            </td>
                            <td>
                                @if ($c->estado === 'aceptado' && in_array($c->tipo, ['01', '03']))
                                    <form method="POST" action="{{ route('admin.comprobantes.nota', $c->id) }}" style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:6px;">
                                        @csrf
                                        <select class="inp" name="codigo" style="width:auto;">
                                            @foreach (\App\Support\NubefactNotas::CODIGOS as $k => $label)
                                                <option value="{{ $k }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input class="inp" name="motivo" placeholder="Motivo" maxlength="200" required style="width:140px;">
                                        <button class="btn btn-g" type="submit">Nota de crédito</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.comprobantes.anular', $c->id) }}" style="display:flex;gap:4px;">
                                        @csrf
                                        <input class="inp" name="motivo" placeholder="Motivo de anulación" maxlength="200" required style="width:160px;">
                                        <button class="btn btn-g" type="submit">Anular</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($comprobantes->isEmpty())
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay comprobantes emitidos</div></div>
        @endif
    </div>
</div>
@endsection
