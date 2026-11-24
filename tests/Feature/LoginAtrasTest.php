<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Login sin credenciales de ejemplo y sin volver al login con el botón "Atrás". */
class LoginAtrasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_no_muestra_el_acceso_de_demostracion(): void
    {
        $this->get('/login')->assertOk()
            ->assertDontSee('admin@gestionahub.pe')
            ->assertDontSee('Admin2026!')
            ->assertDontSee('Acceso de demostración');
    }

    public function test_login_y_panel_no_se_guardan_en_cache(): void
    {
        $this->assertStringContainsString('no-store', $this->get('/login')->headers->get('Cache-Control'));

        Role::findOrCreate('admin', 'web');
        $tenant = Tenant::create(['nombre_negocio' => 'Prueba', 'slug' => 'prueba', 'email' => 'p@p.pe', 'estado' => 'activo']);
        $u = User::factory()->create(['tenant_id' => $tenant->id]);
        $u->assignRole('admin');
        $r = $this->actingAs($u)->get(route('admin.dashboard'))->assertOk();
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
    }

    public function test_con_sesion_el_login_lleva_al_inicio_de_cada_rol(): void
    {
        foreach (['admin' => 'admin.dashboard', 'vendedor' => 'admin.pedidos'] as $rol => $destino) {
            Role::findOrCreate($rol, 'web');
            $tenant = Tenant::create(['nombre_negocio' => 'N ' . $rol, 'slug' => 'n-' . $rol, 'email' => $rol . '@p.pe', 'estado' => 'activo']);
            $u = User::factory()->create(['tenant_id' => $tenant->id]);
            $u->assignRole($rol);
            $this->actingAs($u)->get('/login')->assertRedirect(route($destino));
        }
    }
}
