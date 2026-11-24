@props(['modulo', 'delay' => '.15s'])
@php $ia = \App\Support\Recomendaciones::para($modulo); @endphp
@if ($ia)
<div class="card fade-in ia-box" data-ia="{{ $modulo }}" style="background:#F0F5FF;border:1px solid #C7D9F8;border-left:4px solid var(--accent);border-radius:12px;padding:14px 16px;margin-bottom:16px;animation-delay:{{ $delay }}">
    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--accent);display:flex;align-items:center;gap:6px;margin-bottom:6px;">
        <x-icon name="sparkles" size="13" /> {{ $ia['titulo'] }}
    </div>
    <div style="font-size:15px;font-weight:700;color:var(--fg);line-height:1.4;">{{ $ia['frase'] }}</div>
    @if (! empty($ia['puntos']))
        <ul style="list-style:none;margin:8px 0 0;padding:0;display:grid;gap:4px;">
            @foreach ($ia['puntos'] as $p)
                <li style="font-size:13px;color:var(--fg2);display:flex;gap:6px;line-height:1.45;"><span style="color:var(--accent);font-weight:700;">→</span><span>{{ $p }}</span></li>
            @endforeach
        </ul>
    @endif
</div>
@endif
