<?php

use App\Models\FormaEntrega;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Negocios sin ninguna forma de entrega no podían recibir pedidos en su tienda
 * ("El negocio aún no configuró sus formas de entrega"). Les crea Delivery y Recojo en tienda,
 * que el administrador puede cambiar en Tienda pública → Entregas. No toca negocios que ya tienen alguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasTable('formas_entrega')) {
            return;
        }
        foreach (DB::table('tenants')->pluck('id') as $id) {
            if (DB::table('formas_entrega')->where('tenant_id', $id)->exists()) {
                continue;
            }
            foreach ([
                ['nombre' => 'Delivery', 'tipo' => 'delivery', 'costo' => 7, 'tiempo_estimado' => '30-45 min', 'orden' => 1],
                ['nombre' => 'Recojo en tienda', 'tipo' => 'recojo', 'costo' => 0, 'tiempo_estimado' => '20 min', 'orden' => 2],
            ] as $forma) {
                $columnas = array_flip(Schema::getColumnListing('formas_entrega'));
                FormaEntrega::withoutGlobalScopes()->forceCreate(array_intersect_key($forma + ['tenant_id' => $id, 'activa' => true], $columnas));
            }
        }
    }

    public function down(): void
    {
        // Solo agrega datos: no se quita nada.
    }
};
