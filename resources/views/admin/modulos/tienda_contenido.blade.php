@extends('layouts.app')

@section('titulo', 'Contenido de la tienda')
@section('subtitulo', 'Textos que ven tus clientes en tu tienda')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('contenido')
<div>
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tienda.contenido.guardar') }}" class="panel fade-in" style="padding:18px;">
        @csrf
        <div style="font-weight:700;margin-bottom:8px;">Portada</div>
        <div class="grid-2">
            <div><label class="lbl" for="portada_titulo">Título</label><input class="inp" id="portada_titulo" name="portada_titulo" maxlength="120" value="{{ old('portada_titulo', $c['portada']['titulo']) }}"></div>
            <div><label class="lbl" for="portada_subtitulo">Subtítulo</label><input class="inp" id="portada_subtitulo" name="portada_subtitulo" maxlength="250" value="{{ old('portada_subtitulo', $c['portada']['subtitulo']) }}"></div>
        </div>

        <div style="font-weight:700;margin:16px 0 8px;">Después del pedido</div>
        <label class="lbl" for="mensaje_confirmacion">Mensaje de confirmación</label>
        <textarea class="inp" id="mensaje_confirmacion" name="mensaje_confirmacion" rows="2" maxlength="300">{{ old('mensaje_confirmacion', $c['pedido']['mensaje_confirmacion']) }}</textarea>

        <div style="font-weight:700;margin:16px 0 8px;">Pie de página</div>
        <div class="grid-2">
            <div><label class="lbl" for="pie_texto">Texto</label><input class="inp" id="pie_texto" name="pie_texto" maxlength="200" value="{{ old('pie_texto', $c['pie']['texto']) }}"></div>
            <div><label class="lbl" for="pie_email">Correo de contacto</label><input class="inp" id="pie_email" name="pie_email" type="email" maxlength="150" value="{{ old('pie_email', $c['pie']['email']) }}"></div>
        </div>

        <div style="font-weight:700;margin:16px 0 8px;">Posicionamiento (SEO)</div>
        <div class="grid-2">
            <div><label class="lbl" for="seo_titulo">Título en Google (máx. 60)</label><input class="inp" id="seo_titulo" name="seo_titulo" maxlength="60" value="{{ old('seo_titulo', $c['seo']['titulo']) }}"></div>
            <div><label class="lbl" for="seo_imagen">Imagen para compartir (https)</label><input class="inp" id="seo_imagen" name="seo_imagen" type="url" maxlength="500" value="{{ old('seo_imagen', $c['seo']['imagen']) }}"></div>
            <div style="grid-column:1 / -1;"><label class="lbl" for="seo_descripcion">Descripción (máx. 160)</label><textarea class="inp" id="seo_descripcion" name="seo_descripcion" rows="2" maxlength="160">{{ old('seo_descripcion', $c['seo']['descripcion']) }}</textarea></div>
        </div>

        <button class="btn btn-p" type="submit" style="margin-top:14px;">Guardar contenido</button>
    </form>
</div>
@endsection
