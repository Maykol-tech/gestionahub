@extends('layouts.app')
@section('titulo', 'Proveedores')
@section('contenido')
<div class="space-y-8">
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">Abarrotes</h2>
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b"><tr><th class="px-4 py-3">Razón Social</th><th class="px-4 py-3">RUC</th><th class="px-4 py-3">Contacto</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50"><td class="px-4 py-3 font-bold">Distribuidora SAC</td><td class="px-4 py-3">20123456789</td><td class="px-4 py-3">Juan Perez</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">Bebidas</h2>
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b"><tr><th class="px-4 py-3">Razón Social</th><th class="px-4 py-3">RUC</th><th class="px-4 py-3">Contacto</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50"><td class="px-4 py-3 font-bold">Bebidas del Sur</td><td class="px-4 py-3">20987654321</td><td class="px-4 py-3">Maria Lopez</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection