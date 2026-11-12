<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar · GestionaHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-gradient-blue { background: linear-gradient(135deg, #2B6EF6 0%, #1E4FD1 100%); }
        .input-focus:focus { border-color: #2B6EF6; box-shadow: 0 0 0 3px rgba(43,110,246,.25); outline: none; }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-15px); } 100% { transform: translateY(0px); } }
        .floating-shape { animation: float 6s ease-in-out infinite; }
        .shake { animation: shake 0.5s; }
        @keyframes shake { 0% { transform: translateX(0); } 25% { transform: translateX(-5px); } 50% { transform: translateX(5px); } 75% { transform: translateX(-5px); } 100% { transform: translateX(0); } }
    </style>
</head>
<body class="h-screen flex bg-white overflow-hidden">

    <!-- Lado Izquierdo (45%) - Branding -->
    <div class="hidden lg:flex w-[45%] bg-gradient-blue text-white flex-col justify-center items-center relative overflow-hidden p-12">
        <div class="absolute top-10 left-10 w-40 h-40 bg-white/10 rounded-full floating-shape"></div>
        <div class="absolute bottom-20 right-10 w-64 h-64 bg-white/10 rounded-full floating-shape" style="animation-delay: 2s;"></div>
        
        <div class="relative z-10 max-w-md">
            <div class="flex items-center gap-3 mb-10">
                <span class="bg-white text-[#2B6EF6] p-2 rounded-lg text-2xl font-black">GH</span>
                <h1 class="text-3xl font-bold tracking-tight">GestionaHub</h1>
            </div>
            <h2 class="text-4xl font-extrabold leading-tight mb-6">Gestiona tu negocio en un solo lugar.</h2>
            <p class="text-blue-100 text-lg mb-10">El sistema integral para hacer crecer tu empresa.</p>
            
            <div class="space-y-6">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
</div>
                    <div>
                        <h4 class="font-bold">Pedidos y tienda online</h4>
                        <p class="text-sm text-blue-200">Vende sin límites 24/7.</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>
</div>
                    <div>
                        <h4 class="font-bold">Caja y facturación SUNAT</h4>
                        <p class="text-sm text-blue-200">Cumple con todas las normas.</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
</div>
                    <div>
                        <h4 class="font-bold">Clientes y fidelización</h4>
                        <p class="text-sm text-blue-200">Haz que vuelvan por más.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lado Derecho (55%) - Formulario -->
    <div class="w-full lg:w-[55%] flex items-center justify-center p-4 lg:p-12 bg-gray-50">
        <div class="w-full max-w-md bg-white p-8 lg:p-10 rounded-2xl shadow-xl border border-gray-100">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido de nuevo</h2>
                <p class="text-gray-500">Ingresa tus credenciales para acceder.</p>
            </div>

            @if(session('status'))
                <div class="bg-green-50 text-green-700 p-3 rounded-lg text-sm mb-4 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 text-red-700 p-3 rounded-lg text-sm mb-4 border border-red-200 shake">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Correo electrónico</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        </div>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus
                               class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl input-focus transition text-gray-800"
                               placeholder="admin@gestiouhub.pe">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <input type="password" name="password" id="password" autocomplete="current-password" required
                               class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl input-focus transition text-gray-800"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg id="eye-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-[#2B6EF6] rounded border-gray-300 focus:ring-[#2B6EF6]">
                        <span class="text-sm text-gray-600">Recordarme</span>
                    </label>
                    <a href="#" class="text-sm font-semibold text-[#2B6EF6] hover:underline">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" id="submit-btn" class="w-full bg-[#F47B20] hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition duration-300 shadow-lg flex justify-center items-center">
                    <span id="btn-text">Ingresar</span>
                    <svg id="btn-spinner" class="hidden animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </button>
            </form>
            
            <div class="mt-8 text-center text-xs text-gray-400">
                © 2026 GestionaHub. Todos los derechos reservados.
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('btn-text').innerText = 'Ingresando...';
            document.getElementById('btn-spinner').classList.remove('hidden');
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').classList.add('opacity-75', 'cursor-not-allowed');
        });
    </script>
</body>
</html>