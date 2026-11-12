@extends('layouts.app')
@section('titulo', 'Editor del Popup')
@section('subtitulo', 'Configura el popup de conversión de la landing SaaS')

@section('contenido')
@if(session('success'))
    <div class="bg-green-50 text-green-700 p-3 rounded-lg mb-4">{{ session('success') }}</div>
@endif
<form action="{{ route('superadmin.popup.update') }}" method="POST" class="bg-white rounded-xl shadow-sm p-6">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold mb-1">Activo</label>
            <select name="popup_activo" class="w-full border rounded-lg p-2">
                <option value="1" {{ $config->popup_activo ? 'selected' : '' }}>Sí</option>
                <option value="0" {{ !$config->popup_activo ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Delay (segundos de tiempo activo)</label>
            <input type="number" name="popup_delay_seconds" value="{{ $config->popup_delay_seconds }}" class="w-full border rounded-lg p-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Exit intent (escritorio)</label>
            <select name="popup_exit_intent" class="w-full border rounded-lg p-2">
                <option value="1" {{ $config->popup_exit_intent ? 'selected' : '' }}>Sí</option>
                <option value="0" {{ !$config->popup_exit_intent ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">No mostrar por (días)</label>
            <input type="number" name="popup_frecuencia_dias" value="{{ $config->popup_frecuencia_dias }}" class="w-full border rounded-lg p-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Cuenta regresiva (min)</label>
            <input type="number" name="popup_cuenta_regresiva_min" value="{{ $config->popup_cuenta_regresiva_min }}" class="w-full border rounded-lg p-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Badge</label>
            <input type="text" name="popup_badge" value="{{ $config->popup_badge }}" class="w-full border rounded-lg p-2">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold mb-1">Título</label>
            <input type="text" name="popup_titulo" value="{{ $config->popup_titulo }}" class="w-full border rounded-lg p-2">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold mb-1">Texto</label>
            <textarea name="popup_texto" rows="2" class="w-full border rounded-lg p-2">{{ $config->popup_texto }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold mb-1">Beneficios (uno por línea)</label>
            <textarea name="popup_beneficios" rows="3" class="w-full border rounded-lg p-2">{{ implode("\n", (array)($config->popup_beneficios ?? [])) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">CTA</label>
            <input type="text" name="popup_cta" value="{{ $config->popup_cta }}" class="w-full border rounded-lg p-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Código de oferta</label>
            <input type="text" name="popup_oferta_codigo" value="{{ $config->popup_oferta_codigo }}" class="w-full border rounded-lg p-2">
        </div>
    </div>
    <div class="mt-6 flex justify-end">
        <button type="submit" class="bg-[#F47B20] text-white px-6 py-2 rounded-lg font-semibold">Guardar configuración</button>
    </div>
</form>
@endsection