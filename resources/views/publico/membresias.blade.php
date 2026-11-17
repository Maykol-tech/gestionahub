@php
    $simbolo = $config['moneda']['simbolo'] ?? 'S/';
    $elegido = (int) old('plan_id', request('plan', $planes->count() === 1 ? $planes->first()->id : 0));
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $color }}">
    <title>Planes · {{ $config['negocio']['nombre'] }}</title>
    @include('publico._membresia_estilos')
</head>
<body>
<div class="wrap">
    @include('publico._membresia_negocio', ['leyenda' => 'Planes y membresías'])

    <h1>Elige tu plan</h1>
    <p class="sub">Te suscribes una vez y pagas cada periodo desde tu enlace personal, con Yape, Plin o tarjeta. Te avisamos por WhatsApp cuando toque renovar.</p>

    @if ($planes->isEmpty())
        <div class="card"><p class="sub" style="margin:0;">Este negocio todavía no tiene planes disponibles.</p></div>
    @else
        @foreach ($planes as $p)
            <div class="card {{ $elegido === $p->id ? 'elegido' : '' }}" id="plan-{{ $p->id }}">
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                    <div>
                        <div style="font-weight:800;font-size:17px;">{{ $p->nombre }}</div>
                        @if ($p->descripcion)<div style="font-size:13px;color:var(--fg2);margin-top:2px;">{{ $p->descripcion }}</div>@endif
                    </div>
                    <div class="precio" style="text-align:right;">{{ $simbolo }} {{ number_format($p->precio, 2) }}<br><small>{{ strtolower($periodos[$p->periodicidad] ?? $p->periodicidad) }}</small></div>
                </div>
                @if (! empty($p->beneficios))
                    <ul class="ben">@foreach ((array) $p->beneficios as $b)<li>{{ $b }}</li>@endforeach</ul>
                @endif
                <a class="btn {{ $elegido === $p->id ? '' : 'sec' }}" href="?plan={{ $p->id }}#suscribirme">{{ $elegido === $p->id ? 'Plan elegido ✓' : 'Elegir este plan' }}</a>
            </div>
        @endforeach

        <form class="card" id="suscribirme" method="POST" action="{{ route('membresia.suscribirse', $config['negocio']['slug']) }}">
            @csrf
            <div class="ttl">Tus datos</div>
            <label for="plan_id">Plan</label>
            <select class="inp" name="plan_id" id="plan_id" required>
                <option value="">Elige un plan</option>
                @foreach ($planes as $p)
                    <option value="{{ $p->id }}" @selected($elegido === $p->id)>{{ $p->nombre }} · {{ $simbolo }} {{ number_format($p->precio, 2) }} {{ strtolower($periodos[$p->periodicidad] ?? '') }}</option>
                @endforeach
            </select>
            @error('plan_id')<div class="err">{{ $message }}</div>@enderror

            <label for="nombre">Nombre y apellido</label>
            <input class="inp" name="nombre" id="nombre" value="{{ old('nombre') }}" maxlength="120" required autocomplete="name">
            @error('nombre')<div class="err">{{ $message }}</div>@enderror

            <label for="telefono">Celular (WhatsApp)</label>
            <input class="inp" name="telefono" id="telefono" value="{{ old('telefono') }}" inputmode="numeric" maxlength="9" placeholder="9XXXXXXXX" required autocomplete="tel">
            @error('telefono')<div class="err">{{ $message }}</div>@enderror

            <label for="email">Correo (opcional)</label>
            <input class="inp" type="email" name="email" id="email" value="{{ old('email') }}" maxlength="120" autocomplete="email">
            @error('email')<div class="err">{{ $message }}</div>@enderror

            <button class="btn" type="submit">Suscribirme y pagar</button>
            <p class="pie" style="margin-top:10px;">En el siguiente paso eliges cómo pagar. Tu suscripción se activa cuando el negocio confirma tu pago.</p>
        </form>
    @endif

    <div class="pie">{{ $config['negocio']['nombre'] }}@if ($config['negocio']['direccion']) · {{ $config['negocio']['direccion'] }}@endif</div>
</div>
</body>
</html>
