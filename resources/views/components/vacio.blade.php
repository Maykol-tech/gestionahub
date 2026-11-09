@props(['icono' => 'inbox', 'titulo' => 'Aún no hay datos registrados', 'texto' => null])
{{-- Estado vacio formal: icono, titulo, texto y acciones opcionales --}}
<div {{ $attributes->merge(['class' => 'vacio']) }}>
    <div class="vacio-ico"><x-icon :name="$icono" size="22" /></div>
    <div class="vacio-ttl">{{ $titulo }}</div>
    @if ($texto)
        <div class="vacio-txt">{{ $texto }}</div>
    @endif
    @if (trim($slot) !== '')
        <div class="vacio-acc">{{ $slot }}</div>
    @endif
</div>
