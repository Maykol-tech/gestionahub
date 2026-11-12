@extends('layouts.app')
@section('titulo', 'Repartos')
@section('subtitulo', 'Gestión de delivery y repartidores')
@section('contenido')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4">Repartidores Activos</h3>
        <div class="space-y-3">
            @foreach($repartidores as $r)
            <div class="flex items-center justify-between p-3 border border-gray-100 rounded-lg bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold">{{ substr($r->nombre_completo, 0, 1) }}</div>
                    <div><h4 class="font-bold text-sm">{{ $r->nombre_completo }}</h4><p class="text-xs text-gray-500">{{ $r->tipo_vehiculo }} - {{ $r->placa }}</p></div>
                </div>
                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Disponible</span>
            </div>
            @endforeach
            @if($repartidores->isEmpty())
            <p class="text-sm text-gray-500">No hay repartidores registrados.</p>
            @endif
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4">Pedidos en Camino</h3>
        @foreach($pedidos as $p)
            <div class="p-3 border border-gray-100 rounded-lg mb-3">
                <div class="flex justify-between mb-1"><span class="font-bold text-[#2B6EF6]">{{ $p->numero }}</span><span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">En camino</span></div>
                <p class="text-sm text-gray-600">{{ $p->cliente->nombre ?? 'Cliente' }}</p>
            </div>
        @endforeach
        @if($pedidos->isEmpty())
        <p class="text-sm text-gray-500">No hay pedidos en camino.</p>
        @endif
    </div>
</div>
@endsection