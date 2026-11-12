@extends('layouts.app')
@section('titulo', 'Repartidores')
@section('contenido')
<div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 border-b"><tr><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Vehículo</th><th class="px-4 py-3">Placa</th><th class="px-4 py-3">Teléfono</th><th class="px-4 py-3">Estado</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            <tr class="hover:bg-gray-50"><td class="px-4 py-3 font-bold">Carlos Mendoza</td><td class="px-4 py-3">Motocicleta</td><td class="px-4 py-3">ABC-123</td><td class="px-4 py-3">999 888 777</td><td class="px-4 py-3"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Activo</span></td></tr>
            <tr class="hover:bg-gray-50"><td class="px-4 py-3 font-bold">Luis Quispe</td><td class="px-4 py-3">Bicicleta</td><td class="px-4 py-3">-</td><td class="px-4 py-3">911 000 000</td><td class="px-4 py-3"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Activo</span></td></tr>
        </tbody>
    </table>
</div>
@endsection