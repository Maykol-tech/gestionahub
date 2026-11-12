@extends('layouts.app')
@section('titulo', 'Fidelización')
@section('subtitulo', 'Puntos, niveles y recompensas')
@section('acciones')
    <button class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-600">+ Configurar Puntos</button>
@endsection
@section('contenido')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50"><h3 class="font-bold text-gray-800">Clientes con Puntos</h3></div>
    <table class="w-full text-left border-collapse">
        <thead><tr class="bg-white text-xs text-gray-500 uppercase border-b border-gray-200"><th class="px-6 py-4">Cliente</th><th class="px-6 py-4">Nivel</th><th class="px-6 py-4">Puntos Acumulados</th></tr></thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @foreach($clientes as $index => $c)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-medium">{{ $c->nombre }}</td>
                <td class="px-6 py-4"><span class="{{ $index % 2 == 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700' }} px-2 py-1 rounded text-xs font-bold">{{ $index % 2 == 0 ? 'Oro' : 'Plata' }}</span></td>
                <td class="px-6 py-4 font-bold text-blue-600">{{ rand(50, 500) }} pts</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection