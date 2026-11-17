@props(['f', 'i' => 0])
{{-- GEST-89: pregunta frecuente desplegable --}}
<details class="rv" style="--d:{{ $i * 60 }}ms" @if ($i === 0) open @endif>
    <summary>{{ $f['pregunta'] }}</summary>
    <p>{{ $f['respuesta'] }}</p>
</details>
