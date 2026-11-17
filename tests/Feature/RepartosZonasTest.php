<?php

namespace Tests\Feature;

use App\Models\Repartidor;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ZonaDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-23/25: Repartos muestra solo los datos del propio negocio y gestiona zonas de delivery. */
class RepartosZonasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $negocio): User
    {
        $t = Tenant::create(['nombre_negocio' => $negocio, 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');

        return $u;
    }

    public function test_repartos_es_del_propio_negocio_y_crea_zonas(): void
    {
        $a = $this->admin('Negocio A');
        $b = $this->admin('Negocio B');
        Repartidor::withoutGlobalScopes()->create(['tenant_id' => $a->tenant_id, 'nombre' => 'Carlos A', 'activo' => true, 'disponible' => true]);
        Repartidor::withoutGlobalScopes()->create(['tenant_id' => $b->tenant_id, 'nombre' => 'Beto B', 'activo' => true, 'disponible' => true]);

        $this->actingAs($a)->get(route('admin.repartos'))->assertOk()->assertSee('Carlos A')->assertDontSee('Beto B');

        $this->actingAs($a)->post(route('admin.zonas.guardar'), ['nombre' => 'Surco', 'costo' => 9, 'tiempo_min' => 40, 'color' => '#22AA55'])->assertSessionHas('ok');
        $zona = ZonaDelivery::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($a->tenant_id, $zona->tenant_id);

        $this->actingAs($a)->put(route('admin.zonas.actualizar', $zona->id), ['nombre' => 'Surco', 'costo' => 10])->assertSessionHas('ok');
        $this->assertEquals(10, (float) $zona->fresh()->costo);

        // Otro negocio no puede tocarla
        $this->actingAs($b)->delete(route('admin.zonas.eliminar', $zona->id))->assertNotFound();
        $this->actingAs($a)->delete(route('admin.zonas.eliminar', $zona->id))->assertSessionHas('ok');
        $this->assertSame(0, ZonaDelivery::withoutGlobalScopes()->count());
    }
}
