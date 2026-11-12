<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Contraseña para salir de la pantalla de bloqueo por inactividad. */
class DesbloquearPantallaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string', 'max:200']];
    }

    public function messages(): array
    {
        return ['password.required' => 'Escribe tu contraseña para continuar.'];
    }
}
