@extends('layouts.app')

@section('titulo', $saludo . ', ' . ($layout['negocio'] ?? 'Mi negocio'))

@section('acciones')
    <a href="{{ route('admin.productos') }}?nuevo=1" class="btn btn-p"><x-icon name="plus" size="15" stroke="2.4" /> Nuevo producto</a>
@endsection

@section('contenido')
    <div class="stat-row">
        <div class="stat fade-in" style="animation-delay:.02s">
            <div class="stat-lbl">Ventas hoy</div>
            <div class="stat-val">S/ {{ number_format($datos['ventas_hoy'], 2) }}</div>
            <div class="stat-ch {{ $datos['ventas_variacion'] >= 0 ? 'up' : 'dn' }}">
                {{ $datos['ventas_variacion'] >= 0 ? '↑' : '↓' }} {{ abs($datos['ventas_variacion']) }}% vs ayer
            </div>
        </div>
        <div class="stat fade-in" style="animation-delay:.08s">
            <div class="stat-lbl">Pedidos hoy</div>
            <div class="stat-val">{{ $datos['pedidos_hoy'] }}</div>
            <div class="stat-ch neutral">{{ $datos['pedidos_pendientes'] }} {{ $datos['pedidos_pendientes'] === 1 ? 'pendiente' : 'pendientes' }}</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.14s">
            <div class="stat-lbl">Clientes nuevos</div>
            <div class="stat-val">{{ $datos['clientes_nuevos'] }}</div>
            <div class="stat-ch up">↑ esta semana · {{ $datos['clientes_total'] }} en total</div>
        </div>
        <div class="stat fade-in" style="animation-delay:.2s">
            <div class="stat-lbl">Productos activos</div>
            <div class="stat-val">{{ $datos['productos_activos'] }}</div>
            <div class="stat-ch {{ $stockBajo->count() ? 'dn' : 'neutral' }}">{{ $stockBajo->count() }} con stock bajo</div>
        </div>
    </div>

    <div class="card fade-in" style="background:#F0F5FF;border-color:#C7D9F8;border-left:4px solid var(--accent);margin-bottom:16px;animation-delay:.25s">
        <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--accent);display:flex;align-items:center;gap:6px;margin-bottom:6px;">
            <x-icon name="sparkles" size="13" /> IA · Resumen semanal
        </div>
        <div style="font-size:15px;font-weight:700;">
            @if ($datos['ventas_semana'] > 0)
                Esta semana vendiste S/ {{ number_format($datos['ventas_semana'], 2) }} en {{ $datos['pedidos_semana'] }} pedidos
            @else
                Registra tus primeros pedidos para generar el resumen automático
            @endif
        </div>
    </div>

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
