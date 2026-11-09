@extends('layouts.app')

@section('titulo', 'Campañas de WhatsApp')
@section('subtitulo', 'Envía mensajes a tus clientes por segmento, ahora o programados')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .seg-card { padding:14px; text-align:center; }
        .estado-camp { font-size:11px; font-weight:700; padding:3px 10px; border-radius:99px; }
        .borrador { background:var(--bg); color:var(--fg3); }
        .programada { background:#FEF3C7; color:#92400E; }
        .enviada { background:#DCFCE7; color:#15803D; }
        .cancelada { background:#FEE2E2; color:#B91C1C; }
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

    <div class="grid-2" style="margin-bottom:12px;">
        @foreach ($segmentos as $seg)
            <div class="panel seg-card fade-in">
                <div style="font-size:12px;color:var(--fg3);">{{ $seg['label'] }}</div>
                <div style="font-size:24px;font-weight:800;">{{ $seg['total'] }}</div>
                <div style="font-size:11px;color:var(--fg3);">destinatarios con teléfono</div>
            </div>
        @endforeach
    </div>

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <div style="font-weight:700;margin-bottom:10px;">Nueva campaña</div>
        <form method="POST" action="{{ route('admin.campanas.guardar') }}" class="grid-2">
            @csrf
            <div>
                <label class="lbl" for="nombre">Nombre interno</label>
                <input class="inp" id="nombre" name="nombre" maxlength="120" required>
            </div>
            <div>
                <label class="lbl" for="segmento">Segmento</label>
                <select class="inp" id="segmento" name="segmento">
                    @foreach (\App\Models\CampanaWhatsapp::SEGMENTOS as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lbl" for="plantilla">Plantilla aprobada por Meta (recomendado)</label>
                <input class="inp" id="plantilla" name="plantilla" maxlength="120" placeholder="promo_semana" pattern="[a-z0-9_]+">
            </div>
            <div>
                <label class="lbl" for="programada_para">Programar para (opcional)</label>
                <input class="inp" id="programada_para" name="programada_para" type="datetime-local">
            </div>
            <div style="grid-column:1 / -1;">
                <label class="lbl" for="texto">Texto (solo para clientes que escribieron en las últimas 24 horas)</label>
                <textarea class="inp" id="texto" name="texto" rows="3" maxlength="1000"></textarea>
            </div>
            <div style="grid-column:1 / -1;">
                <button class="btn btn-p" type="submit">Guardar campaña</button>
            </div>
        </form>
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="panel-hd"><span style="font-weight:700;">Campañas</span></div>
        <div class="tbl-wrap" @if ($campanas->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Campaña</th>
                        <th>Segmento</th>
                        <th>Programación</th>
                        <th class="num">Enviados</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campanas as $c)
                        <tr>
                            <td>{{ $c->nombre }}<div style="font-size:11px;color:var(--fg3);">{{ $c->plantilla ? 'Plantilla: ' . $c->plantilla : 'Texto libre' }}</div></td>
                            <td>{{ \App\Models\CampanaWhatsapp::SEGMENTOS[$c->segmento] ?? $c->segmento }}</td>
                            <td>{{ $c->programada_para?->format('d/m/Y H:i') ?? 'Sin programar' }}</td>
                            <td class="num">{{ $c->enviados }} @if ($c->fallidos)<span style="color:#B91C1C;">Â· {{ $c->fallidos }} fallidos</span>@endif</td>
                            <td><span class="estado-camp {{ $c->estado }}">{{ ucfirst($c->estado) }}</span></td>
                            <td>
                                @if (in_array($c->estado, ['borrador', 'programada']))
                                    <form method="POST" action="{{ route('admin.campanas.enviar', $c->id) }}" style="display:inline;">@csrf <button class="btn btn-p" type="submit">Enviar ahora</button></form>
                                    <form method="POST" action="{{ route('admin.campanas.cancelar', $c->id) }}" style="display:inline;">@csrf <button class="btn btn-g" type="submit">Cancelar</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($campanas->isEmpty())
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay campañas</div></div>
        @endif
    </div>
</div>
@endsection
