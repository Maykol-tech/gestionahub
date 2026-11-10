@extends('layouts.app')

@section('titulo', 'Formas de entrega')
@section('subtitulo', 'Delivery con costo o recojo en tienda. Se muestran en el checkout')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
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

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <form method="POST" action="{{ route('admin.entregas.guardar') }}" class="grid-2">
            @csrf
            <div><label class="lbl" for="nombre">Nombre</label><input class="inp" id="nombre" name="nombre" maxlength="80" placeholder="Delivery zona norte" required></div>
            <div>
                <label class="lbl" for="tipo">Tipo</label>
                <select class="inp" id="tipo" name="tipo" required>
                    @foreach ($tipos as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div><label class="lbl" for="costo">Costo (S/)</label><input class="inp" id="costo" name="costo" type="number" step="0.01" min="0" value="0" required></div>
            <div><label class="lbl" for="tiempo_estimado">Tiempo estimado</label><input class="inp" id="tiempo_estimado" name="tiempo_estimado" maxlength="40" placeholder="30 a 45 min"></div>
            <div style="grid-column:1 / -1;"><button class="btn btn-p" type="submit">Agregar forma de entrega</button></div>
        </form>
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="tbl-wrap" @if ($formas->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead><tr><th>Nombre</th><th>Tipo</th><th class="num">Costo</th><th>Tiempo</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @foreach ($formas as $f)
                        <tr style="{{ $f->activa ? '' : 'opacity:.55;' }}">
                            <td>{{ $f->nombre }}</td>
                            <td>{{ $tipos[$f->tipo] ?? $f->tipo }}</td>
                            <td class="num">S/ {{ number_format($f->costo, 2) }}</td>
                            <td>{{ $f->tiempo_estimado ?: ' ”' }}</td>
                            <td>{{ $f->activa ? 'Activa' : 'Oculta' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.entregas.alternar', $f->id) }}" style="display:inline;">@csrf <button class="btn btn-g" type="submit">{{ $f->activa ? 'Ocultar' : 'Activar' }}</button></form>
                                <form method="POST" action="{{ route('admin.entregas.eliminar', $f->id) }}" style="display:inline;" onsubmit="return confirm('Â¿Eliminar esta forma de entrega?')">@csrf @method('DELETE') <button class="btn btn-g" type="submit">Eliminar</button></form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($formas->isEmpty())
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay formas de entrega</div></div>
        @endif
    </div>
</div>
@endsection
