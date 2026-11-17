@extends('layouts.app')

@section('titulo', 'Ingresos manuales')
@section('subtitulo', 'Ingresos que no pasan por pedidos ni por el POS. Se suman en Finanzas.')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .ing-top { display:grid; grid-template-columns:220px minmax(0, 1fr); gap:12px; margin-bottom:12px; align-items:start; }
        .ing-total { padding:16px; }
        .ing-form { padding:14px 16px; }
        .ing-fila { display:grid; grid-template-columns:150px minmax(0, 1fr) 130px auto; gap:10px; align-items:end; }
        .ing-fila .lbl { margin-top:0; }
        .ing-fila .inp { margin-bottom:0; }
        .ing-mas { margin-top:8px; background:none; border:none; padding:0; color:var(--accent); font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
        @media (max-width: 900px) { .ing-top { grid-template-columns:1fr; } .ing-fila { grid-template-columns:1fr 1fr; } .ing-ancho { grid-column:1 / -1; } .ing-fila .btn { grid-column:1 / -1; } }
    </style>
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

    <div class="ing-top">
        <div class="panel fade-in ing-total">
            <div style="font-size:12px;color:var(--fg3);">Ingresos manuales del mes</div>
            <div style="font-size:24px;font-weight:800;font-variant-numeric:tabular-nums;">S/ {{ number_format($totalMes, 2) }}</div>
        </div>
        <form method="POST" action="{{ route('admin.ingresos.guardar') }}" class="panel fade-in ing-form" style="animation-delay:.04s" x-data="{ mas: {{ old('categoria') || old('notas') ? 'true' : 'false' }} }">
            @csrf
            <div class="ing-fila">
                <div><label class="lbl" for="fecha">Fecha</label><input class="inp" id="fecha" type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" required></div>
                <div class="ing-ancho"><label class="lbl" for="concepto">Concepto</label><input class="inp" id="concepto" name="concepto" maxlength="120" value="{{ old('concepto') }}" placeholder="Ej. Alquiler de local" required></div>
                <div><label class="lbl" for="monto">Monto (S/)</label><input class="inp" id="monto" type="number" step="0.01" min="0.01" name="monto" value="{{ old('monto') }}" placeholder="0.00" required></div>
                <button class="btn btn-p" type="submit">Registrar</button>
            </div>
            <button type="button" class="ing-mas" @click="mas = !mas" x-text="mas ? '− Menos datos' : '+ Categoría y notas'"></button>
            <div class="ing-fila" x-show="mas" x-transition x-cloak style="margin-top:8px;">
                <div><label class="lbl" for="categoria">Categoría</label><input class="inp" id="categoria" name="categoria" maxlength="40" value="{{ old('categoria') }}" placeholder="Ej. Alquiler"></div>
                <div class="ing-ancho"><label class="lbl" for="notas">Notas</label><input class="inp" id="notas" name="notas" maxlength="255" value="{{ old('notas') }}"></div>
            </div>
        </form>
    </div>

    <div class="panel fade-in" style="padding:0;overflow:hidden;">
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr><th>Fecha</th><th>Concepto</th><th>Categoría</th><th style="text-align:right;">Monto</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($ingresos as $i)
                    <tr class="row-in">
                        <td style="white-space:nowrap;">{{ \Illuminate\Support\Carbon::parse($i->fecha)->format('d/m/Y') }}</td>
                        <td>{{ $i->concepto }}@if ($i->notas)<div style="font-size:11px;color:var(--fg3);">{{ $i->notas }}</div>@endif</td>
                        <td>{{ $i->categoria ?: '—' }}</td>
                        <td style="text-align:right;">S/ {{ number_format((float) $i->monto, 2) }}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.ingresos.eliminar', $i->id) }}" onsubmit="return confirm('¿Eliminar este ingreso?')">
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
