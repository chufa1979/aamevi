<?php

namespace App\Http\Requests\Classroom;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Mismas reglas que `App\Http\Requests\Auth\RegisterRequest` para estos
 * campos — la única diferencia real es el `unique` del DNI, que acá tiene
 * que ignorar la ficha del propio alumno.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'dni' => ['nullable', 'string', 'max:20', Rule::unique('students', 'dni')->ignore($this->user()->student->id)],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'graduation_date' => ['nullable', 'date', 'before_or_equal:today'],
            'university' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'membership_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Ingresá tu nombre.',
            'last_name.required' => 'Ingresá tu apellido.',
            'dni.unique' => 'Ya hay una cuenta con ese DNI.',
            'date_of_birth.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'graduation_date.before_or_equal' => 'La fecha de egreso no puede ser futura.',
        ];
    }
}
