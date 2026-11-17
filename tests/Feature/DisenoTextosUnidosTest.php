<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Landing y Diseño juntos: un solo formulario guarda textos, beneficios y colores. */
class DisenoTextosUnidosTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_solo_formulario_guarda_beneficios_y_landing_redirige(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Café Luna', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $t->slug = 'cafe-luna';
        $t->save();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'A', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');

        $this->actingAs($u)->get(route('admin.landing_negocio'))->assertRedirect(route('admin.personalizacion'));
        $this->actingAs($u)->get(route('admin.personalizacion'))->assertOk()->assertSee('Beneficios')->assertSee('Diseño y textos');

        $base = ['plantilla' => 'clasica', 'color_primario' => '#2B6EF6', 'color_secundario' => '#F47B20', 'fuente' => 'Poppins',
            'titulo' => 'Café de especialidad', 'subtitulo' => 'Tostado en Lima', 'boton' => 'Pedir'];
        $this->actingAs($u)->postJson(route('admin.personalizacion.guardar'), $base + ['beneficios' => [['titulo' => 'Grano peruano', 'texto' => ''], ['titulo' => 'B', 'texto' => 'b'], ['titulo' => 'C', 'texto' => 'c']]])
            ->assertStatus(422)->assertJsonValidationErrors('beneficios.0.texto');
        $this->actingAs($u)->postJson(route('admin.personalizacion.guardar'), $base + ['beneficios' => [
            ['titulo' => 'Grano peruano', 'texto' => 'De Cusco y Junín'], ['titulo' => 'Delivery', 'texto' => 'En 30 minutos'], ['titulo' => 'Wifi', 'texto' => 'Gratis'],
        ]])->assertOk();

        $this->get(route('publico.landing_negocio', 'cafe-luna'))->assertOk()
            ->assertSee('Café de especialidad')->assertSee('Grano peruano')->assertSee('De Cusco y Junín');

        // La vista previa también muestra los beneficios sin guardar.
        $this->actingAs($u)->get(route('admin.personalizacion.vista', ['beneficios' => json_encode([['titulo' => 'Nuevo', 'texto' => 'x'], ['titulo' => 'b', 'texto' => 'b'], ['titulo' => 'c', 'texto' => 'c']])]))
            ->assertOk()->assertSee('Nuevo');
    }
}
