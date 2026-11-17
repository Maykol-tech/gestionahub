<?php

namespace Tests\Feature;

use App\Models\BotMenu;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Editor visual de menús del bot: filas numeradas solas, editar, eliminar y formato antiguo. */
class BotMenuEditorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $t = Tenant::create(['nombre_negocio' => 'Bot', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'Admin', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');

        return $u;
    }

    public function test_crea_edita_y_elimina_con_filas(): void
    {
        $u = $this->admin();
        $this->actingAs($u)->post(route('admin.bot.menus.guardar'), [
            'disparador' => 'menu', 'titulo' => 'Hola',
            'opciones' => [['texto' => 'Precios', 'respuesta' => 'Desde S/ 10'], ['texto' => '', 'respuesta' => ''], ['texto' => 'Horario', 'respuesta' => '9 a 6']],
        ])->assertSessionHas('ok');
        $m = BotMenu::withoutGlobalScopes()->firstOrFail();
        $this->assertSame([1, 2], array_column($m->opciones, 'numero'));
        $this->assertSame('Horario', $m->opciones[1]['texto']);

        $this->actingAs($u)->put(route('admin.bot.menus.actualizar', $m->id), [
            'disparador' => 'info', 'titulo' => 'Hola de nuevo',
            'opciones' => [['texto' => 'Horario', 'respuesta' => '9 a 6'], ['texto' => 'Precios', 'respuesta' => 'Desde S/ 10'], ['texto' => 'Delivery', 'respuesta' => 'Sí']],
        ])->assertSessionHas('ok');
        $m->refresh();
        $this->assertSame('info', $m->disparador);
        $this->assertCount(3, $m->opciones);
        $this->assertSame('Horario', $m->opciones[0]['texto']);

        $this->actingAs($u)->get(route('admin.bot.menus'))->assertOk()->assertSee('Vista previa');
        $this->actingAs($u)->delete(route('admin.bot.menus.eliminar', $m->id))->assertSessionHas('ok');
        $this->assertSame(0, BotMenu::withoutGlobalScopes()->count());
    }

    public function test_valida_opcion_incompleta_repetido_y_formato_antiguo(): void
    {
        $u = $this->admin();
        $this->actingAs($u)->post(route('admin.bot.menus.guardar'), ['disparador' => 'menu', 'titulo' => 'Hola', 'opciones' => [['texto' => 'Uno', 'respuesta' => ''], ['texto' => 'Dos', 'respuesta' => 'x']]])
            ->assertSessionHasErrors('opciones');
        $this->actingAs($u)->post(route('admin.bot.menus.guardar'), ['disparador' => 'menu', 'titulo' => 'Hola', 'opciones' => "1|Uno|a\n2|Dos|b"])->assertSessionHas('ok');
        $this->actingAs($u)->post(route('admin.bot.menus.guardar'), ['disparador' => 'MENU', 'titulo' => 'Hola', 'opciones' => "1|Uno|a\n2|Dos|b"])->assertSessionHasErrors('disparador');
    }
}
