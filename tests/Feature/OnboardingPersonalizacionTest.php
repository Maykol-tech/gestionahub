<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Tenant;
use App\Models\TiendaContenido;
use App\Models\User;
use App\Support\AjustesNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-80 a 84: galería, asistente por rubro, plantillas y personalización. */
class OnboardingPersonalizacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->tenant = Tenant::create(['nombre_negocio' => 'Negocio Prueba', 'email' => uniqid() . '@test.pe', 'estado' => 'trial']);
        $this->tenant->slug = 'negocio-prueba';
        $this->tenant->save();
        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Ana', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
    }

    public function test_asistente_configura_rubro_modulos_y_ejemplos_sin_duplicar(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('admin.bienvenida'))->assertOk()->assertSee('Configura tu negocio');

        $datos = [
            'rubro' => 'peluqueria', 'nombre_negocio' => 'Studio Bella', 'telefono' => '987654321',
            'color_primario' => '#BE185D', 'color_secundario' => '#0F172A', 'modulos' => ['servicios', 'admin.galeria'], 'ejemplos' => 1,
        ];
        $this->postJson(route('admin.bienvenida.guardar'), $datos)->assertOk();
        $this->postJson(route('admin.bienvenida.guardar'), $datos)->assertOk(); // no duplica ejemplos

        $this->assertSame('peluqueria', $this->tenant->fresh()->rubro);
        $this->assertSame(1, Categoria::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->where('nombre', 'Cortes')->count());
        $ocultos = AjustesNegocio::leer($this->tenant->id)['modulos_ocultos'];
        $this->assertContains('admin.mesas', $ocultos);
        $this->assertNotContains('servicios', $ocultos);
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Mesas y QR');
    }

    public function test_aplicar_plantilla_no_borra_textos_y_vista_previa_carga(): void
    {
        $this->actingAs($this->admin);
        TiendaContenido::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'seccion' => 'landing', 'contenido' => ['titulo' => 'Mi título propio']]);

        $this->postJson(route('admin.personalizacion.plantilla'), ['rubro' => 'restaurante', 'plantilla' => 'moderna'])->assertOk();
        $c = TiendaContenido::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->where('seccion', 'landing')->value('contenido');
        $this->assertSame('Mi título propio', $c['titulo']);
        $this->assertNotEmpty($c['subtitulo']);
        $this->assertSame('moderna', AjustesNegocio::leer($this->tenant->id)['plantilla']);

        $this->get(route('admin.personalizacion.vista', ['plantilla' => 'minimal', 'color_primario' => '#123456', 'titulo' => 'Vista previa']))
            ->assertOk()->assertSee('pl-minimal', false)->assertSee('#123456')->assertSee('Vista previa');
        $this->get(route('publico.landing_negocio', 'negocio-prueba'))->assertOk()->assertSee('pl-moderna', false)->assertSee('Mi título propio');
    }

    public function test_guardar_diseno_con_logo_y_textos(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('admin.personalizacion'))->assertOk();
        $this->post(route('admin.personalizacion.guardar'), [
            'plantilla' => 'clasica', 'color_primario' => '#2b6ef6', 'color_secundario' => '#F47B20', 'fuente' => 'Poppins',
            'titulo' => 'Hola', 'subtitulo' => 'Mundo', 'boton' => 'Pedir', 'pie_texto' => 'Pie propio',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNotNull($this->tenant->fresh()->logo_url);
        $this->get(route('publico.landing_negocio', 'negocio-prueba'))->assertOk()->assertSee('Pie propio')->assertSee('family=Poppins', false);
    }

    public function test_galeria_sube_varias_fotos_respeta_limite_y_ordena(): void
    {
        $this->actingAs($this->admin);
        $r = $this->post(route('admin.galeria.guardar'), [
            'fotos' => [UploadedFile::fake()->image('corte-uno.jpg', 800, 600), UploadedFile::fake()->image('corte-dos.jpg', 800, 600)],
            'categoria' => 'Cortes',
        ], ['Accept' => 'application/json'])->assertOk();
        $ids = collect($r->json('items'))->pluck('id')->all();
        $this->assertCount(2, $ids);
        $this->assertSame('Corte uno', $r->json('items.0.titulo'));

        $this->postJson(route('admin.galeria.ordenar'), ['ids' => array_reverse($ids)])->assertOk();
        $this->assertSame(1, \App\Models\GaleriaItem::find($ids[1])->orden);

        $fotos = array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg", 50, 50), range(1, 19));
        $this->post(route('admin.galeria.guardar'), ['fotos' => $fotos], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('fotos');

        $this->get(route('publico.landing_negocio', 'negocio-prueba'))->assertOk()->assertSee('Nuestros trabajos')->assertSee('Cortes');
    }
}
