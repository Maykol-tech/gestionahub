@extends('layouts.app')
@section('titulo', 'Cobros y Pagos')
@section('subtitulo', 'Gestiona las cuentas por cobrar')
@section('contenido')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
        <h3 class="font-bold text-gray-800">Módulo de Cobros y Pagos</h3>
    </div>
    <table class="w-full text-left border-collapse">
        <thead><tr class="bg-white text-gray-500 text-xs uppercase border-b border-gray-200"><th class="px-6 py-4">ID</th><th class="px-6 py-4">Descripción</th><th class="px-6 py-4">Estado</th></tr></thead>
        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
            <tr class="hover:bg-gray-50"><td class="px-6 py-4">#001</td><td class="px-6 py-4 font-medium">Registro de prueba - Cobros y Pagos</td><td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Activo</span></td></tr>
        </tbody>
    </table>
</div>
@endsection