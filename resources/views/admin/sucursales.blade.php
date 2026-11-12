@extends('layouts.app')
@section('titulo', 'Sucursales')
@section('subtitulo', 'Gestión multi-local')

@section('acciones')
    <button class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-600 transition-colors">
        + Nuevo Registro
    </button>
@endsection

@section('contenido')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
        <h3 class="font-bold text-gray-800">Listado de Sucursales</h3>
        <input type="text" placeholder="Buscar..." class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
    </div>
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-white text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                <th class="px-6 py-4 font-semibold">ID</th>
                <th class="px-6 py-4 font-semibold">Descripción</th>
                <th class="px-6 py-4 font-semibold">Estado</th>
                <th class="px-6 py-4 font-semibold">Fecha</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">#001</td>
                <td class="px-6 py-4 font-medium">Registro de prueba - Sucursales</td>
                <td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Activo</span></td>
                <td class="px-6 py-4">Hoy</td>
            </tr>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">#002</td>
                <td class="px-6 py-4 font-medium">Ejemplo de datos generados</td>
                <td class="px-6 py-4"><span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">Pendiente</span></td>
                <td class="px-6 py-4">Ayer</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection