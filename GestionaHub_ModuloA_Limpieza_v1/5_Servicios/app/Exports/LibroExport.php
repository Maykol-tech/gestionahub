<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

/**
 * Libro de Excel con varias hojas, cada una con el estilo de TablaExport
 * (cabecera azul #2B6EF6, texto blanco, bordes finos, moneda S/ y totales).
 */
class LibroExport implements WithMultipleSheets, WithProperties
{
    /** @param  array<int, TablaExport>  $hojas */
    public function __construct(
        private string $titulo,
        private array $hojas,
        private ?string $negocio = null,
    ) {}

    public function sheets(): array
    {
        return $this->hojas;
    }

    public function properties(): array
    {
        return [
            'creator' => 'GestionaHub',
            'company' => $this->negocio ?? 'GestionaHub',
            'title' => $this->titulo,
        ];
    }
}
