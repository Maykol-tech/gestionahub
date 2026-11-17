@props(['id' => null, 'alt' => false, 'orden' => 0])
{{-- GEST-89: envoltorio de sección; el orden lo decide el superadmin (GEST-98) --}}
<section @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => $alt ? 'alt' : '']) }} style="order:{{ $orden }}">
    <div class="wrap">{{ $slot }}</div>
</section>
