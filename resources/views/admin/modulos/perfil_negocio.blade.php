@extends('layouts.app')
@section('titulo', 'Perfil del Negocio')
@section('subtitulo', 'Configura los datos de tu empresa')
@section('contenido')
@if(session('success'))
    <div class="bg-green-50 text-green-700 p-3 rounded-lg mb-4">{{ session('success') }}</div>
@endif
<form action="{{ route('admin.perfil.update') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-6">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="text-sm font-semibold">Razón social</label><input type="text" name="razon_social" value="{{ $tenant->razon_social ?? '' }}" class="w-full border rounded-lg p-2"></div>
        <div><label class="text-sm font-semibold">Nombre comercial</label><input type="text" name="nombre_comercial" value="{{ $tenant->nombre ?? '' }}" class="w-full border rounded-lg p-2"></div>
        <div><label class="text-sm font-semibold">RUC (11 dígitos)</label><input type="text" name="ruc" value="{{ $tenant->ruc ?? '' }}" maxlength="11" class="w-full border rounded-lg p-2"></div>
        <div><label class="text-sm font-semibold">Teléfono</label><input type="text" name="telefono" value="{{ $tenant->telefono ?? '' }}" class="w-full border rounded-lg p-2"></div>
        <div><label class="text-sm font-semibold">Email</label><input type="email" name="email" value="{{ $tenant->email ?? '' }}" class="w-full border rounded-lg p-2"></div>
        <div><label class="text-sm font-semibold">Rubro</label><input type="text" name="rubro" value="{{ $tenant->rubro ?? '' }}" class="w-full border rounded-lg p-2"></div>
        <div class="md:col-span-2"><label class="text-sm font-semibold">Dirección</label><input type="text" name="direccion" value="{{ $tenant->direccion ?? '' }}" class="w-full border rounded-lg p-2"></div>
        <div class="md:col-span-2">
            <label class="text-sm font-semibold">Logo (JPG, PNG, WEBP, máx 2MB)</label>
            @if($tenant->logo_url ?? false)
                <img src="{{ $tenant->logo_url }}" style="max-width:120px;max-height:120px;border-radius:8px;margin-bottom:8px;display:block;">
            @endif
            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="w-full border rounded-lg p-2">
        </div>
    </div>
    <div class="mt-6 flex justify-end">
        <button type="submit" class="bg-[#F47B20] text-white px-6 py-2 rounded-lg font-semibold">Guardar cambios</button>
    </div>
</form>
@endsection