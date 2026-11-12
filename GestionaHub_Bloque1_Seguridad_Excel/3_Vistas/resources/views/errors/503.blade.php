@extends('errors.layout')
@section('codigo', '503')
@section('titulo', 'Volvemos enseguida')
@section('mensaje', 'Estamos haciendo mantenimiento para mejorar GestionaHub. Intenta de nuevo en unos minutos.')
@section('acciones')
    <button class="p" type="button" onclick="location.reload()">Reintentar</button>
@endsection
