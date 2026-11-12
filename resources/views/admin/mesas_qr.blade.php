@extends('layouts.app')
@section('titulo', 'Código QR')
@section('contenido')
<div class="panel" style="text-align: center; padding: 40px;">
    <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">Mesa {{ $mesa->numero }}</h2>
    <p style="color: var(--fg3); margin-bottom: 24px;">Escanea este código para ver el menú o hacer un pedido.</p>
    <div style="display: flex; justify-content: center; margin-bottom: 24px;">
        {!! $qr !!}
    </div>
    <button onclick="window.print()" class="btn btn-p">Imprimir QR</button>
</div>
@endsection