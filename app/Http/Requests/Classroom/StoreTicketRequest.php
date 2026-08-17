<?php

namespace App\Http\Requests\Classroom;

use App\Enums\EnrollmentStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Apertura de una consulta.
 *
 * El curso se valida contra los que ese alumno cursa —no contra la tabla
 * entera—: el `select` los lista, pero el que arma la petición a mano no tiene
 * por qué respetarlo. `SupportService` lo vuelve a comprobar; acá la validación
 * existe para dar un error de formulario en vez de una excepción de dominio.
 */
class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->student !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $suyos = $this->user()->student->enrollments()
            ->whereIn('status', EnrollmentStatus::ocupantes())
            ->pluck('course_id')
            ->all();

        return [
            'course_id' => ['required', 'uuid', Rule::in($suyos)],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'course_id.required' => 'Elegí de qué curso es la consulta.',
            'course_id.in' => 'Ese curso no está entre los que estás cursando.',
            'subject.required' => 'Poné un asunto.',
            'message.required' => 'Escribí tu consulta.',
        ];
    }
}
