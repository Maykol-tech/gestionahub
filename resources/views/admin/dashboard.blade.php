@extends('layouts.app')

@section('titulo', $saludo . ', ' . ($layout['negocio'] ?? 'Mi negocio'))

@section('acciones')
    <a href="{{ route('admin.productos') }}?nuevo=1" class="btn btn-p"><x-icon name="plus" size="15" stroke="2.4" /> Nuevo producto</a>
@endsection

@push('styles')
<style>
    .rango-sel { display:inline-flex; gap:4px; padding:4px; background:var(--surface); border:1px solid var(--border); border-radius:12px; margin-bottom:14px; }
    .rango-sel a { padding:7px 16px; border-radius:9px; font-size:13px; font-weight:600; color:var(--fg3); text-decoration:none; transition:background .15s, color .15s; }
    .rango-sel a:hover { color:var(--fg); }
    .rango-sel a.on { background:var(--accent); color:#fff; }
</style>
@endpush

@section('contenido')
@if (! ($layout['onboarding_listo'] ?? true) && \Illuminate\Support\Facades\Route::has('admin.bienvenida'))
    <a href="{{ route('admin.bienvenida') }}" class="panel fade-in" style="display:flex;align-items:center;gap:14px;padding:16px 18px;margin-bottom:16px;text-decoration:none;color:inherit;border:1px solid var(--accent);background:var(--acl);">
        <span style="font-size:26px;">🚀</span>
        <span style="flex:1;"><b style="display:block;">Configura tu negocio en 4 pasos</b><span style="font-size:13px;color:var(--fg2);">Elige tu rubro, sube tu logo y te dejamos el menú y los ejemplos listos.</span></span>
        <span class="btn btn-p btn-sm">Empezar</span>
    </a>
@endif
    <div class="rango-sel fade-in" role="tablist" aria-label="Periodo de las tarjetas">
        @foreach (['hoy' => 'Hoy', 'semana' => 'Semana', 'mes' => 'Mes'] as $k => $t)
            <a href="{{ route('admin.dashboard', ['rango' => $k]) }}" class="{{ $datos['rango'] === $k ? 'on' : '' }}" role="tab" aria-selected="{{ $datos['rango'] === $k ? 'true' : 'false' }}">{{ $t }}</a>
        @endforeach
    </div>
    <div class="stat-row">
        <div class="stat fade-in" style="animation-delay:.02s">
            <div class="stat-lbl">Ventas {{ $datos['rango_texto'] }}</div>
            <div class="stat-val">S/ {{ number_format($datos['ventas_hoy'], 2) }}</div>
            <div class="stat-ch {{ $datos['ventas_variacion'] >= 0 ? 'up' : 'dn' }}">
                {{ $datos['ventas_variacion'] >= 0 ? '↑' : '↓' }} {{ abs($datos['ventas_variacion']) }}% {{ $datos['rango_compara'] }}
            </div>
        </div>
        <div class="stat fade-in" style="animation-delay:.08s">
            <div class="stat-lbl">Pedidos {{ $datos['rango_texto'] }}</div>
            <div class="stat-val">{{ $datos['pedidos_hoy'] }}</div>
            <div class="stat-ch neutral">{{ $datos['pedidos_pendientes'] }} {{ $datos['pedidos_pendientes'] === 1 ? 'pendiente' : 'pendientes' }}</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.14s">
            <div class="stat-lbl">Clientes nuevos</div>
            <div class="stat-val">{{ $datos['clientes_nuevos'] }}</div>
            <div class="stat-ch up">↑ {{ $datos['rango_texto'] }} · {{ $datos['clientes_total'] }} en total</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.2s">
            <div class="stat-lbl">Gasto publicidad</div>
            <div class="stat-val">S/ {{ number_format($datos['gasto_publicidad'], 2) }}</div>
            <div class="stat-ch neutral">{{ $datos['roi_publicidad'] !== null ? 'ROI ' . number_format($datos['roi_publicidad'], 1) . 'x' : 'Sin campañas registradas' }} · {{ $datos['productos_activos'] }} productos activos</div>
        </div>
    </div>

    <x-ia modulo="dashboard" delay=".25s" />

    <div class="two">
        <div class="fade-in" style="animation-delay:.3s">
            <div class="sec-ttl" style="display:flex;justify-content:space-between;">
                Últimos pedidos <a href="{{ route('admin.pedidos') }}" style="color:var(--accent);font-size:12px;font-weight:600;">Ver todos</a>
            </div>
            <div class="card tbl-wrap" style="padding:0;">
                @if ($ultimosPedidos->isEmpty())
                    <div class="empty"><x-icon name="inbox" size="30" /><span>Aún no hay pedidos registrados</span></div>
                @else
                    <table class="tbl">
                        <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                        <tbody>
                        @foreach ($ultimosPedidos as $p)
                            <tr>
                                <td style="font-weight:700;">#{{ $p['numero'] }}</td>
                                <td>{{ $p['cliente'] }}</td>
                                <td style="font-weight:600;">S/ {{ number_format($p['total'], 2) }}</td>
                                <td><span class="badge {{ $p['badge'] }}">{{ $p['estado_texto'] }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="fade-in" style="animation-delay:.36s">
            <div class="sec-ttl" style="display:flex;justify-content:space-between;">
                Stock bajo — alerta <a href="{{ route('admin.productos') }}" style="color:var(--accent);font-size:12px;font-weight:600;">Ver productos</a>
            </div>
            <div class="card tbl-wrap" style="padding:0;">
                @if ($stockBajo->isEmpty())
                    <div class="empty"><x-icon name="check-circle" size="30" /><span>Todo el stock está en orden</span></div>
                @else
                    <table class="tbl">
                        <thead><tr><th>Producto</th><th>Stock</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($stockBajo as $prod)
                            <tr>
                                <td>{{ $prod->nombre }}</td>
                                <td><span class="badge {{ $prod->stock <= 0 ? 'b-r' : 'b-a' }}">{{ $prod->stock }} und.</span></td>
                                <td style="text-align:right;"><a href="{{ route('admin.productos') }}?editar={{ $prod->id }}" class="btn btn-g btn-sm">Reponer</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
