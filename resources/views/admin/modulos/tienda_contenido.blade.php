@extends('layouts.app')

@section('titulo', 'Contenido de la tienda')
@section('subtitulo', 'Lo que ven tus clientes en tu tienda online')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns:1fr; } }
        .tabs-t { display:flex; gap:6px; overflow-x:auto; scrollbar-width:none; margin-bottom:12px; padding-bottom:2px; }
        .tabs-t::-webkit-scrollbar { display:none; }
        .tab-t { flex:none; border:1px solid var(--border); background:var(--card); border-radius:999px; padding:8px 14px; font-size:13px; font-weight:600; cursor:pointer; color:var(--fg2); transition:background .2s, color .2s, border-color .2s; }
        .tab-t.on { background:#1F2937; color:#fff; border-color:#1F2937; }
        .sec-t { padding:18px; animation:secIn .3s ease both; }
        @keyframes secIn { from { opacity:0; transform:translateY(6px); } }
        .sec-t h3 { margin:0 0 4px; font-size:15px; }
        .sec-t .ayuda { color:var(--fg3); font-size:13px; margin:0 0 14px; }
        .item-t { border:1px solid var(--border); border-radius:12px; padding:12px; margin-bottom:10px; position:relative; animation:secIn .25s ease both; }
        .item-t .quitar { position:absolute; top:8px; right:8px; }
        .ban-prev { width:100%; aspect-ratio:16/6; border-radius:10px; background:var(--bg) center/cover no-repeat; margin-bottom:10px; display:grid; place-items:center; color:var(--fg3); font-size:12px; }
        .chk-lista { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:8px; max-height:380px; overflow:auto; }
        .chk { display:flex; gap:8px; align-items:center; border:1px solid var(--border); border-radius:10px; padding:9px 10px; font-size:13px; cursor:pointer; transition:border-color .2s, background .2s; }
        .chk.on { border-color:var(--brand, #2B6EF6); background:color-mix(in srgb, var(--brand, #2B6EF6) 7%, transparent); }
        .pago-op { display:flex; gap:10px; align-items:center; }
        .barra-guardar { position:sticky; bottom:0; z-index:5; display:flex; gap:10px; align-items:center; justify-content:flex-end; padding:12px 0; background:linear-gradient(transparent, var(--bg) 35%); margin-top:6px; }
        .cup { display:flex; gap:10px; align-items:center; padding:10px 0; border-bottom:1px dashed var(--border); font-size:13px; }
        .cup code { font-weight:800; font-size:14px; }
        .cup.off { opacity:.5; }
        .cnt { font-size:11px; color:var(--fg3); text-align:right; }
        .mp-caja { margin-top:14px; padding:14px; border:1px solid #BAE6FD; background:#F0F9FF; border-radius:12px; }
    .mp-ok { font-size:12px; font-weight:700; padding:3px 9px; border-radius:99px; background:#DCFCE7; color:#15803D; }
</style>
@endpush

@section('contenido')
@php
    $paginas = $c['paginas'];
    $destacados = $productos->where('destacado', true)->pluck('id')->map(fn ($id) => (int) $id)->values();
@endphp
<div x-data="{
        tab: (location.hash || '#portada').slice(1),
        banners: @js(array_values($c['banners']['items'] ?? [])),
        faq: @js(array_values($paginas['faq'] ?? [])),
        dest: @js($destacados),
        buscar: '',
        ir(t) { this.tab = t; history.replaceState(null, '', '#' + t); },
        prevArchivo(e, b) { const f = e.target.files[0]; if (f) b._prev = URL.createObjectURL(f); }
    }" @hashchange.window="tab = (location.hash || '#portada').slice(1)">
    @if (session('ok'))
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#15803D;display:flex;gap:10px;align-items:center;">{{ session('ok') }}
            @if ($url)<a href="{{ $url }}" target="_blank" rel="noopener" style="margin-left:auto;">Ver mi tienda →</a>@endif
        </div>
    @endif
    @if ($errors->any())
        <div class="panel fade-in" style="padding:12px;margin-bottom:12px;color:#B91C1C;">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;flex-wrap:wrap;">
        <div class="tabs-t" role="tablist" style="flex:1;min-width:0;margin:0;">
            @foreach (['portada' => '🏠 Portada y banners', 'avisos' => '📣 Avisos y textos', 'destacados' => '⭐ Destacados', 'pagos' => '💳 Pagos y redes', 'paginas' => '📄 Páginas', 'cupones' => '🎟️ Cupones', 'seo' => '🔎 Google (SEO)'] as $k => $t)
                <button type="button" class="tab-t" role="tab" :class="{ on: tab === '{{ $k }}' }" @click="ir('{{ $k }}')">{{ $t }}</button>
            @endforeach
        </div>
        @if ($url)<a class="btn btn-g btn-sm" href="{{ $url }}" target="_blank" rel="noopener">Ver tienda</a>@endif
    </div>

    <form method="POST" action="{{ route('admin.tienda.contenido.guardar') }}" enctype="multipart/form-data" x-show="tab !== 'cupones'">
        @csrf
        <input type="hidden" name="banners" :value="JSON.stringify(banners.map(({ _prev, ...b }) => b))">
        <input type="hidden" name="faq" :value="JSON.stringify(faq)">

        {{-- PORTADA Y BANNERS --}}
        <div class="panel sec-t" x-show="tab === 'portada'">
            <h3>Portada</h3>
            <p class="ayuda">Si lo dejas vacío, usamos el nombre y la descripción de tu negocio.</p>
            <div class="grid-2">
                <div><label class="lbl" for="portada_titulo">Título</label><input class="inp" id="portada_titulo" name="portada_titulo" maxlength="120" value="{{ old('portada_titulo', $c['portada']['titulo']) }}" placeholder="{{ $negocio->nombre_negocio ?? '' }}"></div>
                <div><label class="lbl" for="portada_subtitulo">Subtítulo</label><input class="inp" id="portada_subtitulo" name="portada_subtitulo" maxlength="250" value="{{ old('portada_subtitulo', $c['portada']['subtitulo']) }}" placeholder="Ej.: Pollos a la brasa con delivery en 30 minutos"></div>
            </div>

            <h3 style="margin-top:20px;">Banners (carrusel)</h3>
            <p class="ayuda">Hasta 6 imágenes que rotan solas. Tamaño recomendado: 1600 × 600 px.</p>
            <template x-for="(b, k) in banners" :key="k">
                <div class="item-t">
                    <button type="button" class="btn btn-g btn-sm quitar" @click="banners.splice(k, 1)" aria-label="Quitar banner">✕</button>
                    <div class="ban-prev" :style="(b._prev || b.imagen) ? 'background-image:url(' + (b._prev || b.imagen) + ')' : ''"><span x-show="!b._prev && !b.imagen">Sin imagen</span></div>
                    <div class="grid-2">
                        <div><label class="lbl">Subir imagen</label><input class="inp" type="file" accept="image/*" :name="'banner_archivo[' + k + ']'" @change="prevArchivo($event, b)"></div>
                        <div><label class="lbl">O pega el enlace de una imagen</label><input class="inp" x-model.trim="b.imagen" maxlength="500" placeholder="https://…"></div>
                        <div><label class="lbl">Título (opcional)</label><input class="inp" x-model="b.titulo" maxlength="80" placeholder="Ej.: 2x1 los martes"></div>
                        <div><label class="lbl">Texto (opcional)</label><input class="inp" x-model="b.texto" maxlength="160"></div>
                        <div style="grid-column:1 / -1;"><label class="lbl">Al tocar, ir a (opcional)</label><input class="inp" x-model.trim="b.enlace" maxlength="300" placeholder="#productos o https://…"></div>
                    </div>
                </div>
            </template>
            <button type="button" class="btn btn-g" x-show="banners.length < 6" @click="banners.push({ imagen: '', titulo: '', texto: '', enlace: '' })">+ Agregar banner</button>
        </div>

        {{-- AVISOS Y TEXTOS --}}
        <div class="panel sec-t" x-show="tab === 'avisos'" x-cloak>
            <h3>Cinta de avisos</h3>
            <p class="ayuda">Se muestra arriba de tu tienda, en movimiento. Un aviso por línea (máximo 6).</p>
            <textarea class="inp" name="avisos" rows="3" maxlength="600" placeholder="Delivery gratis desde S/ 50&#10;Abrimos los domingos desde las 12 m.">{{ old('avisos', implode("\n", (array) ($c['avisos']['items'] ?? []))) }}</textarea>

            <h3 style="margin-top:18px;">Mensaje de bienvenida</h3>
            <textarea class="inp" name="bienvenida" rows="2" maxlength="400" placeholder="Ej.: Hacemos todo al momento con ingredientes frescos.">{{ old('bienvenida', $c['bienvenida']['texto'] ?? '') }}</textarea>

            <h3 style="margin-top:18px;">Después del pedido</h3>
            <textarea class="inp" name="mensaje_confirmacion" rows="2" maxlength="300">{{ old('mensaje_confirmacion', $c['pedido']['mensaje_confirmacion']) }}</textarea>

            <h3 style="margin-top:18px;">Pie de página</h3>
            <div class="grid-2">
                <div><label class="lbl" for="pie_texto">Texto</label><input class="inp" id="pie_texto" name="pie_texto" maxlength="200" value="{{ old('pie_texto', $c['pie']['texto']) }}"></div>
                <div><label class="lbl" for="pie_email">Correo de contacto</label><input class="inp" id="pie_email" name="pie_email" type="email" maxlength="150" value="{{ old('pie_email', $c['pie']['email']) }}"></div>
            </div>
        </div>

        {{-- DESTACADOS --}}
        <div class="panel sec-t" x-show="tab === 'destacados'" x-cloak>
            <input type="hidden" name="destacados_enviado" value="1">
            <h3>Productos destacados</h3>
            <p class="ayuda">Aparecen primero en tu tienda, en la sección “Destacados”. Elige hasta 8 para que luzcan mejor.</p>
            <input class="inp" x-model="buscar" placeholder="Buscar producto" style="margin-bottom:10px;max-width:320px;">
            <div class="chk-lista">
                @forelse ($productos as $p)
                    <label class="chk" :class="{ on: dest.includes({{ $p->id }}) }" x-show="!buscar || @js(mb_strtolower($p->nombre)).includes(buscar.toLowerCase())">
                        <input type="checkbox" name="destacados[]" value="{{ $p->id }}" x-model.number="dest"> <span style="flex:1;">{{ $p->nombre }}</span><span class="sub">S/ {{ number_format((float) $p->precio, 2) }}</span>
                    </label>
                @empty
                    <div class="sub">Aún no tienes productos activos. Agrégalos en <a href="{{ url('admin/productos') }}">Productos</a>.</div>
                @endforelse
            </div>
            <div class="sub" style="margin-top:8px;" x-text="dest.length + ' destacados'"></div>
        </div>

        {{-- PAGOS Y REDES --}}
        <div class="panel sec-t" x-show="tab === 'pagos'" x-cloak>
            <h3>¿Cómo te pueden pagar?</h3>
            <p class="ayuda">Tus clientes eligen uno de estos al hacer su pedido.</p>
            @php $pagosAct = old('tienda_pagos', $a['tienda_pagos']); @endphp
            <div class="chk-lista" style="max-height:none;">
                @foreach ($metodos as $k => $m)
                    <label class="chk pago-op" x-data="{ on: @js(in_array($k, (array) $pagosAct, true)) }" :class="{ on }">
                        <input type="checkbox" name="tienda_pagos[]" value="{{ $k }}" x-model="on"> <span style="font-size:18px;">{{ $m['icono'] }}</span> <span><b>{{ $m['nombre'] }}</b><br><span class="sub">{{ $m['detalle'] }}</span></span>
                    </label>
                @endforeach
            </div>
            <div class="mp-caja">
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <b>💳 Cobrar con tarjeta en línea (Mercado Pago)</b>
                    @if (\App\Support\MercadoPagoTienda::activo((int) $negocio->id))
                        <span class="mp-ok">Conectado{{ \App\Support\MercadoPagoTienda::esPrueba((string) \App\Support\MercadoPagoTienda::token((int) $negocio->id)) ? ' · modo prueba' : '' }}</span>
                    @endif
                </div>
                <p class="ayuda" style="margin:6px 0 10px;">Entra a mercadopago.com.pe → Tus integraciones → Credenciales y copia tu <b>Access Token</b> y tu <b>Public Key</b>. Con unos que empiezan en "TEST-" puedes probar sin cobrar de verdad. Luego marca "Tarjeta en línea" arriba.</p>
                <label class="lbl" for="mp_access_token">Access Token (secreto)</label>
                <input class="inp" type="password" id="mp_access_token" name="mp_access_token" autocomplete="off" placeholder="{{ \App\Support\MercadoPagoTienda::activo((int) $negocio->id) ? 'Guardado. Pega otro solo si quieres cambiarlo' : 'APP_USR-... o TEST-...' }}">
                <label class="lbl" for="mp_public_key" style="margin-top:10px;">Public Key</label>
                <input class="inp" id="mp_public_key" name="mp_public_key" autocomplete="off" maxlength="120" value="{{ old('mp_public_key', $a['mp_public_key'] ?? '') }}" placeholder="APP_USR-... o TEST-...">
                <p class="ayuda" style="margin:6px 0 0;">Con la Public Key, el cliente paga con tarjeta sin salir de tu tienda. Sin ella, el botón lo lleva a la página de Mercado Pago.</p>
                @if (\App\Support\MercadoPagoTienda::activo((int) $negocio->id))
                    <label class="sub" style="display:flex;gap:6px;align-items:center;margin-top:8px;"><input type="checkbox" name="quitar_mp" value="1"> Desconectar Mercado Pago</label>
                @endif
            </div>
            <div class="grid-2" style="margin-top:14px;">
                <div><label class="lbl" for="yape_numero">Número de Yape</label><input class="inp" id="yape_numero" name="yape_numero" inputmode="numeric" maxlength="9" value="{{ old('yape_numero', $negocio->yape_numero ?? '') }}" placeholder="9 dígitos"></div>
                <div><label class="lbl" for="plin_numero">Número de Plin</label><input class="inp" id="plin_numero" name="plin_numero" inputmode="numeric" maxlength="9" value="{{ old('plin_numero', $negocio->plin_numero ?? '') }}" placeholder="9 dígitos"></div>
                <div>
                    <label class="lbl" for="yape_qr">QR de Yape (imagen)</label><input class="inp" id="yape_qr" name="yape_qr" type="file" accept="image/*">
                    @if (! empty($negocio->yape_qr))
                        <div style="display:flex;gap:10px;align-items:center;margin-top:8px;"><img src="{{ $negocio->yape_qr }}" alt="QR actual" style="width:64px;height:64px;border-radius:8px;object-fit:cover;"><label class="sub"><input type="checkbox" name="quitar_qr" value="1"> Quitar QR</label></div>
                    @endif
                </div>
                <div><label class="lbl" for="tienda_moneda">Moneda</label>
                    <select class="inp" id="tienda_moneda" name="tienda_moneda">@foreach ($monedas as $k => $sim)<option value="{{ $k }}" @selected(old('tienda_moneda', $a['tienda_moneda']) === $k)>{{ $sim }} ({{ $k }})</option>@endforeach</select>
                </div>
            </div>

            <h3 style="margin-top:20px;">Opciones</h3>
            <label style="display:flex;gap:8px;align-items:center;font-size:14px;margin:6px 0;"><input type="hidden" name="tienda_programar" value="0"><input type="checkbox" name="tienda_programar" value="1" @checked(old('tienda_programar', $a['tienda_programar']))> Aceptar pedidos programados (también cuando estás cerrado)</label>
            <label style="display:flex;gap:8px;align-items:center;font-size:14px;margin:6px 0;"><input type="hidden" name="tienda_puntos" value="0"><input type="checkbox" name="tienda_puntos" value="1" @checked(old('tienda_puntos', $a['tienda_puntos']))> Permitir pagar con puntos de fidelización</label>
            <p class="sub" style="margin:4px 0 0;">Los horarios se configuran en <a href="{{ url('admin/horarios') }}">Horarios</a> y las formas de entrega en <a href="{{ url('admin/entregas') }}">Formas de entrega</a>.</p>

            <h3 style="margin-top:20px;">WhatsApp y redes</h3>
            <div class="grid-2">
                <div><label class="lbl" for="tienda_whatsapp">WhatsApp de la tienda</label><input class="inp" id="tienda_whatsapp" name="tienda_whatsapp" inputmode="tel" maxlength="20" value="{{ old('tienda_whatsapp', $a['tienda_whatsapp']) }}" placeholder="Si lo dejas vacío, usamos el teléfono del negocio"></div>
                <div><label class="lbl" for="tienda_facebook">Facebook</label><input class="inp" id="tienda_facebook" name="tienda_facebook" type="url" maxlength="200" value="{{ old('tienda_facebook', $a['tienda_facebook']) }}" placeholder="https://facebook.com/…"></div>
                <div><label class="lbl" for="tienda_instagram">Instagram</label><input class="inp" id="tienda_instagram" name="tienda_instagram" type="url" maxlength="200" value="{{ old('tienda_instagram', $a['tienda_instagram']) }}" placeholder="https://instagram.com/…"></div>
                <div><label class="lbl" for="tienda_tiktok">TikTok</label><input class="inp" id="tienda_tiktok" name="tienda_tiktok" type="url" maxlength="200" value="{{ old('tienda_tiktok', $a['tienda_tiktok']) }}" placeholder="https://tiktok.com/@…"></div>
            </div>
        </div>

        {{-- PÁGINAS --}}
        <div class="panel sec-t" x-show="tab === 'paginas'" x-cloak>
            <h3>Términos y condiciones</h3>
            <p class="ayuda">Si las dejas vacías, la página no aparece en tu tienda.</p>
            <textarea class="inp" name="terminos" rows="6" maxlength="20000">{{ old('terminos', $paginas['terminos'] ?? '') }}</textarea>
            <h3 style="margin-top:18px;">Políticas de cambios y devoluciones</h3>
            <textarea class="inp" name="politicas" rows="6" maxlength="20000">{{ old('politicas', $paginas['politicas'] ?? '') }}</textarea>

            <h3 style="margin-top:18px;">Preguntas frecuentes</h3>
            <template x-for="(f, k) in faq" :key="k">
                <div class="item-t">
                    <button type="button" class="btn btn-g btn-sm quitar" @click="faq.splice(k, 1)" aria-label="Quitar pregunta">✕</button>
                    <label class="lbl">Pregunta</label><input class="inp" x-model="f.pregunta" maxlength="200" placeholder="¿Hacen delivery a mi distrito?" style="width:calc(100% - 44px);">
                    <label class="lbl" style="margin-top:8px;">Respuesta</label><textarea class="inp" x-model="f.respuesta" rows="2" maxlength="1500"></textarea>
                </div>
            </template>
            <button type="button" class="btn btn-g" x-show="faq.length < 30" @click="faq.push({ pregunta: '', respuesta: '' })">+ Agregar pregunta</button>
        </div>

        {{-- SEO --}}
        <div class="panel sec-t" x-show="tab === 'seo'" x-cloak>
            <h3>Cómo aparece tu tienda en Google y al compartirla</h3>
            <p class="ayuda">Cada producto ya tiene su propio enlace, título y descripción. Tu mapa del sitio está en
                @if ($url)<a href="{{ $url }}/sitemap.xml" target="_blank" rel="noopener">{{ $url }}/sitemap.xml</a>@endif.</p>
            <div class="grid-2" x-data="{ t: @js(old('seo_titulo', $c['seo']['titulo'])), d: @js(old('seo_descripcion', $c['seo']['descripcion'])) }">
                <div><label class="lbl" for="seo_titulo">Título en Google</label><input class="inp" id="seo_titulo" name="seo_titulo" maxlength="60" x-model="t"><div class="cnt" x-text="t.length + ' / 60'"></div></div>
                <div><label class="lbl" for="seo_imagen">Imagen para compartir (https)</label><input class="inp" id="seo_imagen" name="seo_imagen" type="url" maxlength="500" value="{{ old('seo_imagen', $c['seo']['imagen']) }}"></div>
                <div style="grid-column:1 / -1;"><label class="lbl" for="seo_descripcion">Descripción</label><textarea class="inp" id="seo_descripcion" name="seo_descripcion" rows="2" maxlength="160" x-model="d"></textarea><div class="cnt" x-text="d.length + ' / 160'"></div></div>
            </div>
        </div>

        <div class="barra-guardar">
            <span class="sub">Los cambios se ven al instante en tu tienda.</span>
            <button class="btn btn-p" type="submit">Guardar tienda</button>
        </div>
    </form>

    {{-- CUPONES --}}
    <div x-show="tab === 'cupones'" x-cloak>
        <div class="panel sec-t" style="margin-bottom:12px;">
            <h3>Nuevo cupón</h3>
            <p class="ayuda">Tus clientes lo escriben en el paso de pago. Ejemplo: BIENVENIDO10 para 10% de descuento.</p>
            <form method="POST" action="{{ route('admin.tienda.cupones.guardar') }}" class="grid-2" x-data="{ tipo: @js(old('tipo', 'porcentaje')) }">
                @csrf
                <div><label class="lbl" for="codigo">Código</label><input class="inp" id="codigo" name="codigo" maxlength="40" required value="{{ old('codigo') }}" style="text-transform:uppercase;" placeholder="BIENVENIDO10"></div>
                <div><label class="lbl" for="tipo">Tipo</label><select class="inp" id="tipo" name="tipo" x-model="tipo"><option value="porcentaje">Porcentaje (%)</option><option value="monto">Monto fijo (S/)</option></select></div>
                <div><label class="lbl" for="valor" x-text="tipo === 'porcentaje' ? 'Descuento (%)' : 'Descuento (S/)'"></label><input class="inp" id="valor" name="valor" type="number" step="0.01" min="0.01" :max="tipo === 'porcentaje' ? 100 : 99999" required value="{{ old('valor') }}"></div>
                <div><label class="lbl" for="minimo">Compra mínima (S/)</label><input class="inp" id="minimo" name="minimo" type="number" step="0.01" min="0" value="{{ old('minimo', 0) }}"></div>
                <div><label class="lbl" for="usos_max">Máximo de usos</label><input class="inp" id="usos_max" name="usos_max" type="number" min="1" value="{{ old('usos_max') }}" placeholder="Sin límite"></div>
                <div><label class="lbl" for="vence">Vence</label><input class="inp" id="vence" name="vence" type="date" min="{{ now()->toDateString() }}" value="{{ old('vence') }}"></div>
                <div style="grid-column:1 / -1;"><button class="btn btn-p" type="submit">Crear cupón</button></div>
            </form>
        </div>
        <div class="panel sec-t">
            <h3>Tus cupones</h3>
            @forelse ($cupones as $cu)
                @php $vencido = $cu->vence && $cu->vence < now()->toDateString(); $agotado = $cu->usos_max && $cu->usos >= $cu->usos_max; @endphp
                <div class="cup {{ ! $cu->activo || $vencido || $agotado ? 'off' : '' }}">
                    <code>{{ $cu->codigo }}</code>
                    <span style="flex:1;">{{ $cu->tipo === 'porcentaje' ? rtrim(rtrim(number_format($cu->valor, 2), '0'), '.') . '%' : 'S/ ' . number_format($cu->valor, 2) }}
                        <span class="sub">{{ (float) $cu->minimo > 0 ? '· mínimo S/ ' . number_format($cu->minimo, 2) : '' }} · {{ $cu->usos }}{{ $cu->usos_max ? '/' . $cu->usos_max : '' }} usos{{ $cu->vence ? ' · vence ' . \Illuminate\Support\Carbon::parse($cu->vence)->format('d/m/Y') : '' }}{{ $vencido ? ' (vencido)' : '' }}{{ $agotado ? ' (agotado)' : '' }}</span></span>
                    <form method="POST" action="{{ route('admin.tienda.cupones.alternar', $cu->id) }}">@csrf <button class="btn btn-g btn-sm" type="submit">{{ $cu->activo ? 'Pausar' : 'Activar' }}</button></form>
                    <form method="POST" action="{{ route('admin.tienda.cupones.eliminar', $cu->id) }}" onsubmit="return confirm('¿Eliminar el cupón {{ $cu->codigo }}?')">@csrf @method('DELETE') <button class="btn btn-g btn-sm" type="submit" style="color:#B91C1C;">Eliminar</button></form>
                </div>
            @empty
                <div class="empty"><div style="font-size:30px;">🎟️</div><div class="sub">Aún no tienes cupones.</div></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
