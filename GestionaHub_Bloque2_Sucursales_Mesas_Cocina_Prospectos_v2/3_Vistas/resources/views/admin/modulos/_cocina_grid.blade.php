<div class="cocina-grid">
    @forelse ($comandas as $c)
        <div class="panel comanda fade-in">
            <div class="comanda-hd">
                <strong>{{ $c->mesa->nombre ?? 'Mesa' }}</strong>
                <span class="chip-estado {{ $c->estado }}">{{ str_replace('_', ' ', ucfirst($c->estado)) }}</span>
            </div>

            @foreach ($c->items as $linea)
                <div class="linea"><span>{{ $linea['cantidad'] }} × {{ $linea['nombre'] }}</span></div>
            @endforeach

            @if ($c->notas)
                <div style="font-size:12px;color:var(--fg3);margin-top:6px;">Nota: {{ $c->notas }}</div>
            @endif

            <div style="display:flex;gap:6px;margin-top:12px;flex-wrap:wrap;">
                @if ($c->estado === 'pendiente')
                    <form method="POST" action="{{ route('admin.cocina.estado', $c->id) }}">@csrf <input type="hidden" name="estado" value="en_preparacion"><button class="btn btn-p" type="submit">Preparar</button></form>
                @elseif ($c->estado === 'en_preparacion')
                    <form method="POST" action="{{ route('admin.cocina.estado', $c->id) }}">@csrf <input type="hidden" name="estado" value="lista"><button class="btn btn-p" type="submit">Lista</button></form>
                @elseif ($c->estado === 'lista')
                    <form method="POST" action="{{ route('admin.cocina.estado', $c->id) }}">@csrf <input type="hidden" name="estado" value="entregada"><button class="btn btn-p" type="submit">Entregada</button></form>
                @endif
                @if (in_array($c->estado, ['pendiente', 'en_preparacion']))
                    <form method="POST" action="{{ route('admin.cocina.estado', $c->id) }}">@csrf <input type="hidden" name="estado" value="cancelada"><button class="btn btn-g" type="submit">Cancelar</button></form>
                @endif
            </div>
        </div>
    @empty
        <div class="panel empty" style="grid-column:1 / -1;">
            <div style="font-weight:600;color:var(--fg2);">No hay comandas en cocina</div>
            <div style="font-size:13px;color:var(--fg3);">Aparecerán cuando un cliente pida desde el QR de su mesa.</div>
        </div>
    @endforelse
</div>
</div>
