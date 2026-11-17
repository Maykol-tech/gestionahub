<?php

namespace Tests\Unit;

use App\Support\TiendaPedido;
use PHPUnit\Framework\TestCase;

/** El nombre de una linea por kilo debe mostrar el peso pedido tal cual. */
class TextoPesoTest extends TestCase
{
    public function test_gramos_y_kilos(): void
    {
        $this->assertSame('250 g', TiendaPedido::textoPeso(0.25));
        $this->assertSame('500 g', TiendaPedido::textoPeso(0.5));
        $this->assertSame('100 g', TiendaPedido::textoPeso(0.1));
        $this->assertSame('1 kg', TiendaPedido::textoPeso(1));
        $this->assertSame('1.5 kg', TiendaPedido::textoPeso(1.5));
    }
}
