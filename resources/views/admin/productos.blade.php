@extends('layouts.app')
@section('titulo', 'Productos')
@section('subtitulo', 'Gestiona tu catálogo de productos')
@section('acciones')
    <button class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-600">+ Nuevo producto</button>
@endsection
@section('contenido')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><p class="text-xs text-gray-500 font-bold">PRODUCTOS</p><h3 class="text-2xl font-bold">{{ $totalProductos }}</h3></div>
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><p class="text-xs text-gray-500 font-bold">ACTIVOS</p><h3 class="text-2xl font-bold text-green-500">{{ $activos }}</h3></div>
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><p class="text-xs text-gray-500 font-bold">STOCK BAJO</p><h3 class="text-2xl font-bold text-red-500">{{ $stockBajo }}</h3></div>
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100"><p class="text-xs text-gray-500 font-bold">VALOR INVENTARIO</p><h3 class="text-2xl font-bold">S/ {{ number_format($valorInventario, 2) }}</h3></div>
</div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead><tr class="bg-gray-50 text-xs text-gray-500 uppercase"><th class="px-6 py-4">Producto</th><th class="px-6 py-4">Precio</th><th class="px-6 py-4">Stock</th><th class="px-6 py-4">Estado</th></tr></thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @foreach($productos as $p)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-medium">{{ $p->nombre }}</td>
                <td class="px-6 py-4">S/ {{ number_format($p->precio, 2) }}</td>
                <td class="px-6 py-4">{{ $p->stock }} und.</td>
                <td class="px-6 py-4"><span class="{{ $p->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} px-2 py-1 rounded text-xs font-bold">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection