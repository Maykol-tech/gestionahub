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
    </style>
@endpush

@section('contenido')
<div>
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif

    <div class="kpis">
        <div class="panel kpi fade-in"><div class="n">{{ $promedio ?? '”' }}</div><div class="l">Promedio publicado</div></div>
        <div class="panel kpi fade-in"><div class="n">{{ $total }}</div><div class="l">Reseñas publicadas</div></div>
        <div class="panel kpi fade-in"><div class="n">{{ $pendientes }}</div><div class="l">Pendientes de revisar</div></div>
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
                            <td><span class="estrellas">{{ str_repeat('â˜…', $r->calificacion) }}</span></td>
                            <td style="max-width:320px;">{{ $r->comentario ?: '”' }}</td>
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
