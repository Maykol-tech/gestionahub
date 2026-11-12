<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanesSaaSSeeder extends Seeder {
    public function run(): void {
        DB::table('planes_saas')->truncate();
        DB::table('planes_saas')->insert([
            ['nombre' => 'Emprendedor', 'slug' => 'emprendedor', 'precio_mensual' => 99, 'precio_anual' => 69, 'destacado' => false, 'features_json' => json_encode(['CRM y Pedidos', 'POS Básico', '1 Sucursal']), 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Negocio', 'slug' => 'negocio', 'precio_mensual' => 199, 'precio_anual' => 139, 'destacado' => true, 'features_json' => json_encode(['Todo Emprendedor', 'Delivery y Sucursales', 'Mesas y Comandas']), 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Pro', 'slug' => 'pro', 'precio_mensual' => 349, 'precio_anual' => 244, 'destacado' => false, 'features_json' => json_encode(['Todo Negocio', 'Chatbot IA', 'Prospección IA', 'Sucursales ilimitadas']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}