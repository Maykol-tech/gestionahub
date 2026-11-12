<?php

namespace App\Http\Requests;

use App\Models\Repartidor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta y edicion de repartidores (solo administradores). El estado activo se cambia aparte. */
class RepartidorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'telefono' => $this->filled('telefono') ? trim((string) $this->input('telefono')) : null,
            'dni' => $this->filled('dni') ? trim((string) $this->input('dni')) : null,
            'placa' => $this->filled('placa') ? strtoupper(trim((string) $this->input('placa'))) : null,
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) $this->user()?->tenant_id;
        // En edicion el parametro de ruta es {id}; en el alta no existe
        $ignorar = $this->route('id');

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]{6,20}$/',
                Rule::unique('repartidores', 'telefono')->where('tenant_id', $tenantId)->ignore($ignorar)],
            'dni' => ['nullable', 'string', 'max:12', 'regex:/^[0-9A-Za-z]{8,12}$/'],
            'vehiculo' => ['required', Rule::in(array_keys(Repartidor::VEHICULOS))],
            'placa' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9-]{5,10}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del repartidor.',
            'nombre.min' => 'El nombre es muy corto.',
            'telefono.regex' => 'El teléfono solo puede tener números, espacios, + o -.',
            'telefono.unique' => 'Ya tienes un repartidor con este teléfono.',
            'dni.regex' => 'El DNI debe tener entre 8 y 12 caracteres.',
            'vehiculo.in' => 'Elige un vehículo válido.',
            'placa.regex' => 'La placa solo puede tener letras, números y guiones (5 a 10 caracteres).',
        ];
    }
}
