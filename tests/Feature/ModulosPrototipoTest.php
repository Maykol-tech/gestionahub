<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Por pedido del profesor: los módulos que no están en el prototipo salen del panel (Seguridad se queda). */
class ModulosPrototipoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.modulos_extra' => false]);
        Role::findOrCreate('admin', 'web');
        $tenant = Tenant::create(['nombre_negocio' => 'Pollería Prueba', 'slug' => 'polleria-prueba', 'email' => 'p@prueba.pe', 'estado' => 'activo']);
        $this->admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->admin->assignRole('admin');
    }

    public function test_los_modulos_fuera_del_prototipo_no_se_abren(): void
    {
        foreach (['admin.pos', 'admin.mesas', 'admin.cocina', 'admin.comprobantes', 'admin.membresias', 'admin.espera', 'admin.resenas', 'admin.api'] as $ruta) {
            $this->actingAs($this->admin)->get(route($ruta))->assertNotFound();
        }
    }

    public function test_el_menu_no_los_muestra_pero_si_seguridad(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee(route('admin.pos'), false)->assertDontSee(route('admin.mesas'), false)
            ->assertDontSee(route('admin.cocina'), false)->assertDontSee(route('admin.comprobantes'), false)
            ->assertSee(route('admin.seguridad'), false)
            ->assertSee(route('admin.modulo', 'caja'), false)->assertSee(route('admin.modulo', 'prospectar'), false);
        $this->actingAs($this->admin)->get(route('admin.seguridad'))->assertOk();
    }

    public function test_se_pueden_volver_a_activar(): void
    {
        config(['app.modulos_extra' => true]);
        $this->actingAs($this->admin)->get(route('admin.pos'))->assertOk();
    }
}
