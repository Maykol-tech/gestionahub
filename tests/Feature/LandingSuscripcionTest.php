<?php

namespace Tests\Feature;

use App\Http\Middleware\CapturarUtm;
use App\Models\LandingLead;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Cms;
use App\Support\Prueba;
use App\Support\Suscripciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-88..100: CMS de la landing, leads, prueba de 14 días, checkout, Niubiz, pagos manuales, blog, UTM y Google. */
class LandingSuscripcionTest extends TestCase
{
    use RefreshDatabase;

    private int $planId;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'superadmin'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }
        $this->planId = DB::table('planes_saas')->insertGetId([
            'nombre' => 'Negocio', 'slug' => 'negocio', 'precio_mensual' => 89, 'precio_anual' => 890,
            'max_usuarios' => 5, 'max_sucursales' => 1, 'activo' => true,
        ]);
    }

    private function usuario(string $rol = 'admin', array $tenant = []): User
    {
        $t = Tenant::create($tenant + ['nombre_negocio' => 'Pollería Rosa', 'email' => uniqid() . '@test.pe', 'estado' => 'activo']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Rosa', 'email' => uniqid() . '@test.pe', 'password' => 'Secreto123!']);
        $u->assignRole($rol);

        return $u;
    }

    public function test_landing_usa_el_cms_secciones_blog_y_barra_superadmin(): void
    {
        Cms::reemplazar('faqs', [['pregunta' => '¿Tiene app?', 'respuesta' => 'Sí, desde el navegador.']]);
        Cms::reemplazar('logos', [['nombre' => 'Bodega Sarita']]);
        Cms::guardarSecciones(['como' => ['visible' => false], 'funciones' => ['visible' => true, 'titulo' => 'Todo en uno']]);
        \App\Models\LandingContenido::guardar('blog', ['url' => 'https://blog.test', 'titulo' => 'Consejos', 'cantidad' => 3]);
        Http::fake(['blog.test/*' => Http::response([
            ['title' => ['rendered' => 'Cómo vender más'], 'excerpt' => ['rendered' => '<p>Tips</p>'], 'link' => 'https://blog.test/a', 'date' => '2026-11-01T10:00:00'],
        ])]);

        $html = $this->get('/landing')->assertOk()->getContent();
        $this->assertStringContainsString('¿Tiene app?', $html);
        $this->assertStringContainsString('Bodega Sarita', $html);
        $this->assertStringContainsString('Cómo vender más', $html);
        $this->assertStringNotContainsString('id="como"', $html);
        $this->assertStringNotContainsString('Leads hoy', $html);

        // El blog queda en caché 1 hora: no vuelve a llamar a WordPress
        $this->get('/landing')->assertOk();
        Http::assertSentCount(1);

        $super = $this->usuario('superadmin');
        $this->actingAs($super)->get('/landing')->assertSee('Leads hoy');
        $this->actingAs($super)->get('/landing?visitante=1')->assertDontSee('Leads hoy');
    }

    public function test_editor_guarda_listas_y_valida(): void
    {
        $super = $this->usuario('superadmin');
        $this->actingAs($super)->get('/superadmin/landing')->assertOk()->assertSee('Testimonios');
        $this->actingAs($super)->postJson('/superadmin/landing/cms', ['tipo' => 'testimonios', 'items' => [
            ['nombre' => 'Lucía', 'negocio' => 'Spa', 'texto' => 'Muy bueno', 'estrellas' => 4, 'visible' => true],
        ]])->assertOk();
        $this->assertSame(4, (int) DB::table('cms_testimonios')->value('estrellas'));
        $this->actingAs($super)->postJson('/superadmin/landing/cms', ['tipo' => 'faqs', 'items' => [['pregunta' => '', 'respuesta' => 'x']]])
            ->assertUnprocessable();
        // Un administrador de negocio no entra al editor
        $this->actingAs($this->usuario())->get('/superadmin/landing')->assertForbidden();
    }

    public function test_landing_usa_cifras_y_datos_reales_de_la_empresa(): void
    {
        // Sin datos reales no se muestran cifras inventadas
        $this->get('/landing')->assertOk()->assertDontSee('negocios activos');

        $super = $this->usuario('superadmin');
        $base = ['hero_titulo' => 'Hola', 'hero_subtitulo' => 'Sub', 'hero_cta' => 'Probar', 'pie_texto' => '© GH',
            'beneficio' => array_fill(0, 3, ['titulo' => 'T', 'texto' => 'X'])];
        $this->actingAs($super)->post('/superadmin/landing', $base + ['ruc' => '123'])->assertSessionHasErrors('ruc');

        $this->actingAs($super)->post('/superadmin/landing', $base + [
            'cifras_mostrar' => '1',
            'cifra' => [['valor' => 35, 'sufijo' => '+', 'texto' => 'negocios activos'], ['valor' => 0, 'sufijo' => '', 'texto' => ''],
                ['valor' => 0, 'sufijo' => '', 'texto' => ''], ['valor' => 0, 'sufijo' => '', 'texto' => '']],
            'razon_social' => 'Intelecta Lab S.A.C.', 'ruc' => '20612345678', 'whatsapp' => '+51 987 654 321',
            'video_url' => 'https://youtube.com/watch?v=demo', 'terminos_url' => 'https://ejemplo.pe/terminos', 'privacidad_url' => 'https://ejemplo.pe/privacidad',
        ])->assertSessionHasNoErrors();

        auth()->logout();
        $this->get('/landing')->assertOk()
            ->assertSee('data-contar="35"', false)->assertSee('negocios activos')
            ->assertSee('RUC 20612345678')->assertSee('https://wa.me/51987654321', false)
            ->assertSee('Ver demo en video')->assertSee('https://ejemplo.pe/terminos', false);
        $this->get('/registro')->assertOk()->assertSee('https://ejemplo.pe/privacidad', false);
    }

    public function test_lead_guarda_utm_de_la_cookie_no_se_duplica_y_se_mueve_en_el_embudo(): void
    {
        $this->get('/landing?utm_source=facebook&utm_campaign=noviembre')->assertCookie(CapturarUtm::COOKIE);

        $cookie = json_encode(['utm_source' => 'facebook', 'utm_campaign' => 'noviembre']);
        $this->withCookie(CapturarUtm::COOKIE, $cookie)
            ->post('/landing/contacto', ['nombre' => 'Jorge', 'telefono' => '987111222'])->assertRedirect();
        $this->withCookie(CapturarUtm::COOKIE, $cookie)
            ->post('/landing/contacto', ['nombre' => 'Jorge', 'telefono' => '987111222', 'mensaje' => 'Otra vez'])->assertRedirect();

        $this->assertSame(1, LandingLead::count());
        $lead = LandingLead::first();
        $this->assertSame('facebook', $lead->utm_source);
        $this->assertSame('noviembre', $lead->utm_campaign);
        $this->assertStringContainsString('Otra vez', (string) $lead->notas);

        $super = $this->usuario('superadmin');
        $this->actingAs($super)->get('/superadmin/leads')->assertOk()->assertSee('Jorge');
        $this->actingAs($super)->patchJson('/superadmin/leads/' . $lead->id, ['estado' => 'demo'])->assertOk()->assertJsonPath('lead.etapa', 'demo');
        $this->assertNotNull($lead->fresh()->contactado_at);
        $csv = $this->actingAs($super)->get('/superadmin/leads/exportar')->assertOk()->streamedContent();
        $this->assertStringContainsString('facebook', $csv);
    }

    public function test_prueba_vencida_queda_en_solo_lectura_y_avisos_una_vez(): void
    {
        Mail::fake();
        $u = $this->usuario('admin', ['estado' => 'trial', 'trial_ends_at' => now()->addDays(3)->addHour()]);
        $this->actingAs($u)->get('/admin')->assertOk()->assertSee('quedan 4 días de prueba', false);

        $this->assertSame(1, Prueba::enviarAvisos());
        $this->assertSame(0, Prueba::enviarAvisos()); // el aviso de 3 días no se repite

        $u->tenant->update(['trial_ends_at' => now()->subMinute()]);
        Prueba::limpiarCache();
        $u->refresh();
        $this->actingAs($u)->get('/admin/planes')->assertOk()->assertSee('Tu prueba gratis terminó');
        $this->actingAs($u)->postJson('/admin/gastos', ['concepto' => 'x', 'monto' => 1])->assertStatus(402)->assertJsonPath('solo_lectura', true);
        // Pagar sí funciona
        $this->actingAs($u)->get('/admin/planes/negocio/checkout')->assertOk()->assertSee('Enviar comprobante');
    }

    public function test_pago_manual_con_captura_operacion_unica_y_aprobacion(): void
    {
        Mail::fake();
        $u = $this->usuario('admin', ['estado' => 'trial', 'trial_ends_at' => now()->addDays(5)]);
        LandingLead::create(['nombre' => 'Rosa', 'email' => $u->email, 'tenant_id' => $u->tenant_id, 'estado' => 'demo']);

        $this->actingAs($u)->get('/admin/planes/negocio/checkout?ciclo=anual')->assertOk()->assertSee('890.00');
        $this->actingAs($u)->post('/admin/planes/negocio/pagar', [
            'metodo' => 'yape', 'ciclo' => 'anual', 'numero_operacion' => '12-34-5678', 'captura' => UploadedFile::fake()->image('yape.jpg'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pago = DB::table('pagos_saas')->first();
        $this->assertSame('pendiente', $pago->estado);
        $this->assertSame('12345678', $pago->numero_operacion);
        $this->assertEquals(890, (float) $pago->monto);

        // El mismo número de operación no se acepta otra vez (otro negocio)
        $otro = $this->usuario();
        $this->actingAs($otro)->post('/admin/planes/negocio/pagar', [
            'metodo' => 'yape', 'numero_operacion' => '12345678', 'captura' => UploadedFile::fake()->image('y.jpg'),
        ])->assertSessionHasErrors('numero_operacion');

        $super = $this->usuario('superadmin');
        $this->actingAs($super)->get('/superadmin/suscripciones')->assertOk()->assertSee('Pollería Rosa');
        $this->actingAs($super)->get('/superadmin/suscripciones/pagos/' . $pago->id . '/captura')->assertOk();
        $this->actingAs($super)->post('/superadmin/suscripciones/pagos/' . $pago->id . '/aprobar')->assertRedirect();

        $this->assertSame('pagado', DB::table('pagos_saas')->value('estado'));
        $tenant = $u->tenant->fresh();
        $this->assertSame('activo', $tenant->estado);
        $sus = DB::table('suscripciones_saas')->where('tenant_id', $u->tenant_id)->first();
        $this->assertSame('activa', $sus->estado);
        $this->assertTrue(\Carbon\Carbon::parse($sus->vence_at)->greaterThan(now()->addMonths(11)));
        $this->assertSame('ganado', LandingLead::first()->estado);
        // No se aprueba dos veces
        $this->actingAs($super)->post('/superadmin/suscripciones/pagos/' . $pago->id . '/aprobar')->assertSessionHasErrors('pago');
    }

    public function test_niubiz_cobra_activa_y_la_renovacion_fallida_deja_solo_lectura(): void
    {
        Mail::fake();
        config(['services.niubiz' => ['merchant_id' => '456879852', 'usuario' => 'u', 'clave' => 'c', 'produccion' => false]]);
        Http::fake([
            '*/api.security/*' => Http::response('TOKEN123'),
            '*/token/session/*' => Http::response(['sessionKey' => 'SES1']),
            '*/token/card/*' => Http::response(['token' => ['tokenId' => 'CARD-TOK'], 'card' => ['brand' => 'visa', 'cardNumber' => '411111******1111']]),
            '*/api.authorization/*' => Http::sequence()
                ->push(['dataMap' => ['ACTION_CODE' => '000', 'TRANSACTION_ID' => 'T1']])
                ->push(['data' => ['ACTION_CODE' => '116', 'ACTION_DESCRIPTION' => 'Fondos insuficientes']], 400)
                ->push(['data' => ['ACTION_CODE' => '116', 'ACTION_DESCRIPTION' => 'Fondos insuficientes']], 400)
                ->push(['data' => ['ACTION_CODE' => '116', 'ACTION_DESCRIPTION' => 'Fondos insuficientes']], 400),
        ]);

        $u = $this->usuario('admin', ['estado' => 'trial', 'trial_ends_at' => now()->addDays(2)]);
        $r = $this->actingAs($u)->postJson('/admin/planes/negocio/niubiz', ['ciclo' => 'mensual', 'renovar' => true])->assertOk();
        $this->assertSame('SES1', $r->json('sessiontoken'));

        // Niubiz devuelve el pago por POST a la URL firmada, sin sesión
        auth()->logout();
        $this->post($r->json('action'), ['transactionToken' => 'TX1'])->assertRedirect();
        $sus = DB::table('suscripciones_saas')->where('tenant_id', $u->tenant_id)->first();
        $this->assertSame('activa', $sus->estado);
        $this->assertTrue((bool) $sus->renovacion_auto);
        $this->assertSame('1111', $sus->tarjeta_ultimos);
        $this->assertSame('activo', $u->tenant->fresh()->estado);
        // URL alterada = rechazada
        $this->post(str_replace('renovar=1', 'renovar=0', $r->json('action')), ['transactionToken' => 'TX1'])->assertForbidden();

        // Vence y la tarjeta falla 3 veces → vencida → solo lectura pasada la gracia
        DB::table('suscripciones_saas')->update(['vence_at' => now()->subHour()]);
        foreach (range(1, 3) as $i) {
            Suscripciones::cobrarRenovaciones();
            DB::table('suscripciones_saas')->update(['proximo_intento_at' => now()->subMinute()]);
        }
        $this->assertSame('vencida', DB::table('suscripciones_saas')->value('estado'));
        $this->assertSame(3, DB::table('pagos_saas')->where('estado', 'fallido')->count());
        DB::table('suscripciones_saas')->update(['vence_at' => now()->subDays(Prueba::GRACIA + 1)]);
        Prueba::limpiarCache();
        $this->assertTrue(Prueba::de($u->tenant->fresh())['solo_lectura']);
    }

    public function test_checkout_sin_pagar_se_vuelve_lead(): void
    {
        Mail::fake();
        $u = $this->usuario('admin', ['estado' => 'trial', 'trial_ends_at' => now()->addDays(5)]);
        $this->actingAs($u)->get('/admin/planes/negocio/checkout')->assertOk();
        $this->assertSame(0, Suscripciones::marcarAbandonados()); // aún no pasan 30 minutos

        $this->travel(31)->minutes();
        $this->assertSame(1, Suscripciones::marcarAbandonados());
        $lead = LandingLead::where('fuente', 'checkout')->first();
        $this->assertNotNull($lead);
        $this->assertSame($u->tenant_id, (int) $lead->tenant_id);
        $this->assertSame('abandonado', DB::table('checkouts_saas')->value('estado'));
        $this->assertSame(0, Suscripciones::marcarAbandonados()); // una sola vez
    }

    public function test_google_crea_la_cuenta_con_prueba_o_vincula_la_existente(): void
    {
        config(['services.google' => ['client_id' => 'id', 'client_secret' => 's', 'redirect' => '/auth/google/callback']]);
        $this->get('/login')->assertSee('Continuar con Google');

        $falso = new \Laravel\Socialite\Two\User();
        $falso->map(['id' => 'g-1', 'name' => 'Ana Pérez', 'email' => 'ana@gmail.com', 'avatar' => null]);
        $falso->user = ['email_verified' => true];
        $proveedor = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $proveedor->shouldReceive('user')->andReturn($falso);
        Socialite::shouldReceive('driver')->with('google')->andReturn($proveedor);

        $this->get('/auth/google/callback')->assertRedirect(route('admin.bienvenida'));
        $u = User::where('email', 'ana@gmail.com')->first();
        $this->assertSame('g-1', $u->google_id);
        $this->assertSame('trial', $u->tenant->estado);
        $this->assertNotNull($u->tenant->trial_ends_at);
        $this->assertSame('google', LandingLead::where('email', 'ana@gmail.com')->value('fuente'));

        // Segunda vez: entra a la misma cuenta, no crea otra
        auth()->logout();
        $this->get('/auth/google/callback')->assertRedirect();
        $this->assertSame(1, User::where('email', 'ana@gmail.com')->count());
    }
}
