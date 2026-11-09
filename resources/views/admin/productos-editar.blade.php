@extends('layouts.app')

@section('titulo', 'Editar Producto')

@section('contenido')
    <div style="max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; border: 1px solid #E2E8F0;">
        <h2>Editar Producto</h2>
        <form action="/admin/productos/{{ $producto->id }}" method="POST">
            @csrf
            @method('PUT')
            <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
                <div>
                    <label>Nombre</label>
                    <input type="text" name="nombre" value="{{ $producto->nombre }}" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                </div>
                <div>
                    <label>Precio</label>
                    <input type="number" step="0.01" name="precio" value="{{ $producto->precio }}" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                </div>
                <div>
                    <label>Stock</label>
                    <input type="number" name="stock" value="{{ $producto->stock }}" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <a href="/admin/productos" style="padding: 10px 20px; border: 1px solid #ccc; background: white; border-radius: 5px; text-decoration: none; color: #333;">Cancelar</a>
                <button type="submit" class="btn-cta">Actualizar</button>
            </div>
        </form>
    </div>
@endsection