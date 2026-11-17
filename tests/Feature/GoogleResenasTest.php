<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Reseñas de Google: listo para la clave del negocio; con solo el ID ya hay enlace para pedir reseñas. */
class GoogleResenasTest extends TestCase
{
    use RefreshDatabase;

    public function test_conecta_google_y_muestra_resenas_en_la_web(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response([
            'displayName' => ['text' => 'Café Luna'], 'rating' => 4.7, 'userRatingCount' => 128, 'googleMapsUri' => 'https://maps.google.com/?cid=1',
            'reviews' => [['rating' => 5, 'text' => ['text' => 'El mejor café de Lima'], 'authorAttribution' => ['displayName' => 'Rosa P.'], 'relativePublishTimeDescription' => 'hace una semana']],
        ])]);
        $t = Tenant::create(['nombre_negocio' => 'Café Luna', 'email' => uniqid() . '@t.pe', 'estado' => 'activo']);
        $t->slug = 'cafe-luna';
        $t->save();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::create(['tenant_id' => $t->id, 'name' => 'A', 'email' => uniqid() . '@t.pe', 'password' => 'Secreto123!']);
        $u->assignRole('admin');

        $this->actingAs($u)->get(route('admin.resenas'))->assertOk()->assertSee('Reseñas de Google');
        $this->actingAs($u)->post(route('admin.resenas.google'), ['google_place_id' => 'ChIJ abc', 'google_api_key' => 'mala'])
            ->assertSessionHasErrors(['google_place_id', 'google_api_key']);

        // Solo el ID: aparece el enlace para pedir reseñas, sin llamar a Google.
        $this->actingAs($u)->post(route('admin.resenas.google'), ['google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4'])->assertSessionHas('ok');
        $this->actingAs($u)->get(route('admin.resenas'))->assertSee('writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4', false);
        Http::assertNothingSent();

        $this->actingAs($u)->post(route('admin.resenas.google'), ['google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4', 'google_api_key' => 'AIzaSyD-123456789012345678901234', 'google_mostrar' => 1])->assertSessionHas('ok');
        $this->actingAs($u)->get(route('admin.resenas'))->assertSee('128 reseñas en Google')->assertSee('El mejor café de Lima');
        $this->assertStringNotContainsString('AIzaSyD', (string) \Illuminate\Support\Facades\DB::table('tenants')->where('id', $t->id)->value('ajustes') . json_encode(\App\Support\AjustesNegocio::leer($t->id)));

        auth()->logout();
        $this->get(route('publico.resenas', 'cafe-luna'))->assertOk()->assertSee('En Google')->assertSee('Rosa P.')->assertSee('También puedes dejarla en Google');
    }
}
