<?php

namespace Tests\Feature;

use App\Models\CobroSuscripcion;
use App\Models\CrmCliente;
use App\Models\PlanCliente;
use App\Models\SuscripcionCliente;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AjustesNegocio;
use App\Support\Membresias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** GEST-57: planes de suscripción que el negocio vende a sus clientes. */
class MembresiasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $slug = null): User
    {
        $t = Tenant::create(['nombre_negocio' => 'Gym Fuerte', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $t->slug = $slug ?? 'gym-' . uniqid();
        $t->save();
        DB::table('tenants')->where('id', $t->id)->update(['yape_numero' => '987111222']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');

        return $u;
    }

    private function plan(int $tenant, float $precio = 120, string $periodo = 'mensual'): PlanCliente
    {
        return PlanCliente::withoutGlobalScopes()->create(['tenant_id' => $tenant, 'nombre' => 'Plan Full', 'precio' => $precio, 'periodicidad' => $periodo, 'beneficios' => ['Acceso libre'], 'activo' => true]);
    }

    public function test_crea_plan_inscribe_y_registra_pago(): void
    {
        $u = $this->admin();
        $this->actingAs($u)->postJson(route('admin.membresias.planes.guardar'), [
            'nombre' => 'Mensual', 'precio' => 99.9, 'periodicidad' => 'mensual', 'beneficios' => ['Clases', ' ', 'Duchas'],
        ])->assertCreated();
        $plan = PlanCliente::withoutGlobalScopes()->where('tenant_id', $u->tenant_id)->first();
        $this->assertSame(['Clases', 'Duchas'], $plan->beneficios);

        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $u->tenant_id, 'nombre' => 'Luis Ramos', 'telefono' => '987654321', 'activo' => true]);
        $this->actingAs($u)->postJson(route('admin.membresias.inscribir'), ['plan_id' => $plan->id, 'cliente_id' => $c->id])->assertCreated();
        $s = SuscripcionCliente::withoutGlobalScopes()->first();
        $this->assertSame('pendiente', $s->estado);
        $cobro = $s->cobros()->first();
        $this->assertSame(99.9, $cobro->monto);

        $this->actingAs($u)->get(route('admin.membresias'))->assertOk()->assertSee('Luis Ramos');

        $this->actingAs($u)->postJson(route('admin.membresias.cobros.pagar', $cobro->id), ['metodo' => 'efectivo'])->assertOk();
        $s->refresh();
        $this->assertSame('activa', $s->estado);
        $this->assertSame(today()->addMonthNoOverflow()->toDateString(), $s->proximo_cobro->toDateString());

        // Cambiar el precio del plan no cambia el de los suscriptores actuales
        $this->actingAs($u)->putJson(route('admin.membresias.planes.actualizar', $plan->id), ['nombre' => 'Mensual', 'precio' => 150, 'periodicidad' => 'mensual'])->assertOk();
        $this->assertSame(99.9, $s->fresh()->precio);
    }

    public function test_tarea_diaria_crea_cobro_y_marca_vencidas(): void
    {
        $u = $this->admin();
        $plan = $this->plan($u->tenant_id, 50, 'semanal');
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $u->tenant_id, 'nombre' => 'Ana', 'telefono' => '911222333', 'activo' => true]);
        $s = Membresias::suscribir($plan, $c);
        Membresias::pagar($s->cobros()->first(), 'yape');

        // Llega la fecha: se crea el cobro de la semana siguiente
        [$creados] = Membresias::procesar(today()->addWeek());
        $this->assertSame(1, $creados);
        $this->assertSame(1, $s->cobros()->where('estado', 'pendiente')->count());
        // Correrla otra vez el mismo día no duplica
        [$creados] = Membresias::procesar(today()->addWeek());
        $this->assertSame(0, $creados);

        // Pasa la gracia sin pagar: vencida
        [, $vencidas] = Membresias::procesar(today()->addWeek()->addDays(Membresias::DIAS_GRACIA + 1));
        $this->assertSame(1, $vencidas);
        $this->assertSame('vencida', $s->fresh()->estado);

        // Pagar el vencido la reactiva
        Membresias::pagar($s->cobros()->where('estado', 'vencido')->first(), 'efectivo');
        $this->assertSame('activa', $s->fresh()->estado);
        $this->assertSame(today()->addWeeks(2)->toDateString(), $s->fresh()->proximo_cobro->toDateString());
    }

    public function test_cliente_se_suscribe_y_envia_captura(): void
    {
        Storage::fake('local');
        $u = $this->admin('gym-fuerte');
        $plan = $this->plan($u->tenant_id);

        $this->get(route('membresia.planes', 'gym-fuerte'))->assertOk()->assertSee('Plan Full')->assertSee('Acceso libre');
        $r = $this->post(route('membresia.suscribirse', 'gym-fuerte'), ['plan_id' => $plan->id, 'nombre' => 'Carla Díaz', 'telefono' => '955444333']);
        $s = SuscripcionCliente::withoutGlobalScopes()->first();
        $r->assertRedirect(route('membresia.publica', $s->token));
        $this->assertSame('landing', CrmCliente::withoutGlobalScopes()->find($s->cliente_id)->fuente);

        // Suscribirse de nuevo con el mismo celular no duplica
        $this->post(route('membresia.suscribirse', 'gym-fuerte'), ['plan_id' => $plan->id, 'nombre' => 'Carla', 'telefono' => '955444333']);
        $this->assertSame(1, SuscripcionCliente::withoutGlobalScopes()->count());
        $this->assertSame(1, CrmCliente::withoutGlobalScopes()->count());

        $this->get(route('membresia.publica', $s->token))->assertOk()->assertSee('Por pagar')->assertSee('987111222')->assertDontSee('Pagar con tarjeta');

        $this->post(route('membresia.reportar', $s->token), ['metodo' => 'yape', 'captura' => UploadedFile::fake()->image('pago.jpg'), 'referencia' => '123456'])
            ->assertRedirect(route('membresia.publica', $s->token));
        $cobro = $s->cobros()->first();
        $this->assertSame('por_revisar', $cobro->estado);
        Storage::disk('local')->assertExists($cobro->captura);
        $this->get(route('membresia.publica', $s->token))->assertSee('Pago en revisión');

        // El negocio ve la captura y la rechaza o aprueba
        $this->actingAs($u)->get(route('admin.membresias.cobros.captura', $cobro->id))->assertOk();
        $this->actingAs($u)->postJson(route('admin.membresias.cobros.rechazar', $cobro->id))->assertOk();
        $this->assertSame('pendiente', $cobro->fresh()->estado);
    }

    public function test_pago_con_tarjeta_mercado_pago(): void
    {
        $u = $this->admin('gym-mp');
        AjustesNegocio::guardar($u->tenant_id, ['mp_access_token' => Crypt::encryptString('TEST-1-x')]);
        $plan = $this->plan($u->tenant_id, 80);
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $u->tenant_id, 'nombre' => 'Pedro', 'telefono' => '933222111', 'activo' => true]);
        $s = Membresias::suscribir($plan, $c);
        $cobro = $s->cobros()->first();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['init_point' => 'https://mp/real', 'sandbox_init_point' => 'https://mp/sandbox'], 201),
            'api.mercadopago.com/v1/payments/111' => Http::response(['status' => 'approved', 'external_reference' => $u->tenant_id . ':m:' . $cobro->id, 'transaction_amount' => 50]),
            'api.mercadopago.com/v1/payments/222' => Http::response(['status' => 'approved', 'external_reference' => $u->tenant_id . ':m:' . $cobro->id, 'transaction_amount' => 80]),
        ]);

        $this->get(route('membresia.publica', $s->token))->assertSee('Pagar con tarjeta');
        $this->get(route('membresia.tarjeta', $s->token))->assertRedirect('https://mp/sandbox');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'preferences') && $r['external_reference'] === $u->tenant_id . ':m:' . $cobro->id);

        // Monto distinto: no se acepta
        $this->get(route('membresia.publica', $s->token) . '?payment_id=111');
        $this->assertSame('pendiente', $cobro->fresh()->estado);

        $this->get(route('membresia.publica', $s->token) . '?payment_id=222')->assertSee('Activa');
        $this->assertSame('pagado', $cobro->fresh()->estado);
        $this->assertSame('activa', $s->fresh()->estado);
    }

    public function test_cancelar_y_reactivar(): void
    {
        $u = $this->admin();
        $plan = $this->plan($u->tenant_id);
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $u->tenant_id, 'nombre' => 'Rosa', 'activo' => true]);
        $s = Membresias::suscribir($plan, $c);

        $this->actingAs($u)->postJson(route('admin.membresias.cancelar', $s->id))->assertOk();
        $this->assertSame('cancelada', $s->fresh()->estado);
        $this->assertSame('anulado', $s->cobros()->first()->estado);
        $this->assertSame([0, 0], Membresias::procesar(today()->addMonths(2)));

        $this->actingAs($u)->postJson(route('admin.membresias.reactivar', $s->id))->assertOk();
        $this->assertSame('pendiente', $s->fresh()->estado);
        $this->assertSame(1, $s->cobros()->where('estado', 'pendiente')->count());
    }

    public function test_no_toca_datos_de_otro_negocio(): void
    {
        $u = $this->admin();
        $otro = $this->admin();
        $plan = $this->plan($otro->tenant_id);
        $c = CrmCliente::withoutGlobalScopes()->create(['tenant_id' => $otro->tenant_id, 'nombre' => 'Ajeno', 'activo' => true]);
        $s = Membresias::suscribir($plan, $c);
        $cobro = $s->cobros()->first();

        $this->actingAs($u)->putJson(route('admin.membresias.planes.actualizar', $plan->id), ['nombre' => 'X', 'precio' => 1, 'periodicidad' => 'mensual'])->assertNotFound();
        $this->actingAs($u)->postJson(route('admin.membresias.cobros.pagar', $cobro->id), ['metodo' => 'efectivo'])->assertNotFound();
        $this->actingAs($u)->postJson(route('admin.membresias.cancelar', $s->id))->assertNotFound();
        $this->actingAs($u)->postJson(route('admin.membresias.inscribir'), ['plan_id' => $plan->id, 'cliente_id' => $c->id])->assertNotFound();
        $this->assertSame('pendiente', $cobro->fresh()->estado);

        // Un plan de otro negocio no se puede contratar desde la página pública de este
        $slug = Tenant::find($u->tenant_id)->slug;
        $this->post(route('membresia.suscribirse', $slug), ['plan_id' => $plan->id, 'nombre' => 'X', 'telefono' => '911111111'])->assertNotFound();
    }
}
