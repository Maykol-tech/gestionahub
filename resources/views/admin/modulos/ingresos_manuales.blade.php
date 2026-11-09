@extends('layouts.app')

@section('titulo', 'Ingresos manuales')
@section('subtitulo', 'Ingresos que no pasan por pedidos ni por el POS. Se suman en Finanzas.')

@push('styles')
    @include('admin.modulos._base')
@endpush

@section('contenido')
<div>
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="panel fade-in" style="padding:14px;margin-bottom:12px;">
        <div style="font-size:12px;color:var(--fg3);">Ingresos manuales del mes</div>
        <div style="font-size:22px;font-weight:700;">S/ {{ number_format($totalMes, 2) }}</div>
    </div>

    <form method="POST" action="{{ route('admin.ingresos.guardar') }}" class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        @csrf
        <label class="lbl" for="fecha">Fecha</label>
        <input class="inp" id="fecha" type="date" name="fecha" value="{{ now()->toDateString() }}" required>

        <label class="lbl" for="concepto">Concepto</label>
        <input class="inp" id="concepto" name="concepto" maxlength="120" placeholder="Ej. Alquiler de local" required>

        <label class="lbl" for="monto">Monto (S/)</label>
        <input class="inp" id="monto" type="number" step="0.01" min="0.01" name="monto" required>

        <label class="lbl" for="categoria">Categoría (opcional)</label>
        <input class="inp" id="categoria" name="categoria" maxlength="40" placeholder="Ej. Alquiler">

        <label class="lbl" for="notas">Notas (opcional)</label>
        <input class="inp" id="notas" name="notas" maxlength="255">

        <button class="btn btn-p" type="submit" style="margin-top:12px;">Registrar ingreso</button>
    </form>

    <div class="panel fade-in" style="padding:0;overflow:hidden;">
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr><th>Fecha</th><th>Concepto</th><th>Categoría</th><th style="text-align:right;">Monto</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($ingresos as $i)
                    <tr class="row-in">
                        <td>{{ $i->fecha->format('d/m/Y') }}</td>
                        <td>{{ $i->concepto }}@if($i->notas)<div style="font-size:12px;color:var(--fg3);">{{ $i->notas }}</div>@endif</td>
                        <td>{{ $i->categoria ?? 'â€”' }}</td>
                        <td style="text-align:right;">S/ {{ number_format((float) $i->monto, 2) }}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.ingresos.eliminar', $i->id) }}" onsubmit="return confirm('Â¿Eliminar este ingreso?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-g" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay ingresos manuales</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
