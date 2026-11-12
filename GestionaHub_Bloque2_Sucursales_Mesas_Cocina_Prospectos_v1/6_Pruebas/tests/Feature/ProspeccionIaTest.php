<?php

namespace Tests\Feature;

use App\Models\Prospecto;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ProspeccionIaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-42 y GEST-43: prospeccion con el microservicio FastAPI y guardado sin repetidos. */
class ProspeccionIaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->tenant = Tenant::create(['nombre_negocio' => 'Negocio IA', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Ana', 'email' => 'ia@test.pe', 'password' => 'Secreto123!']);
        $this->admin->assignRole('admin');
        config(['services.prospeccion.url' => 'http://ia.test', 'services.prospeccion.token' => 'token-prueba']);
    }

    private function respuestaIa(): array
    {
        return ['prospectos' => [
            ['nombre' => 'Pollería El Sol', 'telefono' => '+51 999 111 222', 'email' => 'SOL@mail.pe', 'rubro' => 'Restaurantes', 'fuente_url' => 'https://ejemplo.pe/1'],
            ['nombre' => 'Pollería Repetida', 'telefono' => '999111222', 'email' => null, 'rubro' => 'Restaurantes'],
            ['nombre' => 'Cafetería Luna', 'telefono' => null, 'email' => 'luna@mail.pe', 'rubro' => 'Cafés'],
            ['nombre' => '', 'telefono' => '988888888'],
        ]];
    }

    public function test_busqueda_guarda_prospectos_ia_sin_repetidos(): void
    {
        Http::fake(['ia.test/prospectar' => Http::response($this->respuestaIa(), 200)]);

        $this->actingAs($this->admin)
            ->post(route('admin.prospectos.buscar_ia'), ['rubro' => 'Restaurantes', 'zona' => 'Miraflores', 'limite' => 10])
            ->assertSessionHas('ok');

        Http::assertSent(fn ($request) => $request->url() === 'http://ia.test/prospectar'
            && $request->hasHeader('Authorization', 'Bearer token-prueba')
            && $request['zona'] === 'Miraflores' && $request['limite'] === 10);

        $this->assertSame(2, Prospecto::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
        $sol = Prospecto::withoutGlobalScopes()->where('nombre', 'Pollería El Sol')->first();
        $this->assertSame('ia', $sol->fuente);
        $this->assertSame('sol@mail.pe', $sol->email);
        $this->assertSame('51999111222', $sol->telefono);
    }

    public function test_segunda_busqueda_no_duplica(): void
    {
        Http::fake(['ia.test/prospectar' => Http::response($this->respuestaIa(), 200)]);
        $servicio = app(ProspeccionIaService::class);

        $primera = $servicio->importar($this->tenant->id, $this->respuestaIa()['prospectos']);
        $segunda = $servicio->importar($this->tenant->id, $this->respuestaIa()['prospectos']);

        $this->assertSame(2, $primera['guardados']);
        $this->assertSame(0, $segunda['guardados']);
        $this->assertSame(2, Prospecto::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_si_el_servicio_falla_muestra_mensaje_y_no_guarda(): void
    {
        Http::fake(['ia.test/prospectar' => Http::response('error', 500)]);

        $this->actingAs($this->admin)
            ->post(route('admin.prospectos.buscar_ia'), ['rubro' => 'Bodegas', 'zona' => 'Surco'])
            ->assertSessionHasErrors('ia');

        $this->assertSame(0, Prospecto::withoutGlobalScopes()->count());
    }

    public function test_sin_url_configurada_avisa_que_falta_configuracion(): void
    {
        config(['services.prospeccion.url' => null]);

        $this->actingAs($this->admin)
            ->post(route('admin.prospectos.buscar_ia'), ['rubro' => 'Bodegas', 'zona' => 'Surco'])
            ->assertSessionHasErrors(['ia' => 'La prospección con IA no está configurada (falta PROSPECCION_IA_URL).']);
    }

    public function test_los_prospectos_ia_son_del_tenant_que_busco(): void
    {
        Http::fake(['ia.test/prospectar' => Http::response($this->respuestaIa(), 200)]);
        $otro = Tenant::create(['nombre_negocio' => 'Otro', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->post(route('admin.prospectos.buscar_ia'), ['rubro' => 'Restaurantes', 'zona' => 'Lima']);

        $this->assertSame(0, Prospecto::withoutGlobalScopes()->where('tenant_id', $otro->id)->count());
    }
}
