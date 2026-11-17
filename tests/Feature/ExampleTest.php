<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sin sesion, la raiz muestra la landing (con el boton de prueba gratis).
     */
    public function test_la_raiz_manda_a_la_landing_si_no_hay_sesion(): void
    {
        $this->get('/')->assertRedirect(route('landing'));
    }

    public function test_la_raiz_manda_al_panel_con_sesion(): void
    {
        $this->actingAs(\App\Models\User::factory()->create())->get('/')->assertRedirect('/admin');
    }

    public function test_registro_se_ve_como_vista_previa_con_sesion(): void
    {
        $this->actingAs(\App\Models\User::factory()->create())->get('/registro')
            ->assertOk()->assertSee('vista previa');
    }

    public function test_registro_se_ve_sin_sesion(): void
    {
        $this->get('/registro')->assertOk()->assertSee('Empezar prueba gratis')->assertDontSee('vista previa');
    }
}
