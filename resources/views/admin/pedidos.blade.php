@extends('layouts.app')

@section('titulo', 'Pedidos')
@section('subtitulo', 'Gestiona el flujo de tus operaciones')

@section('acciones')
    <button onclick="document.getElementById('modalNuevoPedido').style.display='flex'" class="bg-[#F47B20] hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Nuevo pedido
    </button>
@endsection

@section('contenido')
<div class="flex flex-col h-full">
    
    <!-- Barra de Filtros -->
    <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-200 mb-6 flex justify-between items-center">
        <div class="flex gap-2">
            <input type="text" placeholder="Buscar pedido o cliente..." class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm w-64 focus:outline-none focus:border-[#2B6EF6]">
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none">
                <option>Todos los canales</option>
                <option>POS</option>
                <option>WhatsApp</option>
                <option>Web</option>
            </select>
        </div>
        <div class="flex gap-1 bg-gray-100 p-1 rounded-lg">
            <button class="px-3 py-1 bg-white shadow text-sm font-semibold rounded-md text-gray-800">Kanban</button>
            <button class="px-3 py-1 text-sm font-medium rounded-md text-gray-500 hover:text-gray-800">Lista</button>
        </div>
    </div>

    <!-- Tablero Kanban -->
    <div class="flex gap-6 overflow-x-auto pb-4 flex-1">
        @foreach(['nuevo' => 'Pendientes', 'preparando' => 'En Preparación', 'entregado' => 'Entregados', 'cancelado' => 'Cancelados'] as $estado => $titulo)
            @php
                $pedidosCol = $pedidos->where('estado', $estado);
                $colorHeader = match($estado) {
                    'nuevo' => 'bg-blue-500',
                    'preparando' => 'bg-yellow-500',
                    'entregado' => 'bg-green-500',
                    'cancelado' => 'bg-red-500',
                    default => 'bg-gray-500'
                };
            @endphp
            <div class="bg-[#F8FAFC] w-[320px] flex-shrink-0 rounded-xl border border-gray-200 flex flex-col max-h-full">
                <!-- Cabecera Columna -->
                <div class="p-3 border-b border-gray-200 flex justify-between items-center bg-white rounded-t-xl">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full {{ $colorHeader }}"></span>
                        <h3 class="font-bold text-gray-700 text-sm">{{ $titulo }}</h3>
                    </div>
                    <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs font-bold">{{ $pedidosCol->count() }}</span>
                </div>
                
                <!-- Contenedor de tarjetas (Sortable) -->
                <div class="p-3 overflow-y-auto flex-1 space-y-3 kanban-column" data-estado="{{ $estado }}">
                    @foreach($pedidosCol as $pedido)
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 cursor-grab active:cursor-grabbing hover:shadow-md transition-shadow pedido-card" data-id="{{ $pedido->id }}">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-sm text-[#2B6EF6]">{{ $pedido->numero }}</span>
                                <span class="text-xs text-gray-400 font-medium">{{ $pedido->created_at->format('H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-3 flex items-center gap-1">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                {{ $pedido->cliente->nombre ?? 'Cliente Mostrador' }}
                            </p>
                            <div class="flex justify-between items-center border-t border-gray-100 pt-2">
                                <span class="text-xs font-semibold text-gray-400">{{ ucfirst($pedido->canal) }}</span>
                                <span class="font-bold text-gray-800">S/ {{ number_format($pedido->total, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- MODAL NUEVO PEDIDO -->
<div id="modalNuevoPedido" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl relative">
        <button onclick="document.getElementById('modalNuevoPedido').style.display='none'" class="absolute top-4 right-4 text-gray-400 hover:text-gray-800 text-xl font-bold">&times;</button>
        <h3 class="text-xl font-bold text-gray-800 mb-4">Nuevo Pedido</h3>
        
        <form onsubmit="event.preventDefault(); alert('Pedido registrado correctamente (Simulación)'); document.getElementById('modalNuevoPedido').style.display='none';">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Cliente</label>
                <select class="w-full border-gray-300 rounded-lg shadow-sm p-2 border focus:border-[#2B6EF6]">
                    <option>Cliente Mostrador</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Producto</label>
                <select class="w-full border-gray-300 rounded-lg shadow-sm p-2 border focus:border-[#2B6EF6]">
                    @foreach($productos as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }} - S/ {{ number_format($p->precio, 2) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-4 mb-6">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Cantidad</label>
                    <input type="number" value="1" min="1" class="w-full border-gray-300 rounded-lg shadow-sm p-2 border">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Canal</label>
                    <select class="w-full border-gray-300 rounded-lg shadow-sm p-2 border">
                        <option>POS</option>
                        <option>WhatsApp</option>
                        <option>Web</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalNuevoPedido').style.display='none'" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="bg-[#F47B20] text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-600">Guardar Pedido</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<!-- Incluir SortableJS para Drag and Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Sortable en cada columna
        document.querySelectorAll('.kanban-column').forEach(column => {
            new Sortable(column, {
                group: 'kanban', // Permite mover entre columnas
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: function(evt) {
                    const pedidoId = evt.item.getAttribute('data-id');
                    const nuevoEstado = evt.to.getAttribute('data-estado');
                    
                    // Enviar actualización al servidor
                    fetch(`/admin/pedidos/${pedidoId}/estado`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ estado: nuevoEstado })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            // Opcional: Mostrar toast
                            console.log('Estado actualizado a: ' + nuevoEstado);
                        }
                    })
                    .catch(err => console.error('Error:', err));
                }
            });
        });
    });
</script>
@endpush