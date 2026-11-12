@extends('layouts.app')
@section('titulo', 'Entregas')
@section('subtitulo', 'Formas y zonas')
@section('acciones')
    <button class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-600">+ Nuevo Registro</button>
@endsection
@section('contenido')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50">
        <h3 class="font-bold text-gray-800">Módulo de Entregas</h3>
    </div>
    <div class="p-10 text-center text-gray-500">
        <p class="text-lg font-semibold mb-2">Módulo operativo</p>
        <p class="text-sm">Configura los datos y empieza a usar Entregas.</p>
    </div>
</div>
@endsection