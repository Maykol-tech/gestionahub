<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportacion a Excel con el estilo de GestionaHub:
 *  - cabecera con fondo azul #2B6EF6, texto blanco en negrita y fila fija al hacer scroll
 *  - bordes finos en toda la tabla, filas alternadas y autofiltro
 *  - columnas autoajustadas y formato de moneda S/ 0.00 en las columnas de tipo "moneda"
 *  - fila de totales opcional (columnas de moneda y las marcadas con 'suma' => true)
 * Los textos se guardan siempre como texto: un valor como "=HYPERLINK(...)" nunca se ejecuta como formula.
 */
class TablaExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithEvents, WithHeadings, WithProperties, WithStyles, WithTitle
{
    public const AZUL = '2B6EF6';

    public const FORMATO_MONEDA = '"S/ "#,##0.00';

    public const FORMATO_NUMERO = '#,##0';

    public const FORMATO_DECIMAL = '#,##0.00';

    /**
     * @param  array<int, array{titulo: string, tipo?: string, suma?: bool}>  $columnas  tipo: texto|moneda|numero|decimal|fecha
     * @param  array<int, array<int, mixed>>  $filas
     */
    public function __construct(
        private string $titulo,
        private array $columnas,
        private array $filas,
        private bool $conTotales = true,
        private ?string $negocio = null,
    ) {}

    public function array(): array
    {
        $tipos = $this->tipos();

        return array_map(function (array $fila) use ($tipos) {
            $limpia = [];
            foreach ($tipos as $i => $tipo) {
                $v = $fila[$i] ?? null;
                $limpia[] = match ($tipo) {
                    'moneda', 'decimal' => is_numeric($v) ? round((float) $v, 2) : ($v === null || $v === '' ? null : $this->aNumero($v)),
                    'numero' => is_numeric($v) ? (float) $v : ($v === null || $v === '' ? null : $this->aNumero($v)),
                    default => $v === null ? '' : (is_bool($v) ? ($v ? 'Sí' : 'No') : (string) $v),
                };
            }

            return $limpia;
        }, $this->filas);
    }

    public function headings(): array
    {
        return array_map(fn ($c) => (string) $c['titulo'], $this->columnas);
    }

    public function title(): string
    {
        // Excel limita el nombre de la hoja a 31 caracteres y prohibe algunos simbolos
        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $this->titulo) ?: 'Datos', 0, 31);
    }

    public function properties(): array
    {
        return [
            'creator' => 'GestionaHub',
            'company' => $this->negocio ?? 'GestionaHub',
            'title' => $this->titulo,
        ];
    }

    public function columnFormats(): array
    {
        $formatos = [];
        foreach ($this->tipos() as $i => $tipo) {
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            $formato = match ($tipo) {
                'moneda' => self::FORMATO_MONEDA,
                'numero' => self::FORMATO_NUMERO,
                'decimal' => self::FORMATO_DECIMAL,
                default => null,
            };
            if ($formato) {
                $formatos[$letra] = $formato;
            }
        }

        return $formatos;
    }

    public function styles(Worksheet $hoja): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::AZUL]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento) {
                $hoja = $evento->sheet->getDelegate();
                $ultimaCol = Coordinate::stringFromColumnIndex(max(1, count($this->columnas)));
                $ultimaFila = count($this->filas) + 1;
                $rango = "A1:{$ultimaCol}{$ultimaFila}";

                $hoja->getRowDimension(1)->setRowHeight(24);
                $hoja->freezePane('A2');
                if ($ultimaFila > 1) {
                    $hoja->setAutoFilter($rango);
                }

                // Filas alternadas para leer mejor
                for ($f = 3; $f <= $ultimaFila; $f += 2) {
                    $hoja->getStyle("A{$f}:{$ultimaCol}{$f}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4F7FE');
                }

                // Fila de totales: columnas de moneda y las marcadas con 'suma' => true
                $sumables = $this->sumables();
                if ($this->conTotales && $ultimaFila > 1 && $sumables) {
                    $filaTotal = $ultimaFila + 1;
                    $hoja->setCellValueExplicit("A{$filaTotal}", 'TOTAL', DataType::TYPE_STRING);
                    foreach ($sumables as $i => $tipo) {
                        $l = Coordinate::stringFromColumnIndex($i + 1);
                        $hoja->setCellValueExplicit("{$l}{$filaTotal}", "=SUM({$l}2:{$l}{$ultimaFila})", DataType::TYPE_FORMULA);
                        $hoja->getStyle("{$l}{$filaTotal}")->getNumberFormat()->setFormatCode(match ($tipo) {
                            'moneda' => self::FORMATO_MONEDA,
                            'numero' => self::FORMATO_NUMERO,
                            default => self::FORMATO_DECIMAL,
                        });
                    }
                    $hoja->getStyle("A{$filaTotal}:{$ultimaCol}{$filaTotal}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0EAFF']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::AZUL]]],
                    ]);
                    $rango = "A1:{$ultimaCol}{$filaTotal}";
                }

                // Bordes finos en toda la tabla
                $hoja->getStyle($rango)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('C9D3E3');
                $hoja->getStyle("A2:{$ultimaCol}" . max(2, $ultimaFila))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }

    /** Los textos siempre como texto (evita inyeccion de formulas); los numeros como numero. */
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_int($value) || is_float($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);

            return true;
        }
        if ($value === null || $value === '') {
            $cell->setValueExplicit('', DataType::TYPE_NULL);

            return true;
        }
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }

    /**
     * Columnas que llevan total: las de moneda por defecto, o las que indiquen 'suma' => true|false.
     *
     * @return array<int, string> indice de columna => tipo
     */
    private function sumables(): array
    {
        $sumables = [];
        foreach ($this->tipos() as $i => $tipo) {
            $suma = $this->columnas[$i]['suma'] ?? ($tipo === 'moneda');
            if ($suma && in_array($tipo, ['moneda', 'numero', 'decimal'], true)) {
                $sumables[$i] = $tipo;
            }
        }

        return $sumables;
    }

    /** @return array<int, string> */
    private function tipos(): array
    {
        return array_map(fn ($c) => in_array($c['tipo'] ?? 'texto', ['texto', 'moneda', 'numero', 'decimal', 'fecha'], true) ? ($c['tipo'] ?? 'texto') : 'texto', $this->columnas);
    }

    /** "S/ 1,234.50" -> 1234.5 */
    private function aNumero(mixed $v): ?float
    {
        $limpio = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $v));

        return is_numeric($limpio) ? round((float) $limpio, 2) : null;
    }
}
