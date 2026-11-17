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

    <div class="panel fade-in" style="padding:14px 18px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div>
            <div style="font-weight:700;">Boletín para clientes inactivos</div>
            <div style="font-size:13px;color:var(--fg3);">{{ $boletinActivo ? 'Activo: se envía cada lunes.' : 'Desactivado.' }} Se configura junto con los puntos en Fidelización.</div>
        </div>
        <a class="btn btn-g btn-sm" href="{{ route('admin.fidelizacion') }}#boletin">Configurar boletín</a>
    </div>

    <form method="POST" action="{{ route('admin.espera.configurar') }}" class="panel fade-in" style="padding:14px 18px;margin-bottom:12px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        @csrf
        <div style="flex:1;min-width:240px;">
            <div style="font-weight:700;">Cuando se cancela una cita</div>
            <div style="font-size:13px;color:var(--fg3);">Avisamos por WhatsApp a la primera persona en espera de ese día y servicio, con un enlace para tomar el cupo. Si no lo toma a tiempo, pasa a la siguiente.</div>
        </div>
        <div><label class="lbl" for="esp-min">El enlace dura (minutos)</label><input class="inp" id="esp-min" type="number" min="5" max="240" name="espera_minutos" value="{{ $minutos }}" style="width:120px;"></div>
        <button class="btn btn-p" type="submit">Guardar y revisar ahora</button>
    </form>

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
                    <tr><th>Cliente</th><th>Día</th><th>Servicio</th><th>Último aviso</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    @foreach ($espera as $e)
                        <tr>
                            <td>{{ $e->cliente_nombre }}<div style="font-size:11px;color:var(--fg3);">{{ $e->cliente_telefono }}</div></td>
                            <td>{{ $e->fecha_deseada->format('d/m/Y') }}</td>
                            <td>{{ $e->servicio->nombre ?? 'Cualquiera' }}</td>
                            <td style="font-size:12px;">
                                @php $o = $ofertas->get($e->id); @endphp
                                @if ($o)
                                    @if ($o->estado === 'enviada' && now()->lessThan($o->expira_at))
                                        <span style="color:#92400E;font-weight:600;">Esperando respuesta hasta las {{ \Carbon\Carbon::parse($o->expira_at)->timezone('America/Lima')->format('H:i') }}</span>
                                    @elseif ($o->estado === 'tomada')
                                        <span style="color:var(--green);font-weight:600;">Tomó el cupo</span>
                                    @else
                                        <span style="color:var(--fg3);">No respondió a tiempo</span>
                                    @endif
                                    @unless ($o->whatsapp_ok)<div style="color:var(--fg3);">Sin WhatsApp conectado: <a href="https://wa.me/{{ preg_replace('/\D/', '', $e->cliente_telefono) }}?text={{ rawurlencode('Se liberó un cupo, tómalo aquí: ' . url('/espera/' . $o->token)) }}" target="_blank" rel="noopener">enviar a mano</a></div>@endunless
                                @else
                                    <span style="color:var(--fg3);">—</span>
                                @endif
                            </td>
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
