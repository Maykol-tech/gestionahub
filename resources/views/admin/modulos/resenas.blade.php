@extends('layouts.app')

@section('titulo', 'Reseñas de clientes')
@section('subtitulo', 'Revisa las reseñas antes de publicarlas en tu página')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .kpis { display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap:12px; margin-bottom:12px; }
        .kpi { padding:14px; }
        .kpi .n { font-size:22px; font-weight:800; }
        .kpi .l { font-size:12px; color:var(--fg3); }
        .filtros { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; }
        .filtros a { font-size:12px; font-weight:700; padding:5px 12px; border-radius:99px; border:1px solid var(--border); color:var(--fg2); text-decoration:none; }
        .filtros a.activo { background:var(--brand); color:#fff; border-color:var(--brand); }
        .estrellas { color:#F59E0B; }
        .g-panel { padding:14px 16px; margin-bottom:12px; }
        .g-hd { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .g-logo { width:36px; height:36px; border-radius:10px; background:#fff; border:1px solid var(--border); display:flex; align-items:center; justify-content:center; font-weight:900; font-size:18px; color:#4285F4; }
        .g-sub { font-size:12px; color:var(--fg3); }
        .g-nota { font-size:13px; color:var(--fg2); margin-top:2px; }
        .g-lista { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px; margin-top:12px; }
        .g-item { border:1px solid var(--border); border-radius:12px; padding:10px 12px; font-size:13px; }
        .g-autor { font-weight:700; margin-bottom:4px; }
        .g-texto { color:var(--fg2); }
        .g-form { border-top:1px solid var(--border); margin-top:12px; padding-top:12px; }
        .g-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media (max-width:700px) { .g-grid { grid-template-columns:1fr; } }
    </style>
@endpush

@section('contenido')
<div>
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif

    <div class="kpis">
        <div class="panel kpi fade-in"><div class="n">{{ $promedio ?? '—' }}</div><div class="l">Promedio publicado</div></div>
        <div class="panel kpi fade-in"><div class="n">{{ $total }}</div><div class="l">Reseñas publicadas</div></div>
        <div class="panel kpi fade-in"><div class="n">{{ $pendientes }}</div><div class="l">Pendientes de revisar</div></div>
    </div>

    {{-- Reseñas de Google --}}
    @php $escribir = \App\Support\GoogleResenas::enlaceEscribir($google['place_id']); @endphp
    <div class="panel fade-in g-panel" x-data="{ abierto: {{ $google['place_id'] ? 'false' : 'true' }} }">
        <div class="g-hd">
            <div class="g-logo">G</div>
            <div style="flex:1;min-width:200px;">
                <b>Reseñas de Google</b>
                @if (is_array($googleDatos) && empty($googleDatos['error']))
                    <div class="g-nota"><span class="estrellas">{{ str_repeat('★', (int) round($googleDatos['nota'])) }}</span> <b>{{ $googleDatos['nota'] }}</b> de 5 · {{ $googleDatos['total'] }} reseñas en Google</div>
                @elseif (is_array($googleDatos))
                    <div class="g-sub" style="color:var(--red);">{{ $googleDatos['error'] }}</div>
                @elseif ($google['place_id'])
                    <div class="g-sub">Conectado con tu ID de Google. Agrega la clave de Google para ver aquí tu nota y tus reseñas.</div>
                @else
                    <div class="g-sub">Conecta tu ficha de Google para pedir reseñas con un enlace y mostrarlas en tu web.</div>
                @endif
            </div>
            @if ($escribir)
                <a class="btn btn-wa btn-sm" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode('¡Gracias por tu preferencia! ¿Nos ayudas con una reseña en Google? Toma 1 minuto: ' . $escribir) }}">Pedir reseña por WhatsApp</a>
                <a class="btn btn-g btn-sm" target="_blank" rel="noopener" href="{{ $escribir }}">Ver enlace</a>
            @endif
            <button type="button" class="btn btn-g btn-sm" @click="abierto = !abierto" x-text="abierto ? 'Cerrar' : 'Configurar'"></button>
        </div>

        @if (is_array($googleDatos) && ! empty($googleDatos['resenas']))
            <div class="g-lista">
                @foreach ($googleDatos['resenas'] as $g)
                    <div class="g-item">
                        <div class="g-autor">{{ $g['autor'] }} <span class="estrellas">{{ str_repeat('★', $g['nota']) }}</span> <span class="g-sub">{{ $g['cuando'] }}</span></div>
                        <div class="g-texto">{{ \Illuminate\Support\Str::limit($g['texto'], 220) }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.resenas.google') }}" class="g-form" x-show="abierto" x-cloak>
            @csrf
            <div class="g-grid">
                <div>
                    <label class="lbl" for="g-place">ID del lugar en Google <span class="g-sub">(empieza con ChIJ)</span></label>
                    <input class="inp" id="g-place" name="google_place_id" maxlength="120" value="{{ old('google_place_id', $google['place_id']) }}" placeholder="ChIJ...">
                    @error('google_place_id')<div class="ferr">{{ $message }}</div>@enderror
                    <div class="g-sub" style="margin-top:4px;">Búscalo en <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener">el buscador de ID de Google</a> escribiendo el nombre de tu negocio.</div>
                </div>
                <div>
                    <label class="lbl" for="g-clave">Clave de Google <span class="g-sub">(opcional, para mostrar tus reseñas)</span></label>
                    <input class="inp" id="g-clave" name="google_api_key" type="password" maxlength="120" autocomplete="off" placeholder="{{ $google['clave'] ? '•••••••• guardada' : 'AIza...' }}">
                    @error('google_api_key')<div class="ferr">{{ $message }}</div>@enderror
                    @if ($google['clave'])
                        <label class="g-sub" style="display:flex;gap:6px;align-items:center;margin-top:4px;"><input type="checkbox" name="quitar_clave" value="1"> Quitar la clave guardada</label>
                    @endif
                </div>
            </div>
            <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-top:10px;">
                <input type="checkbox" name="google_mostrar" value="1" @checked($google['mostrar'])> Mostrar mis reseñas de Google en mi página de reseñas
            </label>
            <button class="btn btn-p btn-sm" type="submit" style="margin-top:10px;">Guardar</button>
        </form>
    </div>

    <div class="filtros">
        <a href="{{ route('admin.resenas') }}" class="{{ $filtro ? '' : 'activo' }}">Todas</a>
        @foreach (\App\Models\Resena::ESTADOS as $k => $label)
            <a href="{{ route('admin.resenas', ['estado' => $k]) }}" class="{{ $filtro === $k ? 'activo' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="tbl-wrap" @if ($resenas->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead>
                    <tr><th>Cliente</th><th>Calificación</th><th>Comentario</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    @foreach ($resenas as $r)
                        <tr>
                            <td>{{ $r->cliente_nombre }}<div style="font-size:11px;color:var(--fg3);">{{ $r->created_at?->format('d/m/Y') }}</div></td>
                            <td><span class="estrellas">{{ str_repeat('★', $r->calificacion) }}</span></td>
                            <td style="max-width:320px;">{{ $r->comentario ?: '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.resenas.estado', $r->id) }}" style="display:flex;gap:6px;align-items:center;">
                                    @csrf
                                    <select class="inp" name="estado" onchange="this.form.submit()" style="width:auto;">
                                        @foreach (\App\Models\Resena::ESTADOS as $k => $label)
                                            <option value="{{ $k }}" @selected($r->estado === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($resenas->isEmpty())
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">Aún no hay reseñas</div><div style="font-size:13px;color:var(--fg3);">Comparte tu enlace público de reseñas con tus clientes: /resenas/tu-negocio.</div></div>
        @endif
    </div>
</div>
@endsection
