@extends('layouts.app')
@section('titulo', 'Comprobantes')
@section('contenido')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead><tr class="bg-gray-50 text-gray-500 border-b"><th class="px-4 py-3">Número</th><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Estado</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($comprobantes as $c)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-bold">{{ $c->numero }}</td>
                <td class="px-4 py-3">{{ $c->cliente->nombre ?? 'Cliente Varios' }}</td>
                <td class="px-4 py-3">S/ {{ number_format($c->total, 2) }}</td>
                <td class="px-4 py-3"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Aceptado</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection