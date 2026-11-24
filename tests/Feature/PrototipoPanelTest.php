<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Pantallas ajustadas al prototipo: recuadros IA, caja del día, prospectar y superadmin. */
class PrototipoPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'superadmin'] as $r) {
            Role::findOrCreate($r, 'web');
        }
        $tenant = Tenant::create(['nombre_negocio' => 'Pollería Prueba', 'slug' => 'polleria-prueba', 'email' => 'p@prueba.pe', 'estado' => 'activo']);
        $this->admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->admin->assignRole('admin');
    }

    public function test_los_modulos_muestran_su_recuadro_ia(): void
    {
        foreach (['produccion' => 'Alerta de producción', 'publicidad' => 'IA · Recomendación', 'servicios' => 'Análisis de servicios',
            'reportes' => 'IA · Análisis', 'fidelizacion' => 'Recomendación de fidelización', 'cobros' => 'Alerta de cobros',
            'prospectar' => 'Identificación de oportunidades'] as $modulo => $titulo) {
            $this->actingAs($this->admin)->get(route('admin.modulo', $modulo))->assertOk()->assertSee('data-ia="' . $modulo . '"', false)->assertSee($titulo);
        }
        $this->actingAs($this->admin)->get(route('admin.repartos'))->assertOk()->assertSee('Asistente de repartos');
    }

    public function test_publicidad_recomienda_el_canal_que_mas_vende(): void
    {
        $t = $this->admin->tenant_id;
        DB::table('campanas_publicidad')->insert([
            ['tenant_id' => $t, 'nombre' => 'A', 'canal' => 'facebook', 'inversion' => 50, 'ventas_atribuidas' => 400, 'activo' => true, 'fecha_inicio' => today(), 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => $t, 'nombre' => 'B', 'canal' => 'tiktok', 'inversion' => 50, 'ventas_atribuidas' => 60, 'activo' => true, 'fecha_inicio' => today(), 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach (DB::table('campanas_publicidad')->get() as $c) {
            DB::table('campana_gastos')->insert(['tenant_id' => $t, 'campana_id' => $c->id, 'fecha' => today(), 'monto' => 50, 'concepto' => 'Anuncio', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->actingAs($this->admin)->get(route('admin.modulo', 'publicidad'))->assertOk()
            ->assertSee('Facebook está funcionando mejor')->assertSee('Canales activos')->assertSee('Historial de gastos');
    }

    public function test_caja_muestra_tarjetas_y_resumen_de_cierre(): void
    {
        $this->actingAs($this->admin)->get(route('admin.modulo', 'caja'))->assertOk()
            ->assertSee('Resultado del día')->assertSee('Resumen para cierre')->assertSee('Ganancia neta del día')->assertSee('Yape/Plin');
    }

    public function test_prospectar_ya_no_dice_proximamente_y_lista_los_prospectos(): void
    {
        DB::table('prospectos')->insert(['tenant_id' => $this->admin->tenant_id, 'nombre' => 'Bodega Don Pepe', 'rubro' => 'Bodegas', 'fuente' => 'ia', 'estado' => 'nuevo', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->admin)->get(route('admin.modulo', 'prospectar'))->assertOk()
            ->assertDontSee('Próximamente')->assertSee('Bodega Don Pepe')->assertSee(route('admin.prospectos.buscar_ia'), false);
    }

    public function test_superadmin_muestra_mrr_distribucion_y_sucursales(): void
    {
        $this->admin->assignRole('superadmin');
        $this->actingAs($this->admin)->get('/superadmin')->assertOk()
            ->assertSee('Ingreso mensual (MRR)')->assertSee('Distribución de planes')->assertSee('Sucursales');
    }

    public function test_pantallas_con_contenido_del_prototipo(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Gasto publicidad')->assertSee('data-ia="dashboard"', false);
        $this->actingAs($this->admin)->get(route('admin.repartos'))->assertOk()->assertSee('Entregas de hoy')->assertSee('Total ruta');
        $this->actingAs($this->admin)->get(route('admin.modulo', 'bot'))->assertOk()->assertSee('Estadísticas del bot')->assertSee('Pedidos generados vía bot');
        $this->actingAs($this->admin)->get(route('admin.modulo', 'servicios'))->assertOk()->assertSee('Ticket promedio')->assertSee('Clientes recurrentes');
        $this->actingAs($this->admin)->get(route('admin.modulo', 'cobros'))->assertOk()->assertSee('porMetodo()', false);
        $this->actingAs($this->admin)->get(route('admin.productos'))->assertOk()->assertSee('prod-grid', false);
    }

    public function test_personalizar_guarda_tipografia_y_modo_oscuro(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.personalizar.guardar'), [
            'nombre_negocio' => 'Pollería Prueba', 'telefono' => '', 'ruc' => '', 'color_menu' => '#2B6EF6', 'color_accion' => '#F47B20',
            'panel_fuente' => 'Poppins', 'panel_modo' => 'oscuro',
        ])->assertOk();
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee("--fuente:'Poppins'", false)->assertSee('family=Poppins', false)->assertSee('"oscuro"', false);
        $this->actingAs($this->admin)->postJson(route('admin.personalizar.guardar'), [
            'nombre_negocio' => 'Pollería Prueba', 'telefono' => '', 'ruc' => '', 'color_menu' => '#2B6EF6', 'color_accion' => '#F47B20', 'panel_modo' => 'neon',
        ])->assertStatus(422);
    }
}
