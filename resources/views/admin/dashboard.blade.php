@extends('layouts.app')
@section('titulo', 'Inicio')
@section('subtitulo', 'Bienvenido a tu panel')

@section('contenido')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-xs font-bold text-gray-500">VENTAS HOY</p>
        <h3 class="text-2xl font-bold text-green-600">S/ {{ number_format($ventasHoy ?? 0, 2) }}</h3>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-xs font-bold text-gray-500">PEDIDOS HOY</p>
        <h3 class="text-2xl font-bold">{{ $pedidosHoy ?? 0 }}</h3>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-xs font-bold text-gray-500">CLIENTES NUEVOS</p>
        <h3 class="text-2xl font-bold text-blue-600">{{ $clientesNuevos ?? 0 }}</h3>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-xs font-bold text-gray-500">PRODUCTOS ACTIVOS</p>
        <h3 class="text-2xl font-bold">{{ $productosActivos ?? 0 }}</h3>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <h3 class="font-bold mb-4">Últimos Pedidos</h3>
    @forelse($ultimosPedidos ?? [] as $p)
        <div class="flex justify-between py-2 border-b border-gray-100">
            <div>
                <div class="font-bold text-[#2B6EF6]">{{ $p->numero }}</div>
                <div class="text-xs text-gray-500">{{ $p->cliente->nombre ?? 'Cliente varios' }}</div>
            </div>
            <div class="font-bold">S/ {{ number_format($p->total, 2) }}</div>
        </div>
    @empty
        <p class="text-gray-500 text-sm">No hay pedidos recientes.</p>
    @endforelse
</div>
@endsection