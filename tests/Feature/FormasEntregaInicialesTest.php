<?php

namespace Tests\Feature;

use App\Models\FormaEntrega;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FormasEntregaInicialesTest extends TestCase
{
    use RefreshDatabase;

    private function migrar(): void
    {
        (require database_path('migrations/2026_11_25_100000_formas_entrega_iniciales.php'))->up();
    }

    public function test_negocio_sin_entregas_recibe_delivery_y_recojo(): void
    {
        $this->seed();
        $id = DB::table('tenants')->value('id');
        DB::table('formas_entrega')->delete();

        $this->migrar();

        $this->assertEqualsCanonicalizing(['delivery', 'recojo'], FormaEntrega::withoutGlobalScopes()->where('tenant_id', $id)->pluck('tipo')->all());
        $slug = DB::table('tenants')->where('id', $id)->value('slug');
        $this->get('/tienda/' . $slug)->assertOk()->assertSee('Recojo en tienda');
    }

    public function test_no_toca_negocios_que_ya_tienen_entregas(): void
    {
        $this->seed();
        $id = DB::table('tenants')->value('id');
        DB::table('formas_entrega')->delete();
        FormaEntrega::withoutGlobalScopes()->forceCreate(['tenant_id' => $id, 'nombre' => 'Solo recojo', 'tipo' => 'recojo', 'costo' => 0, 'activa' => true]);

        $this->migrar();

        $this->assertSame(['Solo recojo'], FormaEntrega::withoutGlobalScopes()->where('tenant_id', $id)->pluck('nombre')->all());
    }
}
