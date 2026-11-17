<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\AjustesNegocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Cuenta → Seguridad: intentos, tiempo de bloqueo (minutos u horas), inactividad, desbloqueo e historial. */
class SeguridadModuloTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol = 'admin', ?int $tenantId = null): User
    {
        $tenantId ??= Tenant::create(['nombre_negocio' => 'Negocio ' . uniqid(), 'email' => uniqid() . '@test.pe', 'estado' => 'activo'])->id;
        Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $tenantId, 'name' => ucfirst($rol), 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $u->assignRole($rol);

        return $u;
    }

    private function fallar(string $email, int $veces)
    {
        $r = null;
        for ($i = 0; $i < $veces; $i++) {
            $r = $this->from('/login')->post('/login', ['email' => $email, 'password' => 'mala']);
        }

        return $r;
    }

    public function test_admin_configura_intentos_y_bloqueo_en_horas(): void
    {
        $admin = $this->usuario();
        $this->actingAs($admin)->get(route('admin.seguridad'))->assertOk()->assertSee('Reglas de acceso');
        $this->actingAs($admin)->post(route('admin.seguridad.guardar'), [
            'intentos' => 3, 'bloqueo_valor' => 2, 'bloqueo_unidad' => 'horas', 'inactividad_activa' => 1, 'inactividad_minutos' => 20,
        ])->assertSessionHas('ok');

        $a = AjustesNegocio::leer($admin->tenant_id);
        $this->assertSame(3, $a['seg_intentos']);
        $this->assertSame('horas', $a['seg_bloqueo_unidad']);
        auth()->logout();

        // Con 3 intentos ya bloquea, y por 2 horas
        $this->fallar($admin->email, 2);
        $r = $this->fallar($admin->email, 1);
        $r->assertSessionHas('intentos_max', 3);
        $this->assertGreaterThan(7000, session('bloqueo_segundos'));
        $this->assertMatchesRegularExpression('/(2 h|1 h 59 min)/', session('errors')->first('email'));
        $this->assertSame(1, DB::table('accesos_log')->where('email', $admin->email)->where('resultado', 'bloqueado')->count());
        $this->assertSame(2, DB::table('accesos_log')->where('email', $admin->email)->where('resultado', 'fallido')->count());
    }

    public function test_valida_limites(): void
    {
        $admin = $this->usuario();
        $this->actingAs($admin)->post(route('admin.seguridad.guardar'), ['intentos' => 1, 'bloqueo_valor' => 30, 'bloqueo_unidad' => 'horas', 'inactividad_minutos' => 0])
            ->assertSessionHasErrors(['intentos', 'bloqueo_valor', 'inactividad_minutos']);
    }

    public function test_apagar_inactividad_llega_al_panel(): void
    {
        $admin = $this->usuario();
        $this->actingAs($admin)->post(route('admin.seguridad.guardar'), ['intentos' => 5, 'bloqueo_valor' => 15, 'bloqueo_unidad' => 'minutos', 'inactividad_minutos' => 10]);
        $this->assertFalse(AjustesNegocio::leer($admin->tenant_id)['seg_inactividad_activa']);
        $this->assertFalse(\App\Http\Controllers\Auth\PantallaBloqueoController::activo());
        $this->actingAs($admin)->get(route('admin.seguridad'))->assertOk()->assertSee('\\u0022activo\\u0022:false', false);
    }

    public function test_admin_ve_al_bloqueado_y_lo_desbloquea(): void
    {
        $admin = $this->usuario();
        $vendedor = $this->usuario('vendedor', $admin->tenant_id);
        $this->fallar($vendedor->email, 5)->assertSessionHas('bloqueo_segundos');

        $this->actingAs($admin)->get(route('admin.seguridad'))->assertOk()->assertSee($vendedor->email)->assertSee('Desbloquear');
        $this->actingAs($admin)->post(route('admin.seguridad.desbloquear'), ['email' => $vendedor->email, 'ip' => '127.0.0.1'])->assertSessionHas('ok');
        auth()->logout();

        $this->post('/login', ['email' => $vendedor->email, 'password' => 'Secreto123!'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($vendedor);
        $this->assertTrue(DB::table('accesos_log')->where('email', $vendedor->email)->where('resultado', 'desbloqueado')->exists());
        $this->assertTrue(DB::table('accesos_log')->where('email', $vendedor->email)->where('resultado', 'ingreso')->exists());
    }

    public function test_no_desbloquea_correos_de_otro_negocio_ni_lo_ve_el_vendedor(): void
    {
        $admin = $this->usuario();
        $otro = $this->usuario();
        $this->fallar($otro->email, 5);
        $this->actingAs($admin)->get(route('admin.seguridad'))->assertDontSee($otro->email);
        $this->actingAs($admin)->post(route('admin.seguridad.desbloquear'), ['email' => $otro->email, 'ip' => '127.0.0.1'])->assertNotFound();

        $vendedor = $this->usuario('vendedor', $admin->tenant_id);
        $this->actingAs($vendedor)->get(route('admin.seguridad'))->assertStatus(403);
    }
}
