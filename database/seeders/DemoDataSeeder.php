<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\Proveedor;
use App\Models\Repartidor;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Datos de demostracion para presentar el sistema "lleno": 5 repartidores, 5 proveedores y 15 productos
 * con imagen de prueba, para cada negocio. Es idempotente (se puede correr varias veces) y no corre en produccion.
 *   docker compose exec app php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    /** [nombre, vehiculo, placa] */
    private const REPARTIDORES = [
        ['Luis Quispe', 'moto', 'ABC-123'],
        ['Rosa Huamán', 'moto', 'XYZ-456'],
        ['Carlos Ramos', 'bicicleta', null],
        ['Milagros Soto', 'auto', 'QWE-789'],
        ['Jorge Paredes', 'moto', 'RTY-321'],
    ];

    /** [nombre, ruc, contacto, telefono, email, notas] (datos ficticios) */
    private const PROVEEDORES = [
        ['Distribuidora Andina S.A.C.', '20500000011', 'María Torres', '987000011', 'ventas1@example.com', 'Bebidas y snacks. Entrega los martes y jueves.'],
        ['Abastos del Sur E.I.R.L.', '20500000022', 'Pedro Gutiérrez', '987000022', 'ventas2@example.com', 'Abarrotes a granel. Pago a 15 días.'],
        ['Comercial Los Andes S.A.C.', '20500000033', 'Lucía Huamán', '987000033', 'ventas3@example.com', 'Lácteos y conservas.'],
        ['Importaciones Pacífico S.A.', '20500000044', 'Renzo Vega', '987000044', 'ventas4@example.com', 'Café, chocolates y productos importados.'],
        ['Alimentos Santa Rosa S.R.L.', '20500000055', 'Carmen Flores', '987000055', 'ventas5@example.com', 'Fideos, arroz y aceites.'],
    ];

    /** Categorias: [nombre, color para la imagen] */
    private const CATEGORIAS = [
        'Bebidas' => '#2B6EF6',
        'Abarrotes' => '#F47B20',
        'Snacks' => '#16A34A',
    ];

    /** 15 productos: [nombre, categoria, precio, stock, stock_minimo]. La imagen es demo/productos/pNN.png (mismo orden). */
    private const PRODUCTOS = [
        ['Agua mineral 625 ml', 'Bebidas', 2.50, 120, 24],
        ['Gaseosa 500 ml', 'Bebidas', 3.00, 90, 20],
        ['Jugo de naranja 1 L', 'Bebidas', 6.50, 40, 10],
        ['Café instantáneo 100 g', 'Abarrotes', 18.90, 25, 8],
        ['Arroz extra 1 kg', 'Abarrotes', 4.80, 80, 20],
        ['Aceite vegetal 1 L', 'Abarrotes', 11.50, 35, 10],
        ['Azúcar rubia 1 kg', 'Abarrotes', 3.90, 60, 15],
        ['Fideos spaghetti 500 g', 'Abarrotes', 3.20, 70, 15],
        ['Leche evaporada 400 g', 'Abarrotes', 4.10, 4, 12],
        ['Galletas de soda', 'Snacks', 1.80, 150, 30],
        ['Papas fritas 150 g', 'Snacks', 4.50, 60, 15],
        ['Chocolate en barra 90 g', 'Snacks', 5.90, 0, 10],
        ['Maní salado 200 g', 'Snacks', 3.50, 45, 12],
        ['Chicles de menta', 'Snacks', 1.20, 200, 40],
        ['Cereal de avena 500 g', 'Abarrotes', 9.90, 22, 8],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoDataSeeder no se ejecuta en producción.');

            return;
        }

        Tenant::query()->get()->each(fn (Tenant $t) => $this->sembrar((int) $t->id));

        $this->command?->info('Datos de demostración listos: repartidores, proveedores, productos y mesas.');
    }

    private function sembrar(int $tenantId): void
    {
        $this->repartidores($tenantId);
        $this->proveedores($tenantId);
        $this->productos($tenantId);
        $this->mesas($tenantId);
    }

    /** Tres mesas de demo: cada una ya trae su QR en Mesas y QR. */
    private function mesas(int $tenantId): void
    {
        foreach (['Mesa 1' => 4, 'Mesa 2' => 4, 'Terraza 1' => 6] as $nombre => $capacidad) {
            Mesa::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenantId, 'nombre' => $nombre],
                ['capacidad' => $capacidad, 'estado' => 'libre', 'activo' => true]
            );
        }
    }

    private function repartidores(int $tenantId): void
    {
        foreach (self::REPARTIDORES as $i => [$nombre, $vehiculo, $placa]) {
            Repartidor::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'nombre' => $nombre],
                [
                    'telefono' => '9' . str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                    'vehiculo' => $vehiculo,
                    'placa' => $placa,
                    'activo' => true,
                    'disponible' => true,
                ]
            );
        }
    }

    private function proveedores(int $tenantId): void
    {
        foreach (self::PROVEEDORES as [$nombre, $ruc, $contacto, $telefono, $email, $notas]) {
            Proveedor::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'nombre' => $nombre],
                ['ruc' => $ruc, 'contacto' => $contacto, 'telefono' => $telefono, 'email' => $email, 'notas' => $notas, 'activo' => true]
            );
        }
    }

    private function productos(int $tenantId): void
    {
        $categorias = [];
        $orden = 0;
        foreach (array_keys(self::CATEGORIAS) as $nombre) {
            $categorias[$nombre] = Categoria::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenantId, 'nombre' => $nombre],
                ['slug' => Str::slug($nombre), 'orden' => $orden++, 'activo' => true]
            );
        }

        foreach (self::PRODUCTOS as $i => [$nombre, $categoria, $precio, $stock, $stockMinimo]) {
            $numero = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            $ruta = "demo/productos/p{$numero}.png";
            $url = Storage::disk('public')->url($ruta);

            $producto = Producto::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'sku' => 'DEMO-' . $numero],
                [
                    'categoria_id' => $categorias[$categoria]->id,
                    'nombre' => $nombre,
                    'slug' => Str::slug($nombre),
                    'descripcion' => 'Producto de demostración.',
                    'precio' => $precio,
                    'precio_oferta' => null,
                    'stock' => $stock,
                    'stock_minimo' => $stockMinimo,
                    'imagen_url' => $url,
                    'tiene_variantes' => false,
                    'activo' => true,
                ]
            );

            ProductoImagen::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenantId, 'producto_id' => $producto->id, 'ruta' => $ruta],
                ['variante_id' => null, 'alt' => $nombre, 'orden' => 0, 'es_principal' => true]
            );
        }
    }
}
