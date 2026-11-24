<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Base creada sin el seeder: "Cambiar a este plan" no debe dar 404. */
class PlanesSinSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_funciona_aunque_la_tabla_de_planes_este_vacia(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $t = Tenant::create(['nombre_negocio' => 'Bodega Luz', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Luz', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');
        $this->assertSame(0, DB::table('planes_saas')->count());

        $this->actingAs($u)->get(route('admin.planes.checkout', 'negocio') . '?ciclo=mensual')->assertOk();

        $this->assertSame(3, DB::table('planes_saas')->count());
        $this->assertEquals(89, DB::table('planes_saas')->where('slug', 'negocio')->value('precio_mensual'));
    }

    public function test_no_toca_planes_existentes(): void
    {
        DB::table('planes_saas')->insert(['nombre' => 'Pro', 'slug' => 'pro', 'precio_mensual' => 199, 'precio_anual' => 100, 'max_usuarios' => 1, 'max_sucursales' => 1, 'activo' => true]);

        \App\Support\Suscripciones::asegurarPlanes();

        $this->assertSame(1, DB::table('planes_saas')->count());
        $this->assertEquals(199, DB::table('planes_saas')->where('slug', 'pro')->value('precio_mensual'));
    }

    public function test_landing_muestra_los_planes_aunque_la_tabla_este_vacia(): void
    {
        $this->get('/landing')->assertOk()->assertDontSee('Pronto publicaremos nuestros planes')->assertSee('Emprendedor');
        $this->assertSame(3, DB::table('planes_saas')->count());
    }
}
