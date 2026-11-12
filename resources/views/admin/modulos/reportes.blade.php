@extends('layouts.app')
@section('titulo', 'Reportes')
@section('subtitulo', 'Analiza el rendimiento de tu negocio')
@section('contenido')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100"><p class="text-xs font-bold text-gray-500">VENTAS HOY</p><h3 class="text-2xl font-bold text-gray-800">S/ {{ number_format($ventasHoy, 2) }}</h3></div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100"><p class="text-xs font-bold text-gray-500">PEDIDOS HOY</p><h3 class="text-2xl font-bold text-gray-800">{{ $pedidosHoy }}</h3></div>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100"><p class="text-xs font-bold text-gray-500">TICKET PROMEDIO</p><h3 class="text-2xl font-bold text-gray-800">S/ {{ number_format($ventasHoy / max($pedidosHoy,1), 2) }}</h3></div>
</div>
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <h3 class="font-bold text-gray-800 mb-4">Ventas de los últimos 7 días</h3>
    <canvas id="chartVentas" height="80"></canvas>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('chartVentas').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{ label: 'Ventas (S/)', data: [1200, 1900, 1500, 2100, 2500, 3200, 2800], borderColor: '#2B6EF6', backgroundColor: 'rgba(43,110,246,0.1)', fill: true, tension: 0.4 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    });
</script>
@endsection