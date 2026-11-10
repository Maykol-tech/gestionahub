@extends('layouts.app')

@section('titulo', 'Pagos y carritos')
@section('subtitulo', 'Confirma Yape, Plin y transferencias, y recupera carritos sin pagar')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .estado-p { font-weight:700; }
        .pendiente { color:#B45309; }
        .confirmado { color:#15803D; }
        .rechazado { color:#B91C1C; }
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
        <div style="font-weight:700;margin-bottom:10px;">Registrar un pago recibido</div>
        <form method="POST" action="{{ route('admin.pagos.guardar') }}" class="grid-2">
            @csrf
            <div>
                <label class="lbl" for="metodo">Método</label>
                <select class="inp" id="metodo" name="metodo" required>
                    @foreach ($metodos as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div><label class="lbl" for="monto">Monto (S/)</label><input class="inp" id="monto" name="monto" type="number" step="0.01" min="0.01" required></div>
            <div><label class="lbl" for="referencia">Código de operación</label><input class="inp" id="referencia" name="referencia" maxlength="60"></div>
            <div><label class="lbl" for="pedido_numero">Número de pedido (opcional)</label><input class="inp" id="pedido_numero" name="pedido_numero" maxlength="20" placeholder="P-0001"></div>
            <div style="grid-column:1 / -1;"><button class="btn btn-p" type="submit">Registrar pago</button></div>
        </form>
    </div>

    <div class="panel fade-in" style="padding:0;margin-bottom:12px;">
        <div class="panel-hd"><span style="font-weight:700;">Pagos</span></div>
        <div class="tbl-wrap" @if ($pagos->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead><tr><th>Método</th><th>Pedido</th><th class="num">Monto</th><th>Referencia</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @foreach ($pagos as $p)
                        <tr>
                            <td>{{ $metodos[$p->metodo] ?? $p->metodo }}</td>
                            <td>{{ $p->pedido->numero ?? '”' }}</td>
                            <td class="num">S/ {{ number_format($p->monto, 2) }}</td>
                            <td>{{ $p->referencia ?: '”' }}</td>
                            <td class="estado-p {{ $p->estado }}">{{ ucfirst($p->estado) }}</td>
                            <td>
                                @if ($p->estado === 'pendiente')
                                    <form method="POST" action="{{ route('admin.pagos.confirmar', $p->id) }}" style="display:inline;">@csrf <button class="btn btn-p" type="submit">Confirmar</button></form>
                                    <form method="POST" action="{{ route('admin.pagos.rechazar', $p->id) }}" style="display:inline;">@csrf <button class="btn btn-g" type="submit">Rechazar</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($pagos->isEmpty())
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">No hay pagos registrados</div></div>
        @endif
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="panel-hd"><span style="font-weight:700;">Carritos sin pagar</span></div>
        <div class="tbl-wrap" @if ($abandonados->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead><tr><th>Cliente</th><th class="num">Total</th><th>Ultimo recordatorio</th><th>Acción</th></tr></thead>
                <tbody>
                    @foreach ($abandonados as $c)
                        <tr>
                            <td>{{ $c->nombre ?: 'Sin nombre' }}<div style="font-size:11px;color:var(--fg3);">{{ $c->telefono ?: $c->email }}</div></td>
                            <td class="num">S/ {{ number_format($c->total, 2) }}</td>
                            <td>{{ $c->recordado_at?->diffForHumans() ?? 'Nunca' }}</td>
                            <td>
                                @if ($c->telefono)
                                    <form method="POST" action="{{ route('admin.carritos.recordar', $c->id) }}">@csrf <button class="btn btn-p" type="submit">Recordar por WhatsApp</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($abandonados->isEmpty())
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">No hay carritos abandonados</div></div>
        @endif
    </div>
</div>
@endsection
