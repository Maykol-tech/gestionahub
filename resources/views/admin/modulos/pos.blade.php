@extends('layouts.app')
@section('titulo', 'Punto de Venta')
@section('subtitulo', 'Cobra rápido')

@section('contenido')
<div x-data="posApp()" class="flex flex-col lg:flex-row gap-4 h-[calc(100vh-140px)]">
    
    <!-- KPIs arriba -->
    <div class="lg:hidden grid grid-cols-2 gap-2 mb-2">
        <div class="bg-white p-3 rounded-lg border"><p class="text-xs text-gray-500">Ventas Hoy</p><p class="text-lg font-bold">{{ $ventasHoy }}</p></div>
        <div class="bg-white p-3 rounded-lg border"><p class="text-xs text-gray-500">Cobrado</p><p class="text-lg font-bold text-green-600">S/ {{ number_format($cobradoHoy, 2) }}</p></div>
    </div>

    <!-- Columna Izquierda: Productos -->
    <div class="flex-1 flex flex-col gap-3 overflow-hidden">
        <div class="hidden lg:grid grid-cols-4 gap-3">
            <div class="bg-white p-3 rounded-lg border"><p class="text-xs text-gray-500">VENTAS HOY</p><p class="text-xl font-bold">{{ $ventasHoy }}</p></div>
            <div class="bg-white p-3 rounded-lg border"><p class="text-xs text-gray-500">COBRADO HOY</p><p class="text-xl font-bold text-green-600">S/ {{ number_format($cobradoHoy, 2) }}</p></div>
            <div class="bg-white p-3 rounded-lg border"><p class="text-xs text-gray-500">TICKET PROM.</p><p class="text-xl font-bold">S/ {{ number_format($ticketPromedio, 2) }}</p></div>
            <div class="bg-white p-3 rounded-lg border"><p class="text-xs text-gray-500">IGV DEL DÍA</p><p class="text-xl font-bold">S/ {{ number_format($igvHoy, 2) }}</p></div>
        </div>
        
        <div class="bg-white p-3 rounded-lg shadow-sm border">
            <div class="input-group">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" x-model="busqueda" @keydown.f2.prevent="$el.focus()" placeholder="Buscar producto (F2)..." class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#2B6EF6]">
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 content-start pb-4">
            <template x-for="prod in productosFiltrados" :key="prod.id">
                <button @click="agregarAlCarrito(prod)" class="bg-white p-3 rounded-lg border hover:border-[#2B6EF6] hover:shadow-md transition text-left flex flex-col justify-between h-32">
                    <div>
                        <h4 class="font-bold text-gray-800 text-sm leading-tight" x-text="prod.nombre"></h4>
                        <p class="text-xs text-gray-500 mt-1" x-text="'Stock: ' + prod.stock"></p>
                    </div>
                    <div class="font-bold text-[#2B6EF6] text-lg" x-text="'S/ ' + parseFloat(prod.precio).toFixed(2)"></div>
                </button>
            </template>
            <div x-show="productosFiltrados.length === 0" class="col-span-full text-center py-10 text-gray-500">
                No se encontraron productos. Verifica que tengas productos activos.
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Ticket -->
    <div class="w-full lg:w-[380px] bg-white rounded-lg shadow-lg border flex flex-col h-full overflow-hidden shrink-0">
        <div class="p-3 bg-[#F47B20] text-white font-bold">Venta Actual</div>
        
        <div class="flex-1 overflow-y-auto p-3 space-y-2">
            <template x-for="(item, index) in carrito" :key="index">
                <div class="bg-gray-50 p-2 rounded border flex justify-between items-center">
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-sm text-gray-800 truncate" x-text="item.nombre"></div>
                        <div class="text-xs text-gray-500" x-text="'S/ ' + item.precio.toFixed(2) + ' × ' + item.cantidad"></div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="item.cantidad > 1 ? item.cantidad-- : carrito.splice(index, 1)" class="w-6 h-6 bg-white border rounded hover:bg-gray-100">−</button>
                        <span class="font-bold w-5 text-center text-sm" x-text="item.cantidad"></span>
                        <button @click="item.cantidad++" class="w-6 h-6 bg-white border rounded hover:bg-gray-100">+</button>
                    </div>
                </div>
            </template>
            <div x-show="carrito.length === 0" class="text-center py-10 text-gray-400 text-sm">Agrega productos</div>
        </div>

        <div class="p-3 border-t bg-white">
            <div class="flex justify-between text-sm text-gray-600 mb-1"><span>Subtotal</span><span x-text="'S/ ' + subtotal.toFixed(2)"></span></div>
            <div class="flex justify-between text-sm text-gray-600 mb-2"><span>IGV (18%)</span><span x-text="'S/ ' + igv.toFixed(2)"></span></div>
            <div class="flex justify-between text-xl font-bold text-gray-800 mb-3"><span>Total</span><span x-text="'S/ ' + total.toFixed(2)"></span></div>
            
            <button @click="abrirPago()" :disabled="carrito.length === 0" class="w-full bg-[#2B6EF6] hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 rounded-lg font-bold transition flex justify-center items-center gap-2">
                <span x-text="'Cobrar S/ ' + total.toFixed(2)"></span>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded">F9</span>
            </button>
        </div>
    </div>

    <!-- MODAL PAGO -->
    <div x-show="modalPago" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Completar Pago</h3>
            <p class="text-gray-500 mb-4">Total: S/ <span x-text="total.toFixed(2)"></span></p>
            
            <div class="grid grid-cols-2 gap-2 mb-4">
                <template x-for="m in ['efectivo','tarjeta','yape','plin']" :key="m">
                    <button @click="metodo = m" :class="metodo === m ? 'border-[#2B6EF6] bg-blue-50 text-[#2B6EF6]' : 'border-gray-200'" class="border-2 p-2 rounded-lg font-semibold capitalize">
                        <span x-text="m"></span>
                    </button>
                </template>
            </div>

            <div class="flex justify-end gap-2">
                <button @click="modalPago = false" class="px-4 py-2 border rounded-lg">Cancelar</button>
                <button @click="procesarPago()" :disabled="procesando" class="bg-[#2B6EF6] text-white px-6 py-2 rounded-lg font-bold disabled:opacity-50">
                    <span x-show="!procesando">Confirmar</span>
                    <span x-show="procesando">Procesando...</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function posApp() {
        return {
            busqueda: '',
            productos: @json($productos),
            carrito: [],
            modalPago: false,
            metodo: 'efectivo',
            procesando: false,
            
            get productosFiltrados() {
                if (!this.busqueda) return this.productos;
                const t = this.busqueda.toLowerCase();
                return this.productos.filter(p => p.nombre.toLowerCase().includes(t) || (p.sku && p.sku.toLowerCase().includes(t)));
            },
            get subtotal() { return this.carrito.reduce((s, i) => s + (i.precio * i.cantidad), 0); },
            get igv() { return this.subtotal * 0.18; },
            get total() { return this.subtotal; },

            agregarAlCarrito(p) {
                const item = this.carrito.find(i => i.id === p.id);
                if (item) item.cantidad++;
                else this.carrito.push({ id: p.id, nombre: p.nombre, precio: parseFloat(p.precio), cantidad: 1 });
            },
            abrirPago() { if (this.carrito.length > 0) this.modalPago = true; },
            async procesarPago() {
                this.procesando = true;
                try {
                    const res = await gh.api('/admin/pos/cobrar', {
                        method: 'POST',
                        body: { total: this.total, metodo_pago: this.metodo, items: this.carrito }
                    });
                    if (res.ok) {
                        alert('Venta ' + res.data.numero + ' registrada');
                        this.carrito = [];
                        this.modalPago = false;
                        window.location.reload();
                    } else {
                        alert('Error: ' + (res.data.message || 'No se pudo cobrar'));
                    }
                } finally {
                    this.procesando = false;
                }
            }
        }
    }
    // F9 cobrar
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F9') document.querySelector('[\\@click="abrirPago()"]')?.click();
    });
</script>
@endpush