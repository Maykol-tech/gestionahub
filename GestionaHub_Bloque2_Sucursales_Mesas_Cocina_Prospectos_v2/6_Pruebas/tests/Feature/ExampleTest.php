<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raiz no es una pagina publica: redirige al panel, que a su vez pide login.
     */
    public function test_la_raiz_manda_al_login_si_no_hay_sesion(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }
}
