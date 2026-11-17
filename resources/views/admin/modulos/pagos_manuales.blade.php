@extends('layouts.app')

@section('titulo', 'Pagos por revisar')
@section('subtitulo', 'Confirma los pagos con Yape, Plin y transferencia de tus clientes')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .estado-p { font-weight:700; }
        .pendiente { color:#B45309; }
        .confirmado { color:#15803D; }
        .rechazado { color:#B91C1C; }
        .cap-mini { width:46px; height:46px; object-fit:cover; border-radius:8px; border:1px solid var(--border); display:block; transition:transform .15s; }
        .cap-mini:hover { transform:scale(1.08); }
        .origen-t { display:inline-block; margin-top:3px; font-size:10px; font-weight:700; padding:2px 7px; border-radius:99px; background:#DBEAFE; color:#1D4ED8; }
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
                <thead><tr><th>Captura</th><th>Método</th><th>Pedido</th><th class="num">Monto</th><th>Referencia</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @foreach ($pagos as $p)
                        <tr>
                            <td>
                                @if ($p->captura)
                                    <a href="{{ route('admin.pagos.captura', $p->id) }}" target="_blank" rel="noopener" title="Ver captura"><img class="cap-mini" src="{{ route('admin.pagos.captura', $p->id) }}" alt="Captura del pago" loading="lazy"></a>
                                @else
                                    <span style="color:var(--fg3);">—</span>
                                @endif
                            </td>
                            <td>{{ $metodos[$p->metodo] ?? $p->metodo }}@if ($p->origen === 'tienda')<div><span class="origen-t">Lo envió el cliente</span></div>@endif</td>
                            <td>{{ $p->pedido->numero ?? '—' }}@if ($p->pedido?->cliente_nombre)<div style="font-size:11px;color:var(--fg3);">{{ $p->pedido->cliente_nombre }}</div>@endif</td>
                            <td class="num">S/ {{ number_format($p->monto, 2) }}</td>
                            <td>{{ $p->referencia ?: '—' }}</td>
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

    {{-- Los carritos sin pagar se gestionan en su propia pestaña (antes se repetían aquí) --}}
    @if ($abandonados->isNotEmpty())
        <a href="{{ route('admin.carritos') }}" class="panel fade-in" style="display:flex;align-items:center;gap:12px;padding:14px 16px;text-decoration:none;color:inherit;">
            <x-icon name="clipboard" size="18" />
            <span><b>{{ $abandonados->count() }} {{ $abandonados->count() === 1 ? 'carrito' : 'carritos' }} sin pagar</b> <span style="color:var(--fg3);font-size:13px;">· recuérdales por WhatsApp</span></span>
            <span style="margin-left:auto;font-weight:700;color:var(--accent);font-size:13px;">Ver carritos →</span>
        </a>
    @endif
</div>
@endsection
