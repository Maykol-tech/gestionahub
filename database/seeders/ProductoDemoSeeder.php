<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Categoria;

class ProductoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        Producto::where('tenant_id', $tenantId)->delete();

        $bebidas = Categoria::firstOrCreate(['tenant_id' => $tenantId, 'nombre' => 'Bebidas'], ['slug' => 'bebidas']);
        $comidas = Categoria::firstOrCreate(['tenant_id' => $tenantId, 'nombre' => 'Comidas'], ['slug' => 'comidas']);
        $postres = Categoria::firstOrCreate(['tenant_id' => $tenantId, 'nombre' => 'Postres'], ['slug' => 'postres']);
        $polos = Categoria::firstOrCreate(['tenant_id' => $tenantId, 'nombre' => 'Polos'], ['slug' => 'polos']);

        // Usamos las imágenes locales que SÍ se descargaron
        $productos = [
            ['nombre' => 'Inca Kola 500ml', 'precio' => 5.00, 'stock' => 50, 'categoria_id' => $bebidas->id, 'imagen_url' => '/images/inca-kola.jpg'],
            ['nombre' => 'Chicha Morada 1L', 'precio' => 12.00, 'stock' => 20, 'categoria_id' => $bebidas->id, 'imagen_url' => '/images/inca-kola.jpg'], // Usa la misma imagen
            ['nombre' => 'Lomo Saltado', 'precio' => 28.00, 'stock' => 15, 'categoria_id' => $comidas->id, 'imagen_url' => '/images/lomo-saltado.jpg'],
            ['nombre' => 'Pollo a la Brasa 1/4', 'precio' => 25.00, 'stock' => 10, 'categoria_id' => $comidas->id, 'imagen_url' => '/images/pollo-brasa.jpg'],
            ['nombre' => 'Arroz con Leche', 'precio' => 8.00, 'stock' => 3, 'categoria_id' => $postres->id, 'imagen_url' => '/images/arroz-leche.jpg'],
            ['nombre' => 'Torta de Chocolate', 'precio' => 15.00, 'stock' => 0, 'categoria_id' => $postres->id, 'imagen_url' => '/images/torta-chocolate.jpg'],
            ['nombre' => 'Polo Oversize Blanco', 'precio' => 45.00, 'stock' => 12, 'categoria_id' => $polos->id, 'imagen_url' => '/images/polo-blanco.jpg'],
            ['nombre' => 'Polo Estampado Negro', 'precio' => 55.00, 'stock' => 8, 'categoria_id' => $polos->id, 'imagen_url' => '/images/polo-negro.jpg'],
        ];

        foreach ($productos as $p) {
            Producto::create(array_merge($p, [
                'tenant_id' => $tenantId, 
                'slug' => \Illuminate\Support\Str::slug($p['nombre']) . '-' . time(), 
                'tiene_variantes' => false, 
                'activo' => true
            ]));
        }
    }
}