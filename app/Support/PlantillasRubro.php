<?php

namespace App\Support;

/**
 * Catalogo de plantillas por rubro (GEST-83): categorias y servicios sugeridos para empezar rapido.
 * El negocio elige su rubro al registrarse o despues en Personalizacion.
 */
class PlantillasRubro
{
    public const CATALOGO = [
        'restaurante' => [
            'nombre' => 'Restaurante',
            'categorias' => ['Entradas', 'Platos de fondo', 'Bebidas', 'Postres'],
            'servicios' => ['Delivery', 'Reserva de mesa', 'Menú del día'],
        ],
        'cafeteria' => [
            'nombre' => 'Cafetería o panadería',
            'categorias' => ['Café', 'Panadería', 'Desayunos', 'Pasteles'],
            'servicios' => ['Pedido para llevar', 'Pedido por encargo'],
        ],
        'peluqueria' => [
            'nombre' => 'Peluquería o barbería',
            'categorias' => ['Cortes', 'Coloración', 'Tratamientos'],
            'servicios' => ['Corte de cabello', 'Barba', 'Tinte', 'Manicure'],
        ],
        'tienda' => [
            'nombre' => 'Tienda de productos',
            'categorias' => ['Novedades', 'Ofertas', 'Más vendidos'],
            'servicios' => ['Envío a domicilio', 'Recojo en tienda'],
        ],
        'servicios' => [
            'nombre' => 'Servicios profesionales',
            'categorias' => ['Paquetes', 'Servicios individuales'],
            'servicios' => ['Consulta inicial', 'Sesión regular'],
        ],
        'otro' => [
            'nombre' => 'Otro negocio',
            'categorias' => ['General'],
            'servicios' => [],
        ],
    ];

    public static function claves(): array
    {
        return array_keys(self::CATALOGO);
    }

    public static function de(?string $rubro): ?array
    {
        return $rubro !== null ? (self::CATALOGO[$rubro] ?? null) : null;
    }
}
