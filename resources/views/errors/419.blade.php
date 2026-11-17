@extends('errors.layout')
@section('codigo', '419')
@section('titulo', 'Tu sesión expiró')
@section('mensaje', 'Por seguridad el formulario caducó. Vuelve a cargar la página e inténtalo otra vez; no perdiste nada.')
@section('acciones')
    <a class="p" href="{{ url()->previous() ?: url('/login') }}">Volver a cargar</a>
@endsection
