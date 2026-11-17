@extends('layouts.app')

@section('titulo', 'Prospectos')
@section('subtitulo', 'Posibles clientes para contactar y convertir')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .filtros { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; }
        .filtros a { font-size:12px; font-weight:700; padding:5px 12px; border-radius:99px; border:1px solid var(--border); color:var(--fg2); text-decoration:none; }
        .filtros a.activo { background:var(--brand); color:#fff; border-color:var(--brand); }
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
        <div style="font-weight:700;margin-bottom:10px;">Agregar prospecto</div>
        <form method="POST" action="{{ route('admin.prospectos.guardar') }}" class="grid-2">
            @csrf
            <div><label class="lbl" for="nombre">Nombre o negocio</label><input class="inp" id="nombre" name="nombre" maxlength="150" required></div>
            <div><label class="lbl" for="telefono">Teléfono</label><input class="inp" id="telefono" name="telefono" maxlength="30"></div>
            <div><label class="lbl" for="email">Correo</label><input class="inp" id="email" name="email" type="email" maxlength="150"></div>
            <div><label class="lbl" for="rubro">Rubro</label><input class="inp" id="rubro" name="rubro" maxlength="80"></div>
            <div style="grid-column:1 / -1;"><label class="lbl" for="notas">Notas</label><input class="inp" id="notas" name="notas" maxlength="1000"></div>
            <div style="grid-column:1 / -1;"><button class="btn btn-p" type="submit">Guardar prospecto</button></div>
        </form>
    </div>

    <div class="panel fade-in" style="padding:18px;margin-bottom:12px;">
        <div style="font-weight:700;margin-bottom:4px;">Buscar prospectos con IA</div>
        <div style="font-size:12px;color:var(--fg3);margin-bottom:10px;">El microservicio busca negocios por rubro y zona. Los repetidos (mismo teléfono o correo) no se duplican.</div>
        <form method="POST" action="{{ route('admin.prospectos.buscar_ia') }}" class="grid-2" style="align-items:end;">
            @csrf
            <div><label class="lbl" for="rubro_ia">Rubro</label><input class="inp" id="rubro_ia" name="rubro" maxlength="80" placeholder="Restaurantes, peluquerías…" required></div>
            <div><label class="lbl" for="zona_ia">Zona</label><input class="inp" id="zona_ia" name="zona" maxlength="80" placeholder="Miraflores, Lima" required></div>
            <div><label class="lbl" for="limite_ia">Máximo a buscar</label><input class="inp" id="limite_ia" name="limite" type="number" min="1" max="50" value="20"></div>
            <div><button class="btn btn-p" type="submit">Buscar prospectos</button></div>
        </form>
    </div>

    <div class="filtros">
        <a href="{{ route('admin.prospectos') }}" class="{{ $filtro ? '' : 'activo' }}">Todos ({{ $conteos->sum() }})</a>
        @foreach (\App\Models\Prospecto::ESTADOS as $k => $label)
            <a href="{{ route('admin.prospectos', ['estado' => $k]) }}" class="{{ $filtro === $k ? 'activo' : '' }}">{{ $label }} ({{ $conteos[$k] ?? 0 }})</a>
        @endforeach
    </div>

    <div class="panel fade-in" style="padding:0;">
        <div class="tbl-wrap" @if ($prospectos->isEmpty()) style="display:none" @endif>
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Prospecto</th>
                        <th>Contacto</th>
                        <th>Fuente</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prospectos as $p)
                        <tr>
                            <td>{{ $p->nombre }}<div style="font-size:11px;color:var(--fg3);">{{ $p->rubro }}</div></td>
                            <td>{{ $p->telefono ?: '—' }}<div style="font-size:11px;color:var(--fg3);">{{ $p->email }}</div></td>
                            <td>{{ $p->fuente }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.prospectos.estado', $p->id) }}">
                                    @csrf
                                    <select class="inp" name="estado" onchange="this.form.submit()" style="width:auto;">
                                        @foreach (\App\Models\Prospecto::ESTADOS as $k => $label)
                                            <option value="{{ $k }}" @selected($p->estado === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($prospectos->isEmpty())
            <div class="empty"><div style="font-weight:600;color:var(--fg2);">No hay prospectos en este filtro</div></div>
        @endif
    </div>
</div>
@endsection
