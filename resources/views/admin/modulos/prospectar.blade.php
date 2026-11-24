@extends('layouts.app')

@section('titulo', 'Prospectar')
@section('subtitulo', 'Encuentra negocios que pueden ser tus clientes')

@section('acciones')
    <a href="{{ route('admin.prospectos') }}" class="btn btn-g"><x-icon name="users" size="15" /> Mis prospectos</a>
@endsection

@section('contenido')
<div>
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <x-ia modulo="prospectar" delay=".05s" />

    <div class="stat-row">
        <div class="stat fade-in">
            <div class="stat-lbl">Encontrados <span class="stat-ico" style="background:var(--acl);color:var(--accent)"><x-icon name="search" size="14" /></span></div>
            <div class="stat-val">{{ $prospectos->count() }}</div>
            <div class="stat-ch neutral">Negocios en tu lista</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.05s">
            <div class="stat-lbl">Por contactar <span class="stat-ico" style="background:var(--ambl);color:var(--amber)"><x-icon name="clock" size="14" /></span></div>
            <div class="stat-val">{{ $conteos['nuevo'] ?? 0 }}</div>
            <div class="stat-ch neutral">Estado nuevo</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.1s">
            <div class="stat-lbl">Interesados <span class="stat-ico" style="background:var(--greenl);color:var(--green)"><x-icon name="check-circle" size="14" /></span></div>
            <div class="stat-val">{{ $conteos['interesado'] ?? 0 }}</div>
            <div class="stat-ch neutral">Listos para tu oferta</div>
        </div>
    </div>

    <form class="panel fade-in" style="margin-bottom:16px;" method="POST" action="{{ route('admin.prospectos.buscar_ia') }}">
        @csrf
        <div class="panel-hd"><div><div class="panel-ttl">Buscar negocios cercanos</div><div class="panel-sub">Los repetidos (mismo teléfono o correo) no se duplican</div></div></div>
        <div class="busq">
            <div class="field" style="margin:0;">
                <label class="lbl" for="rubro">Rubro</label>
                <input class="inp" id="rubro" name="rubro" list="rubros" maxlength="80" required value="{{ old('rubro') }}" placeholder="Restaurantes, bodegas…">
                <datalist id="rubros">
                    <option>Restaurantes</option><option>Bodegas</option><option>Panaderías</option>
                    <option>Cafeterías</option><option>Oficinas</option><option>Colegios</option><option>Peluquerías</option>
                </datalist>
            </div>
            <div class="field" style="margin:0;">
                <label class="lbl" for="zona">Distrito o zona</label>
                <input class="inp" id="zona" name="zona" maxlength="80" required value="{{ old('zona') }}" placeholder="Ej: San Martín de Porres, Lima">
            </div>
            <div class="field" style="margin:0;">
                <label class="lbl" for="limite">Máximo</label>
                <select class="inp" id="limite" name="limite">
                    @foreach ([10, 20, 30, 50] as $n)<option value="{{ $n }}" @selected((int) old('limite', 20) === $n)>{{ $n }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-p" style="align-self:flex-end;"><x-icon name="search" size="15" /> Buscar</button>
        </div>
    </form>

    <div class="panel fade-in" style="animation-delay:.08s">
        <div class="panel-hd">
            <div>
                <div class="panel-ttl">Prospectos</div>
                <div class="panel-sub">{{ $prospectos->count() }} {{ $prospectos->count() === 1 ? 'encontrado' : 'encontrados' }}</div>
            </div>
        </div>
        @if ($prospectos->isEmpty())
            <x-vacio icono="search" titulo="Aún no hay prospectos" texto="Elige un rubro y una zona para encontrar negocios cercanos a los que puedas ofrecer tus productos." />
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead><tr><th>Negocio</th><th>Rubro</th><th>Teléfono</th><th>Fuente</th><th>Estado</th></tr></thead>
                    <tbody>
                        @foreach ($prospectos as $p)
                            <tr>
                                <td style="font-weight:600;">{{ $p->nombre }}@if ($p->notas)<div style="font-size:11px;color:var(--fg3);font-weight:400;">{{ \Illuminate\Support\Str::limit($p->notas, 70) }}</div>@endif</td>
                                <td>{{ $p->rubro ?: '—' }}</td>
                                <td>
                                    @if ($p->telefono)
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $p->telefono) }}" target="_blank" rel="noopener" style="color:var(--accent);font-weight:600;">{{ $p->telefono }}</a>
                                    @else — @endif
                                </td>
                                <td>{{ $p->fuente }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.prospectos.estado', $p->id) }}">
                                        @csrf
                                        <select class="inp" name="estado" onchange="this.form.submit()" style="width:auto;padding:6px 8px;font-size:12px;">
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
        @endif
    </div>
</div>
@endsection

@push('styles')
    @include('admin.modulos._base')
    <style>
        .busq{display:grid;grid-template-columns:1fr 1.4fr .5fr auto;gap:12px;padding:16px;}
        @media (max-width:860px){ .busq{grid-template-columns:1fr;} }
    </style>
@endpush
