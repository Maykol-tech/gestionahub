@extends('layouts.app')
@section('titulo', 'Servicios')
@section('subtitulo', 'Catálogo y agenda')
@section('acciones')
    <button onclick="document.getElementById('modalNuevoServicio').style.display='flex'" class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-semibold">+ Nuevo Servicio</button>
@endsection
@section('contenido')
<div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 border-b"><tr><th class="px-4 py-3">Servicio</th><th class="px-4 py-3">Precio</th><th class="px-4 py-3">Duración</th><th class="px-4 py-3">Estado</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($servicios as $s)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-bold">{{ $s->nombre }}</td>
                <td class="px-4 py-3">S/ {{ number_format($s->precio, 2) }}</td>
                <td class="px-4 py-3">{{ $s->duracion }} min</td>
                <td class="px-4 py-3"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Activo</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- MODAL NUEVO SERVICIO -->
<div id="modalNuevoServicio" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div class="bg-white p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Nuevo Servicio</h3>
        <form onsubmit="event.preventDefault(); alert('Servicio guardado con éxito'); document.getElementById('modalNuevoServicio').style.display='none';">
            <input type="text" placeholder="Nombre del servicio" required class="w-full border rounded-lg p-2 mb-3">
            <input type="number" placeholder="Precio (S/)" step="0.01" required class="w-full border rounded-lg p-2 mb-3">
            <input type="number" placeholder="Duración (min)" value="30" required class="w-full border rounded-lg p-2 mb-4">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalNuevoServicio').style.display='none'" class="px-4 py-2 border rounded-lg">Cancelar</button>
                <button type="submit" class="bg-[#2B6EF6] text-white px-4 py-2 rounded-lg font-bold">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection