@extends('layouts.app')
@section('titulo', 'Horarios de Atención')
@section('subtitulo', 'Configura tus turnos de apertura y cierre')

@section('contenido')
@if(session('success'))
    <div class="bg-green-50 text-green-700 p-3 rounded-lg mb-4">{{ session('success') }}</div>
@endif

<form action="{{ route('admin.horarios.update') }}" method="POST" id="formHorarios">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @php
            $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
        @endphp
        @foreach($dias as $num => $nombre)
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold text-gray-800">{{ $nombre }}</h3>
                <button type="button" onclick="addShift({{ $num }})" class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-lg font-semibold">+ Turno</button>
            </div>
            <div id="turnos-{{ $num }}" class="space-y-2">
                @forelse($horarios->where('dia_semana', $num) as $h)
                <div class="flex items-center gap-2" id="turno">
                    <input type="time" name="shifts[{{ $num }}][][apertura]" value="{{ \Carbon\Carbon::parse($h->apertura)->format('H:i') }}" class="border rounded-lg p-2 text-sm w-32">
                    <span class="text-gray-400">—</span>
                    <input type="time" name="shifts[{{ $num }}][][cierre]" value="{{ \Carbon\Carbon::parse($h->cierre)->format('H:i') }}" class="border rounded-lg p-2 text-sm w-32">
                    <button type="button" onclick="removeShift(this)" class="text-red-500 hover:text-red-700 text-sm ml-2">Quitar</button>
                </div>
                @empty
                <p class="text-xs text-gray-400 italic" id="no-turnos">Cerrado (sin turnos)</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="mt-6 flex justify-end">
        <button type="submit" class="bg-[#F47B20] text-white px-6 py-3 rounded-lg font-bold shadow-lg">Guardar horarios</button>
    </div>
</form>

<script>
function addShift(dia) {
    const container = document.getElementById('turnos-' + dia);
    const noTurnos = container.querySelector('#no-turnos');
    if (noTurnos) noTurnos.remove();
    
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2';
    div.id = 'turno';
    div.innerHTML = `
        <input type="time" name="shifts[${dia}][][apertura]" class="border rounded-lg p-2 text-sm w-32" value="09:00">
        <span class="text-gray-400">—</span>
        <input type="time" name="shifts[${dia}][][cierre]" class="border rounded-lg p-2 text-sm w-32" value="18:00">
        <button type="button" onclick="removeShift(this)" class="text-red-500 hover:text-red-700 text-sm ml-2">Quitar</button>
    `;
    container.appendChild(div);
}
function removeShift(btn) {
    btn.parentElement.remove();
}
</script>
@endsection