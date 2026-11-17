{{-- GEST-87: valor del periodo anterior y variación con flecha de color --}}
@php $v = \App\Services\ReportesService::variacion((float) $actual, (float) $previo); @endphp
@if ($v === null)
    <div class="stat-ch neutral">Antes: {{ $moneda ? 'S/ ' . number_format($previo, 2) : number_format($previo) }} · sin base para comparar</div>
@else
    <div class="stat-ch {{ $v >= 0 ? 'up' : 'dn' }}" title="{{ $texto }}">{{ $v >= 0 ? '▲ +' : '▼ ' }}{{ rtrim(rtrim(number_format($v, 1), '0'), '.') }}% · antes {{ $moneda ? 'S/ ' . number_format($previo, 2) : number_format($previo) }}</div>
@endif
