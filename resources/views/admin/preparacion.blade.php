@extends('layouts.app')
@section('titulo', ucfirst($modulo))
@section('subtitulo', 'Módulo en preparación')

@section('contenido')
<div class="bg-white rounded-xl shadow-sm p-10 text-center">
    <div class="text-4xl mb-4 text-[#2B6EF6]">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
    </div>
    <h2 class="text-xl font-bold mb-2">Módulo en preparación</h2>
    <p class="text-gray-500">Esta sección estará disponible próximamente.</p>
</div>
@endsection