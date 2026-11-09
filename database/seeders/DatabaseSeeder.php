<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga los datos iniciales de GestionaHub (planes, roles, negocio demo,
     * usuario administrador, catalogo, clientes y pedidos).
     */
    public function run(): void
    {
        $this->call(GestionaHubSeeder::class);
        $this->call(RolesDemoSeeder::class);
    }
}