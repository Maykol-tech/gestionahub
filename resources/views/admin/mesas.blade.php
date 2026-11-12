@extends('layouts.app')
@section('titulo', 'Gestión de Mesas')
@section('subtitulo', 'Administra las mesas y genera códigos QR')

@section('acciones')
    <button type="button" class="btn btn-p" onclick="document.getElementById('modalNuevaMesa').style.display='flex'">
        + Nueva Mesa
    </button>
@endsection

@section('contenido')
<div class="vit-wrap">
    <div class="panel">
        <div class="panel-hd">
            <div>
                <div class="panel-ttl">Mesas del Local</div>
                <div class="panel-sub">Total: {{ $mesas->count() }} mesas</div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; padding: 20px;">
            @forelse($mesas as $mesa)
                <div class="card" style="padding: 16px; text-align: center; border: 1px solid var(--border); border-radius: 12px;">
                    <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">Mesa {{ $mesa->numero }}</h3>
                    
                    @if($mesa->estado === 'libre')
                        <span class="badge" style="background:#DCFCE7; color:#166534; margin-bottom: 12px; display:inline-block;">Libre</span>
                    @elseif($mesa->estado === 'ocupada')
                        <span class="badge" style="background:#FEE2E2; color:#991B1B; margin-bottom: 12px; display:inline-block;">Ocupada</span>
                    @else
                        <span class="badge" style="background:#FEF3C7; color:#92400E; margin-bottom: 12px; display:inline-block;">Por Cobrar</span>
                    @endif

                    <div style="display: flex; gap: 8px; justify-content: center; margin-top: 12px;">
                        <a href="{{ route('admin.mesas.qr', $mesa) }}" target="_blank" class="btn btn-g" style="font-size: 12px; padding: 4px 8px;" title="Ver QR">
                            QR
                        </a>
                        <form action="{{ route('admin.mesas.destroy', $mesa) }}" method="POST" onsubmit="return confirm('¿Eliminar mesa?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-g" style="font-size: 12px; padding: 4px 8px; color: red;">X</button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--fg3);">
                    No hay mesas registradas. Crea la primera.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal Nueva Mesa -->
<div id="modalNuevaMesa" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="panel" style="width:100%; max-width:400px; padding:24px; background:#fff; border-radius:12px;">
        <h3 style="font-size:20px; font-weight:700; margin-bottom:16px;">Agregar Mesa</h3>
        <form action="{{ route('admin.mesas.store') }}" method="POST">
            @csrf
            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" style="display:block; margin-bottom:8px; font-weight:600;">Número de Mesa</label>
                <input type="text" name="numero" class="form-input" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px;" required placeholder="Ej. 1, 2, Terraza 1...">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn-g" onclick="document.getElementById('modalNuevaMesa').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-p">Guardar Mesa</button>
            </div>
        </form>
    </div>
</div>
@endsection