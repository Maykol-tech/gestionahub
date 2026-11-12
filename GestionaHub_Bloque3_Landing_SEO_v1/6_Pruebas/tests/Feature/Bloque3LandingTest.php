<?php

namespace Tests\Feature;

use App\Models\LandingContenido;
use App\Models\LandingLead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-49 a 54: landing administrable, SEO con IA, libro de reclamaciones, popup, chatbot y precios. */
class Bloque3LandingTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['superadmin', 'admin'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $tenant = Tenant::create(['nombre_negocio' => 'GestionaHub SaaS', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $this->superadmin = User::create(['tenant_id' => $tenant->id, 'name' => 'Root', 'email' => 'root@test.pe', 'password' => 'Secreto123!']);
        $this->superadmin->assignRole('superadmin');
    }

    public function test_landing_muestra_precios_con_toggle_y_libro_de_reclamaciones(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('data-periodo="anual"', false)
            ->assertSee('Libro de reclamaciones')
            ->assertSee(route('landing.reclamacion'), false);
    }

    public function test_el_titulo_de_la_landing_usa_el_seo_guardado(): void
    {
        LandingContenido::guardar('seo', ['titulo' => 'Software para restaurantes', 'descripcion' => 'Pedidos y cocina.', 'origen' => 'openai']);

        $this->get(route('landing'))
            ->assertSee('<title>Software para restaurantes</title>', false)
            ->assertSee('content="Pedidos y cocina."', false);
    }

    public function test_popup_y_chatbot_envian_su_origen_al_crm(): void
    {
        $this->post(route('landing.contacto'), [
            'nombre' => 'Rosa', 'email' => 'rosa@test.pe', 'fuente' => 'popup', 'oferta' => 'POPUP50',
        ])->assertRedirect();

        $this->post(route('landing.contacto'), [
            'nombre' => 'Luis', 'telefono' => '999000111', 'fuente' => 'chatbot', 'mensaje' => 'Varias sucursales',
        ])->assertRedirect();

        $popup = LandingLead::query()->where('nombre', 'Rosa')->firstOrFail();
        $this->assertSame('popup', $popup->fuente);
        $this->assertSame('POPUP50', $popup->oferta);

        $chat = LandingLead::query()->where('nombre', 'Luis')->firstOrFail();
        $this->assertSame('chatbot', $chat->fuente);
        $this->assertSame('Varias sucursales', $chat->mensaje);
    }

    public function test_un_origen_desconocido_se_rechaza(): void
    {
        $this->post(route('landing.contacto'), [
            'nombre' => 'Intruso', 'email' => 'i@test.pe', 'fuente' => 'otra-cosa',
        ])->assertSessionHasErrors('fuente');

        $this->assertSame(0, LandingLead::query()->count());
    }

    public function test_seo_sin_clave_openai_usa_el_titular_del_hero(): void
    {
        config(['services.openai.key' => null]);
        LandingContenido::guardar('hero', ['titulo' => 'Tu negocio en un panel', 'subtitulo' => 'Pedidos, cocina y caja.', 'cta' => 'Prueba']);

        $this->actingAs($this->superadmin)->post(route('superadmin.landing.seo'))->assertSessionHas('ok');

        $seo = LandingContenido::todo()['seo'];
        $this->assertSame('Tu negocio en un panel', $seo['titulo']);
        $this->assertSame('texto-del-hero', $seo['origen']);
    }

    public function test_seo_con_openai_guarda_la_respuesta_del_modelo(): void
    {
        config(['services.openai.key' => 'sk-prueba']);
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['titulo' => 'Sistema POS para bodegas', 'descripcion' => 'Caja, stock y WhatsApp.'])]]],
        ], 200)]);

        $this->actingAs($this->superadmin)->post(route('superadmin.landing.seo'));

        $seo = LandingContenido::todo()['seo'];
        $this->assertSame('Sistema POS para bodegas', $seo['titulo']);
        $this->assertSame('openai', $seo['origen']);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer sk-prueba'));
    }

    public function test_si_openai_falla_se_usa_el_titular_sin_romper_la_landing(): void
    {
        config(['services.openai.key' => 'sk-prueba']);
        LandingContenido::guardar('hero', ['titulo' => 'Titular seguro', 'subtitulo' => 'Sub', 'cta' => 'X']);
        Http::fake(['api.openai.com/*' => Http::response('error', 500)]);

        $this->actingAs($this->superadmin)->post(route('superadmin.landing.seo'))->assertSessionHas('ok');

        $this->assertSame('texto-del-hero', LandingContenido::todo()['seo']['origen']);
    }

    public function test_solo_el_superadmin_gestiona_el_seo(): void
    {
        $admin = User::create(['tenant_id' => Tenant::first()->id, 'name' => 'Negocio', 'email' => 'neg@test.pe', 'password' => 'Secreto123!']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('superadmin.landing.seo'))->assertForbidden();
    }

    public function test_el_popup_aparece_solo_si_esta_activo_y_con_su_tiempo(): void
    {
        LandingContenido::guardar('popup', ['activo' => true, 'titulo' => 'Oferta', 'texto' => 'Texto', 'segundos' => 30, 'oferta' => 'POPUP50']);

        $this->get(route('landing'))
            ->assertSee('id="popup"', false)
            ->assertSee('const segundos = 30;', false)
            ->assertSee('name="fuente" value="popup"', false)
            ->assertSee('value="POPUP50"', false);

        LandingContenido::guardar('popup', ['activo' => false, 'titulo' => 'Oferta', 'texto' => 'Texto', 'segundos' => 30]);
        $this->get(route('landing'))->assertDontSee('id="popup"', false);
    }
}
