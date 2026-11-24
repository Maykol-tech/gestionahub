<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** El vendedor y el cajero solo ven enlaces del panel que sí pueden abrir. */
class RolesMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_rol_solo_ve_enlaces_que_puede_abrir(): void
    {
        config(['app.modulos_extra' => false]);
        foreach (['vendedor', 'cajero'] as $rol) {
            Role::findOrCreate($rol, 'web');
            $t = Tenant::create(['nombre_negocio' => 'N ' . $rol, 'slug' => 'n-' . $rol, 'email' => $rol . '@p.pe', 'estado' => 'activo']);
            $u = User::factory()->create(['tenant_id' => $t->id]);
            $u->assignRole($rol);
            $html = $this->actingAs($u)->get(\App\Support\Roles::inicio($u))->assertOk()->getContent();
            preg_match_all('#href="(http://[^"]+/admin[^"]*)"#', $html, $m);
            $this->assertNotEmpty($m[1]);
            foreach (array_unique($m[1]) as $url) {
                $this->actingAs($u)->get($url)->assertOk();
            }
        }
    }
}
