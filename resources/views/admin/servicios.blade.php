@extends('layouts.app')
@section('titulo', 'Servicios')
@section('subtitulo', 'Catálogo de servicios y agenda')
@section('acciones')
    <button class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-600">+ Añadir Servicio</button>
@endsection
@section('contenido')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead><tr class="bg-gray-50 text-xs text-gray-500 uppercase border-b border-gray-200"><th class="px-6 py-4">Servicio</th><th class="px-6 py-4">Precio</th><th class="px-6 py-4">Duración</th><th class="px-6 py-4">Estado</th></tr></thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($servicios as $s)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-medium">{{ $s->nombre }}</td>
                <td class="px-6 py-4">S/ {{ number_format($s->precio, 2) }}</td>
                <td class="px-6 py-4">{{ $s->duracion }} min</td>
                <td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Activo</span></td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">No hay servicios registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection