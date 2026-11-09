<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguridadRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'vendedor', 'cajero', 'repartidor'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
    }

    private function usuario(string $rol, string $estado = 'activo'): User
    {
        $tenant = Tenant::create([
            'nombre_negocio' => 'Negocio de prueba', 'email' => uniqid() . '@test.pe', 'estado' => $estado,
        ]);
        $usuario = User::create([
            'tenant_id' => $tenant->id, 'name' => ucfirst($rol), 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!',
        ]);
        $usuario->assignRole($rol);

        return $usuario;
    }

    public function test_api_protegida_exige_token(): void
    {
        $this->getJson('/api/clientes')->assertUnauthorized();
        $this->getJson('/api/productos')->assertUnauthorized();
    }

    public function test_login_api_es_publico(): void
    {
        $this->postJson('/api/login', ['email' => 'nadie@test.pe', 'password' => 'x'])->assertUnprocessable();
    }

    public function test_panel_sin_sesion_va_al_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_vendedor_no_ve_caja(): void
    {
        $this->actingAs($this->usuario('vendedor'))->get('/admin/caja')->assertForbidden();
    }

    public function test_cajero_no_ve_productos(): void
    {
        $this->actingAs($this->usuario('cajero'))->get('/admin/productos')->assertForbidden();
    }

    public function test_negocio_suspendido_bloquea_el_panel(): void
    {
        $this->actingAs($this->usuario('admin', 'suspendido'))->get('/admin')->assertRedirect('/login');
    }
}