<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro de Reclamaciones - GestionaHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 py-10">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Libro de Reclamaciones Virtual</h1>
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded mb-6">{{ session('success') }}</div>
        @endif
        <form action="{{ route('landing.reclamaciones.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm">Nombre Completo</label><input type="text" name="nombre" class="w-full border p-2 rounded" required></div>
                <div><label class="block text-sm">Email</label><input type="email" name="email" class="w-full border p-2 rounded" required></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm">Tipo Doc.</label>
                    <select name="tipo_documento" class="w-full border p-2 rounded" required>
                        <option value="DNI">DNI</option>
                        <option value="RUC">RUC</option>
                        <option value="CE">Carnet de Extranjería</option>
                    </select>
                </div>
                <div><label class="block text-sm">N° Documento</label><input type="text" name="numero_documento" class="w-full border p-2 rounded" required></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm">Teléfono</label><input type="tel" name="telefono" class="w-full border p-2 rounded" required></div>
                <div><label class="block text-sm">Dirección</label><input type="text" name="direccion" class="w-full border p-2 rounded" required></div>
            </div>
            <div>
                <label class="block text-sm">Tipo de Bien</label>
                <select name="tipo_bien" class="w-full border p-2 rounded" required>
                    <option value="Producto">Producto</option>
                    <option value="Servicio">Servicio</option>
                </select>
            </div>
            <div><label class="block text-sm">Detalle del Reclamo</label><textarea name="detalle" class="w-full border p-2 rounded" rows="3" required></textarea></div>
            <div><label class="block text-sm">Pedido del Cliente</label><textarea name="pedido" class="w-full border p-2 rounded" rows="2" required></textarea></div>
            <button type="submit" class="w-full bg-[#2B6EF6] text-white py-3 rounded font-bold">Registrar Reclamo</button>
        </form>
    </div>
</body>
</html>