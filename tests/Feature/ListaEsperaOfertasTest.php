<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\ListaEsperaCita;
use App\Models\Servicio;
use App\Models\Tenant;
use App\Support\ListaEspera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** GEST-86: cupo liberado con enlace que vence y pasa a la siguiente persona. */
class ListaEsperaOfertasTest extends TestCase
{
    use RefreshDatabase;

    public function test_cupo_pasa_a_la_siguiente_si_no_responde_y_se_puede_tomar(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Salón', 'email' => uniqid() . '@test.pe', 'estado' => 'trial']);
        $s = Servicio::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Corte', 'duracion_min' => 30, 'precio' => 25, 'activo' => true]);
        $manana = now()->addDay()->setTime(10, 0);
        $cita = Cita::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'servicio_id' => $s->id, 'cliente_nombre' => 'Ana', 'cliente_telefono' => '900000001', 'inicio' => $manana, 'duracion_min' => 30, 'precio' => 25, 'estado' => 'confirmada']);
        $primera = ListaEsperaCita::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'servicio_id' => $s->id, 'cliente_nombre' => 'Luis', 'cliente_telefono' => '900000002', 'fecha_deseada' => $manana->toDateString(), 'estado' => 'esperando']);
        $this->travel(1)->seconds();
        $segunda = ListaEsperaCita::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'servicio_id' => null, 'cliente_nombre' => 'Rosa', 'cliente_telefono' => '900000003', 'fecha_deseada' => $manana->toDateString(), 'estado' => 'esperando']);

        $cita->update(['estado' => 'cancelada']);
        $this->assertSame(1, ListaEspera::procesar());
        $this->assertSame(0, ListaEspera::procesar()); // mientras la oferta está abierta no se avisa a nadie más
        $this->assertSame('avisado', $primera->fresh()->estado);

        // Luis no responde a tiempo → pasa a Rosa
        $this->travel(31)->minutes();
        $this->assertSame(1, ListaEspera::procesar());
        $this->assertSame('esperando', $primera->fresh()->estado);
        $oferta = DB::table('lista_espera_ofertas')->where('espera_id', $segunda->id)->first();
        $this->assertNotNull($oferta);

        // El enlace viejo de Luis ya no sirve
        $viejo = DB::table('lista_espera_ofertas')->where('espera_id', $primera->id)->first();
        $this->get(url('/espera/' . $viejo->token))->assertOk()->assertSee('ya no está disponible');

        // Rosa toma el cupo
        $this->get(url('/espera/' . $oferta->token))->assertOk()->assertSee('Tomar este cupo');
        $this->post(url('/espera/' . $oferta->token))->assertRedirect();
        $this->assertSame('reservado', $segunda->fresh()->estado);
        $this->assertSame(1, Cita::withoutGlobalScopes()->where('cliente_nombre', 'Rosa')->where('estado', 'confirmada')->count());
        $this->post(url('/espera/' . $oferta->token))->assertSessionHasErrors('cupo'); // no se toma dos veces
    }
}
