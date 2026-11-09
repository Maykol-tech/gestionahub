@extends('layouts.app')

@section('titulo', 'Lista de espera y boletín')
@section('subtitulo', 'Avisa cuando se libere un horario y reactiva a tus clientes inactivos')

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
        <div style="font-weight:700;margin-bottom:10px;">Boletín automático para clientes inactivos</div>
        <form method="POST" action="{{ route('admin.espera.boletin') }}">
            @csrf
            <label style="display:flex;gap:6px;align-items:center;font-size:13px;margin-bottom:8px;">
                <input type="checkbox" name="boletin_activo" value="1" @checked($boletinActivo)> Enviar cada mes a clientes sin compras en 60 días
            </label>
            <label class="lbl" for="boletin_texto">Mensaje (se envía solo dentro de la ventana de 24 horas; para clientes lejanos usa una plantilla en Campañas)</label>
            <textarea class="inp" id="boletin_texto" name="boletin_texto" rows="3" maxlength="1000">{{ old('boletin_texto', $boletinTexto) }}</textarea>
            <button class="btn btn-p" type="submit" style="margin-top:10px;">Guardar boletín</button>
        </form>
    </div>

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <div style="font-weight:700;margin-bottom:10px;">Agregar a la lista de espera</div>
        <form method="POST" action="{{ route('admin.espera.guardar') }}" class="grid-2">
            @csrf
            <div><label class="lbl" for="cliente_nombre">Nombre</label><input class="inp" id="cliente_nombre" name="cliente_nombre" maxlength="120" required></div>
            <div><label class="lbl" for="cliente_telefono">WhatsApp</label><input class="inp" id="cliente_telefono" name="cliente_telefono" maxlength="30" required></div>
            <div><label class="lbl" for="fecha_deseada">Día que quiere</label><input class="inp" id="fecha_deseada" name="fecha_deseada" type="date" min="{{ now()->toDateString() }}" required></div>
            <div>
                <label class="lbl" for="servicio_id">Servicio</label>
                <select class="inp" id="servicio_id" name="servicio_id">
                    <option value="">Cualquiera</option>
                    @foreach ($servicios as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1 / -1;"><button class="btn btn-p" type="submit">Agregar</button></div>
        </form>
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="tbl-wrap" @if ($espera->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead>
                    <tr><th>Cliente</th><th>Día</th><th>Servicio</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    @foreach ($espera as $e)
                        <tr>
                            <td>{{ $e->cliente_nombre }}<div style="font-size:11px;color:var(--fg3);">{{ $e->cliente_telefono }}</div></td>
                            <td>{{ $e->fecha_deseada->format('d/m/Y') }}</td>
                            <td>{{ $e->servicio->nombre ?? 'Cualquiera' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.espera.estado', $e->id) }}">
                                    @csrf
                                    <select class="inp" name="estado" onchange="this.form.submit()" style="width:auto;">
                                        @foreach ($estados as $k => $label)
                                            <option value="{{ $k }}" @selected($e->estado === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($espera->isEmpty())
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">La lista de espera está vacía</div></div>
        @endif
    </div>
</div>
@endsection
