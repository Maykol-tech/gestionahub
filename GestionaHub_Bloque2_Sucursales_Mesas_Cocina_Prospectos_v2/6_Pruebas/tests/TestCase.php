<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El store "array" vive todo el proceso: los intentos de login de una prueba no deben
        // bloquear la siguiente (ModuloAPosSeguridadTest fallaba solo en la corrida completa).
        Cache::flush();
    }
}
