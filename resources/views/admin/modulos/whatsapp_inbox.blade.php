@extends('layouts.app')

@section('titulo', 'Conversaciones de WhatsApp')
@section('subtitulo', 'Mensajes con tus clientes desde el número de tu negocio')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .inbox { display:grid; grid-template-columns: 300px 1fr; gap:12px; }
        .chat-msg { max-width:75%; padding:8px 12px; border-radius:12px; margin-bottom:8px; font-size:14px; white-space:pre-wrap; }
        .chat-in { background:var(--bg); border:1px solid var(--border); }
        .chat-out { background:#DCFCE7; margin-left:auto; color:#1A2332; }
        .chat-fallido { background:#FEE2E2; margin-left:auto; color:#1A2332; }
        .fila-conv { display:block; padding:10px 12px; border-bottom:1px solid var(--border); color:inherit; text-decoration:none; }
        .fila-conv.activa { background:var(--bg); font-weight:700; }
        .chip-activa { font-size:11px; color:#15803D; font-weight:700; }
        @media (max-width: 720px) { .inbox { grid-template-columns: 1fr; } }
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

    @unless ($conectado)
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;font-size:13px;color:var(--fg2);">
            Aún no tienes el número de WhatsApp conectado. Puedes leer los mensajes, pero no responder desde aquí.
        </div>
    @endunless

    <div class="inbox">
        <div class="panel fade-in" style="padding:0;">
            <div class="panel-hd"><span style="font-weight:700;">Clientes</span></div>
            @forelse ($sesiones as $s)
                <a class="fila-conv {{ $telefono === $s->telefono ? 'activa' : '' }}" href="{{ route('admin.whatsapp.inbox', ['telefono' => $s->telefono]) }}">
                    <div>{{ $s->telefono }}</div>
                    <div style="font-size:11px;color:var(--fg3);">
                        {{ $s->ultimo_mensaje_at?->diffForHumans() }} Â· {{ $s->mensajes }} mensajes
                        @if ($s->activa)<span class="chip-activa">Â· activa</span>@endif
                    </div>
                </a>
            @empty
                <div class="empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay conversaciones</div></div>
            @endforelse
        </div>

        <div class="panel fade-in" style="padding:18px;">
            @if ($telefono)
                <div style="font-weight:700;margin-bottom:12px;">{{ $telefono }}</div>
                <div style="max-height:55vh;overflow:auto;display:flex;flex-direction:column;">
                    @forelse ($hilo as $m)
                        <div class="chat-msg {{ $m->direccion === 'entrante' ? 'chat-in' : ($m->estado === 'fallido' ? 'chat-fallido' : 'chat-out') }}">
                            {{ $m->texto }}
                            <div style="font-size:10px;color:var(--fg3);margin-top:4px;">{{ $m->created_at?->format('d/m H:i') }} Â· {{ $m->tipo }}</div>
                        </div>
                    @empty
                        <div style="font-size:13px;color:var(--fg3);">Sin mensajes.</div>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.whatsapp.responder') }}" style="margin-top:14px;display:flex;gap:8px;">
                    @csrf
                    <input type="hidden" name="telefono" value="{{ $telefono }}">
                    <input class="inp" name="texto" maxlength="1000" placeholder="Escribe una respuesta" required {{ $conectado ? '' : 'disabled' }}>
                    <button class="btn btn-p" type="submit" {{ $conectado ? '' : 'disabled' }}>Enviar</button>
                </form>
            @else
                <div style="font-size:13px;color:var(--fg3);">Elige una conversación para verla.</div>
            @endif
        </div>
    </div>
</div>
@endsection
