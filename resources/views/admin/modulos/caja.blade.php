@extends('layouts.app')
@section('titulo', 'Caja del Día')
@section('subtitulo', 'Resumen de ingresos y egresos')

@section('acciones')
    <button class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-semibold">+ Registrar Gasto</button>
@endsection

@section('contenido')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500 font-semibold">INGRESOS DE HOY</p>
        <h3 class="text-2xl font-bold text-green-600">S/ {{ number_format($ingresos, 2) }}</h3>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500 font-semibold">EGRESOS DE HOY</p>
        <h3 class="text-2xl font-bold text-red-600">S/ {{ number_format($egresos, 2) }}</h3>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500 font-semibold">RESULTADO</p>
        <h3 class="text-2xl font-bold text-blue-600">S/ {{ number_format($ingresos - $egresos, 2) }}</h3>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 bg-gray-50 border-b"><h3 class="font-bold">Movimientos de Hoy ({{ $ventasHoy->count() }})</h3></div>
    @if($ventasHoy->count() > 0)
    <table class="w-full text-left text-sm">
        <thead><tr class="bg-white text-gray-500 border-b"><th class="px-4 py-3">Hora</th><th class="px-4 py-3">N° Pedido</th><th class="px-4 py-3">Canal</th><th class="px-4 py-3 text-right">Monto</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($ventasHoy as $v)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">{{ $v->created_at->format('H:i') }}</td>
                <td class="px-4 py-3 font-bold">{{ $v->numero }}</td>
                <td class="px-4 py-3 uppercase text-xs">{{ $v->canal }}</td>
                <td class="px-4 py-3 text-right font-bold text-green-600">S/ {{ number_format($v->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="p-10 text-center text-gray-500">No hay movimientos registrados hoy.</div>
    @endif
</div>
@endsection