@extends('errors.layout')
@section('codigo', '429')
@section('titulo', 'Demasiados intentos')
@section('mensaje', 'Hiciste muchas solicitudes seguidas. Espera un momento y vuelve a intentarlo.')
@section('acciones')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/admin') }}">Volver</a>
    <a class="p" href="{{ url('/admin') }}">Ir al panel</a>
@endsection
