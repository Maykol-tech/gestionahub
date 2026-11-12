@extends('layouts.app')
@section('titulo', 'Ingresos Manuales')
@section('contenido')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead><tr class="bg-gray-50 text-gray-500 border-b"><th class="px-4 py-3">Número</th><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Total</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($ingresos as $i)
            <tr class="hover:bg-gray-50"><td class="px-4 py-3 font-bold">{{ $i->numero }}</td><td class="px-4 py-3">{{ \Carbon\Carbon::parse($i->created_at)->format('d/m/Y H:i') }}</td><td class="px-4 py-3 font-bold">S/ {{ number_format($i->total, 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection