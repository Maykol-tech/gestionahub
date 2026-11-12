<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la tabla que el panel envia para exportar a Excel (lo mismo que el usuario ve filtrado en pantalla).
 * Limites estrictos para que nadie pueda saturar el servidor con un archivo enorme.
 */
class ExportarTablaRequest extends FormRequest
{
    public const MAX_FILAS = 20000;

    public const MAX_COLUMNAS = 30;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:80'],
            'columnas' => ['required', 'array', 'min:1', 'max:' . self::MAX_COLUMNAS],
            'columnas.*.titulo' => ['required', 'string', 'max:60'],
            'columnas.*.tipo' => ['nullable', 'in:texto,moneda,numero,decimal,fecha'],
            'filas' => ['present', 'array', 'max:' . self::MAX_FILAS],
            'filas.*' => ['array', 'max:' . self::MAX_COLUMNAS],
            'filas.*.*' => ['nullable', function (string $campo, mixed $valor, \Closure $falla) {
                if (! is_scalar($valor) && $valor !== null) {
                    $falla('Cada celda debe ser texto o número.');
                } elseif (is_string($valor) && mb_strlen($valor) > 2000) {
                    $falla('Una celda supera los 2000 caracteres.');
                }
            }],
            'totales' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'columnas.required' => 'No hay columnas para exportar.',
            'filas.max' => 'Puedes exportar hasta ' . number_format(self::MAX_FILAS) . ' filas a la vez. Aplica un filtro.',
            'columnas.*.tipo.in' => 'Tipo de columna no válido.',
        ];
    }
}
