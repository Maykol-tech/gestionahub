@extends('layouts.app')

@section('titulo', 'Variantes y venta por kilo')
@section('subtitulo', 'Tamaños, sabores y productos que se venden por peso')

@push('styles')
    @include('admin.modulos._base')
    <style>
        .prod { padding:14px; margin-bottom:10px; }
        .grid-3 { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; align-items:end; }
        .chip { display:inline-flex; gap:6px; align-items:center; font-size:12px; padding:3px 10px; border-radius:99px; background:var(--bg); border:1px solid var(--border); margin:2px; }
        @media (max-width: 640px) { .grid-3 { grid-template-columns: 1fr; } }
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

    @foreach ($productos as $p)
        <div class="panel prod fade-in">
            <div style="font-weight:700;">{{ $p['nombre'] }} <span style="font-weight:400;color:var(--fg3);">Â· S/ {{ number_format($p['precio'], 2) }}</span></div>

            <div style="margin-top:6px;">
                @forelse ($p['variantes'] as $v)
                    <span class="chip">{{ $v->nombre }} Â· S/ {{ number_format($v->precio, 2) }} Â· stock {{ $v->stock }}
                        <form method="POST" action="{{ route('admin.variantes.eliminar', $v->id) }}" style="display:inline;">@csrf @method('DELETE')<button type="submit" style="background:none;border:0;color:#B91C1C;cursor:pointer;" aria-label="Eliminar variante">—</button></form>
                    </span>
                @empty
                    <span style="font-size:12px;color:var(--fg3);">Sin variantes</span>
                @endforelse
            </div>

            <form method="POST" action="{{ route('admin.variantes.guardar', $p['id']) }}" class="grid-3" style="margin-top:10px;">
                @csrf
                <div><label class="lbl">Variante (ej. Grande)</label><input class="inp" name="nombre" maxlength="60" required></div>
                <div><label class="lbl">Precio (S/)</label><input class="inp" name="precio" type="number" step="0.01" min="0" required></div>
                <div><label class="lbl">Stock</label><input class="inp" name="stock" type="number" min="0" value="0"></div>
                <div><button class="btn btn-g" type="submit">Agregar variante</button></div>
            </form>

            <form method="POST" action="{{ route('admin.variantes.kilo', $p['id']) }}" class="grid-3" style="margin-top:12px;border-top:1px dashed var(--border);padding-top:10px;">
                @csrf
                <div><label class="lbl">Precio por kilo (S/)</label><input class="inp" name="precio_kg" type="number" step="0.01" min="0.01" value="{{ $p['kilo']->precio_kg ?? '' }}" required></div>
                <div style="font-size:12px;color:var(--fg3);">{{ $p['kilo'] ? ($p['kilo']->activo ? 'Se vende por kilo' : 'Venta por kilo pausada') : 'No se vende por kilo' }}</div>
                <div><button class="btn btn-g" type="submit">Guardar precio por kilo</button></div>
            </form>
        </div>
    @endforeach

    <div class="panel fade-in" style="padding:18px;margin-top:12px;">
        <div style="font-weight:700;margin-bottom:8px;">Ajustar peso real al despachar</div>
        <form method="POST" action="{{ route('admin.variantes.peso', $pedidosKilo->first()->id ?? 0) }}" class="grid-3">
            @csrf
            <div>
                <label class="lbl">Pedido</label>
                <select class="inp" name="pedido_id_ref" onchange="this.form.action = this.form.action.replace(/\/\d+\/peso$/, '/' + this.value + '/peso')">
                    @foreach ($pedidosKilo as $ped)
                        <option value="{{ $ped->id }}">{{ $ped->numero }} Â· S/ {{ number_format($ped->total, 2) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="lbl">ID del producto por kilo</label><input class="inp" name="producto_id" type="number" min="1" required></div>
            <div><label class="lbl">Peso estimado (kg)</label><input class="inp" name="peso_estimado_kg" type="number" step="0.001" min="0.001" required></div>
            <div><label class="lbl">Peso real (kg)</label><input class="inp" name="peso_real_kg" type="number" step="0.001" min="0.001" required></div>
            <div><button class="btn btn-p" type="submit">Ajustar total</button></div>
        </form>
    </div>
</div>
@endsection
