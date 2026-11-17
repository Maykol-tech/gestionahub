<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Servicio;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Servicios: vista de calendario semanal con citas de varias semanas. */
class AgendaSemanalTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_trae_citas_de_mas_semanas_y_muestra_vista_semana(): void
    {
        $t = Tenant::create(['nombre_negocio' => 'Salón', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'A', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');
        $s = Servicio::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'nombre' => 'Corte', 'precio' => 25, 'duracion_min' => 45, 'activo' => true]);
        foreach ([-20, 2, 50] as $dias) {
            Cita::withoutGlobalScopes()->create(['tenant_id' => $t->id, 'servicio_id' => $s->id, 'cliente_nombre' => 'Cliente ' . $dias,
                'inicio' => now()->addDays($dias)->setTime(10, 0), 'duracion_min' => 45, 'precio' => 25, 'estado' => 'confirmada']);
        }

        $this->actingAs($u)->get(route('admin.servicios'))->assertOk()
            ->assertSee('Semana')->assertSee('cal-slot', false)
            ->assertSee('Cliente -20')->assertSee('Cliente 2')->assertSee('Cliente 50');
    }
}
