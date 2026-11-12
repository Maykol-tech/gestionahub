@extends('layouts.app')
@section('titulo', 'Mesas y QR')
@section('contenido')
<div class="space-y-8">
    @foreach(['Terraza', 'Salón', 'Barra'] as $zona)
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">{{ $zona }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @for($i = 1; $i <= 3; $i++)
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 text-center">
                <div class="w-10 h-10 bg-blue-50 text-[#2B6EF6] rounded-full flex items-center justify-center mx-auto mb-2 font-bold">{{ $i }}</div>
                <h3 class="font-bold">Mesa {{ $i }}</h3>
                <p class="text-xs text-gray-500 mb-3">Capacidad: 4 personas</p>
                <button class="w-full bg-[#F47B20] text-white text-sm py-1.5 rounded-lg font-semibold">Ver QR</button>
            </div>
            @endfor
        </div>
    </div>
    @endforeach
</div>
@endsection