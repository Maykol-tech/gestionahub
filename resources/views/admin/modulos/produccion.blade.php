@extends('layouts.app')
@section('titulo', 'Producción')
@section('subtitulo', 'Inventario de insumos y recetas')
@section('contenido')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4">Insumos</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-gray-500 border-b"><th class="text-left pb-2">Insumo</th><th class="text-left pb-2">Stock</th><th class="text-left pb-2">Mínimo</th></tr></thead>
            <tbody>
                @foreach($insumos as $i)
                <tr class="border-b border-gray-50"><td class="py-2">{{ $i->nombre }}</td><td class="{{ $i->stock <= $i->min ? 'text-red-500 font-bold' : '' }}">{{ $i->stock }}</td><td>{{ $i->min }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4">Recetas</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-gray-500 border-b"><th class="text-left pb-2">Producto</th><th class="text-left pb-2">Costo</th><th class="text-left pb-2">Margen</th></tr></thead>
            <tbody>
                @foreach($recetas as $r)
                <tr class="border-b border-gray-50"><td class="py-2">{{ $r->nombre }}</td><td>S/ {{ number_format($r->costo, 2) }}</td><td class="text-green-500 font-bold">{{ $r->margen }}%</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection