@extends('errors.layout')
@section('codigo', '500')
@section('titulo', 'Algo salió mal')
@section('mensaje', 'Ocurrió un error inesperado. Ya quedó registrado para revisarlo. Intenta de nuevo en unos segundos.')
@section('acciones')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/admin') }}">Volver</a>
    <a class="p" href="{{ url('/admin') }}">Ir al panel</a>
@endsection
