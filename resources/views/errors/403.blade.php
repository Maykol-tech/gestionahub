@extends('errors.layout')
@section('codigo', '403')
@section('titulo', 'No tienes permiso')
@section('mensaje', 'Tu usuario no tiene acceso a esta sección. Si crees que es un error, pide al administrador de tu negocio que revise tu rol.')
@section('acciones')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/admin') }}">Volver</a>
    <a class="p" href="{{ url('/admin') }}">Ir al panel</a>
@endsection
