<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestionaHub · Gestiona tu negocio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1A2332; }
        .btn-cta { background: #F47B20; } .btn-cta:hover { background: #d96a10; }
        .btn-outline { border: 1.5px solid #2B6EF6; color: #2B6EF6; }
        .btn-outline:hover { background: #2B6EF6; color: white; }
    </style>
</head>
<body x-data="landingApp()">

<!-- NAVBAR -->
<nav class="bg-white border-b border-gray-100 sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-[#2B6EF6] text-white flex items-center justify-center font-bold text-sm">GH</div>
            <span class="font-bold text-lg">GestionaHub</span>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin" class="btn-outline px-4 py-2 rounded-lg text-sm font-semibold">Ingresar</a>
        </div>
    </div>
</nav>

<!-- 1. HERO & LEADS (PRIMER PASO) -->
<section class="py-20 px-4 bg-[#F5F8FF]">
    <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-12 items-center">
        <div>
            <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">Gestiona tu negocio en un solo lugar</h1>
            <p class="text-lg text-gray-600 mb-6">Déjanos tus datos y un asesor te contactará para configurar tu prueba gratis de 14 días.</p>
            
            <!-- FORMULARIO DE CAPTURA DE LEADS -->
            <form @submit.prevent="enviarLead($event)" class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100">
                <div class="grid gap-3">
                    <input type="text" name="nombre" placeholder="Tu nombre" required class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-[#2B6EF6]">
                    <input type="tel" name="telefono" placeholder="Tu WhatsApp (9 dígitos)" required pattern="9[0-9]{8}" class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-[#2B6EF6]">
                    <input type="text" name="rubro" placeholder="Tu rubro (restaurante, tienda...)" class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-[#2B6EF6]">
                    <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off"> <!-- Honeypot -->
                    <button type="submit" class="btn-cta text-white w-full py-3 rounded-lg font-bold text-lg">Solicitar Prueba Gratis</button>
                </div>
                <p x-show="mensajeExito" x-text="mensajeExito" class="mt-3 text-sm text-green-600 font-semibold text-center"></p>
            </form>
        </div>
        <div>
            <div class="bg-white rounded-2xl shadow-2xl p-2 border border-gray-100">
                <div class="bg-gray-800 rounded-t-lg p-2 flex gap-1">
                    <div class="w-2 h-2 rounded-full bg-red-400"></div>
                    <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                    <div class="w-2 h-2 rounded-full bg-green-400"></div>
                </div>
                <div class="p-6 bg-gray-50 rounded-b-lg h-64 flex flex-col items-center justify-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2B6EF6" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <p class="text-sm mt-3">Panel de GestionaHub</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. PRECIOS CON TOGGLE (SEGUNDO PASO) -->
<section id="precios" class="py-20 px-4 bg-white">
    <div class="max-w-6xl mx-auto text-center">
        <h2 class="text-4xl font-extrabold mb-4">Elige el plan ideal para ti</h2>
        <p class="text-gray-600 mb-8">Todos los planes incluyen 14 días de prueba sin tarjeta.</p>
        
        <!-- TOGGLE ANUAL / MENSUAL -->
        <div class="flex justify-center items-center gap-3 mb-12 bg-gray-50 p-4 rounded-full w-fit mx-auto border border-gray-200">
            <span :class="!anual ? 'font-bold text-[#2B6EF6]' : 'text-gray-400'" class="text-lg">Mensual</span>
            <button @click="anual = !anual" type="button" class="w-14 h-8 rounded-full bg-gray-300 relative transition-colors duration-300" :class="anual ? 'bg-[#2B6EF6]' : ''">
                <div class="w-6 h-6 rounded-full bg-white absolute top-1 left-1 transition-transform duration-300 shadow" :class="anual ? 'translate-x-6' : ''"></div>
            </button>
            <span :class="anual ? 'font-bold text-[#2B6EF6]' : 'text-gray-400'" class="text-lg">Anual</span>
            <span class="bg-orange-100 text-orange-600 text-xs font-bold px-2 py-1 rounded-full">Ahorra 30%</span>
        </div>

        <!-- TARJETAS DE PRECIOS -->
        <div class="grid md:grid-cols-3 gap-6">
            @forelse($planes as $i => $p)
            <div class="p-8 rounded-2xl {{ $p->destacado ? 'border-2 border-[#2B6EF6] shadow-xl relative' : 'border border-gray-200 shadow-sm' }}">
                @if($p->destacado)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#2B6EF6] text-white text-xs font-bold px-4 py-1 rounded-full">Más popular</div>
                @endif
                <h3 class="text-xl font-bold mb-2">{{ $p->nombre }}</h3>
                
                <!-- LÓGICA DEL TOGGLE AQUÍ -->
                <div class="text-4xl font-extrabold mb-4 text-[#2B6EF6]">
                    S/ <span x-text="anual ? '{{ (int)$p->precio_anual }}' : '{{ (int)$p->precio_mensual }}'">{{ (int)$p->precio_mensual }}</span>
                    <span class="text-base text-gray-400 font-normal">/mes</span>
                </div>
                @if($p->precio_anual)
                <p x-show="anual" class="text-sm text-green-600 font-semibold mb-4">Facturado anualmente (S/ {{ (int)$p->precio_anual * 12 }}/año)</p>
                @endif
                
                <ul class="text-left text-sm space-y-3 mb-8">
                    @foreach(json_decode($p->features_json ?? '[]', true) as $feat)
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3" style="flex-shrink:0;margin-top:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span class="text-gray-700">{{ $feat }}</span>
                    </li>
                    @endforeach
                </ul>
                <a href="/admin" class="{{ $p->destacado ? 'btn-cta text-white' : 'btn-outline' }} block w-full py-3 rounded-lg font-bold text-center">Empezar ahora</a>
            </div>
            @empty
            <div class="col-span-3 text-gray-500">Los planes se están cargando...</div>
            @endforelse
        </div>
    </div>
</section>

<!-- 3. FUNCIONES (PRUEBA SOCIAL) -->
<section class="py-20 px-4 bg-gray-50">
    <div class="max-w-6xl mx-auto text-center">
        <h2 class="text-3xl font-extrabold mb-12">Todo lo que necesitas para crecer</h2>
        <div class="grid md:grid-cols-3 gap-6">
            @php
            $funciones = [
                ['clipboard', 'Pedidos y Kanban', 'Gestiona pedidos con arrastrar y soltar.'],
                ['card', 'POS y Caja', 'Cobra rápido con Yape, Plin y tarjeta.'],
                ['receipt', 'Facturación SUNAT', 'Boletas y facturas electrónicas.'],
                ['shopping', 'Tienda Online', 'Catálogo público con carrito.'],
                ['message', 'WhatsApp Bot', 'Atención automática 24/7.'],
                ['chart', 'Reportes', 'KPIs y análisis en tiempo real.'],
            ];
            $svg = [
                'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>',
                'card' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line>',
                'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path><path d="M12 17.5v-11"></path>',
                'shopping' => '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>',
                'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
                'chart' => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
            ];
            @endphp
            @foreach($funciones as $f)
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-left">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-[#2B6EF6] flex items-center justify-center mb-4">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $svg[$f[0]] !!}</svg>
                </div>
                <h3 class="font-bold mb-2">{{ $f[1] }}</h3>
                <p class="text-sm text-gray-600">{{ $f[2] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="bg-gray-900 text-gray-400 py-12 px-4 text-center">
    <p>© 2026 GestionaHub. Todos los derechos reservados.</p>
    <p class="mt-2 text-sm"><a href="/reclamaciones" class="hover:text-white">Libro de Reclamaciones</a></p>
</footer>

<!-- CHATBOT (Solo aparece si el usuario hace clic) -->
<div class="fixed bottom-6 right-6 z-50">
    <div x-show="chatAbierto" class="bg-white rounded-2xl shadow-2xl w-80 mb-4 overflow-hidden border" style="display:none">
        <div class="bg-[#2B6EF6] text-white p-4 flex justify-between items-center">
            <div class="font-bold text-sm">Asistente GestionaHub</div>
            <button @click="chatAbierto = false" class="text-white text-xl">&times;</button>
        </div>
        <div class="p-4 h-48 bg-gray-50 text-sm">
            Hola, ¿en qué podemos ayudarte?
        </div>
        <form @submit.prevent="enviarChat($event)" class="p-3 border-t bg-white">
            <input type="text" name="nombre" placeholder="Tu nombre" required class="w-full px-3 py-2 border rounded-lg text-sm mb-2">
            <input type="tel" name="telefono" placeholder="WhatsApp" required pattern="9[0-9]{8}" class="w-full px-3 py-2 border rounded-lg text-sm mb-2">
            <button type="submit" class="btn-cta text-white w-full py-2 rounded-lg text-sm font-bold">Enviar</button>
        </form>
    </div>
    <button @click="chatAbierto = !chatAbierto" class="btn-cta text-white w-16 h-16 rounded-full shadow-2xl flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    </button>
</div>

<script>
function landingApp() {
    return {
        anual: false, // Estado inicial del toggle
        chatAbierto: false,
        mensajeExito: '',
        
        async enviarLead(e) {
            const data = Object.fromEntries(new FormData(e.target));
            data.origen = 'formulario_hero';
            try {
                const res = await fetch('/landing/contacto', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                    body: JSON.stringify(data)
                });
                this.mensajeExito = '¡Listo! Te contactaremos por WhatsApp.';
                e.target.reset();
            } catch(err) { this.mensajeExito = 'Error al enviar'; }
        },
        async enviarChat(e) {
            const data = Object.fromEntries(new FormData(e.target));
            data.origen = 'chatbot';
            try {
                await fetch('/landing/contacto', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                    body: JSON.stringify(data)
                });
                e.target.reset();
                this.chatAbierto = false;
            } catch(err) {}
        }
    }
}
</script>

</body>
</html>