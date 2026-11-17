@extends('errors.layout')
@section('codigo', '404')
@section('titulo', 'Página no encontrada')
@section('mensaje', 'La dirección no existe o el registro fue eliminado.')
@section('acciones')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/admin') }}">Volver</a>
    <a class="p" href="{{ url('/admin') }}">Ir al panel</a>
@endsection
