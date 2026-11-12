@extends('layouts.app')
@section('titulo', 'Clientes')
@section('subtitulo', 'Gestiona tu cartera de clientes')
@section('acciones')
    <button onclick="document.getElementById('modalNuevoCliente').style.display='flex'" class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-semibold">+ Nuevo Cliente</button>
@endsection
@section('contenido')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-xs text-gray-500">TOTAL CLIENTES</p><h3 class="text-2xl font-bold">{{ $total }}</h3></div>
    <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-xs text-gray-500">ACTIVOS</p><h3 class="text-2xl font-bold text-green-500">{{ $activos }}</h3></div>
    <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-xs text-gray-500">FRECUENTES</p><h3 class="text-2xl font-bold text-orange-500">{{ $frecuentes }}</h3></div>
    <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-xs text-gray-500">NUEVOS (7 DÍAS)</p><h3 class="text-2xl font-bold text-blue-500">{{ $nuevos }}</h3></div>
</div>
<div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 border-b"><tr><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Teléfono</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Registro</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($clientes as $c)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-bold">{{ $c->nombre }}</td>
                <td class="px-4 py-3">{{ $c->telefono }}</td>
                <td class="px-4 py-3">{{ $c->email }}</td>
                <td class="px-4 py-3">{{ $c->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center py-4 text-gray-500">No hay clientes.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- MODAL NUEVO CLIENTE -->
<div id="modalNuevoCliente" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div class="bg-white p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Nuevo Cliente</h3>
        <form onsubmit="event.preventDefault(); alert('Cliente guardado con éxito'); document.getElementById('modalNuevoCliente').style.display='none';">
            <input type="text" placeholder="Nombre completo" required class="w-full border rounded-lg p-2 mb-3">
            <input type="tel" placeholder="Teléfono" required class="w-full border rounded-lg p-2 mb-3">
            <input type="email" placeholder="Correo" class="w-full border rounded-lg p-2 mb-4">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalNuevoCliente').style.display='none'" class="px-4 py-2 border rounded-lg">Cancelar</button>
                <button type="submit" class="bg-[#2B6EF6] text-white px-4 py-2 rounded-lg font-bold">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection