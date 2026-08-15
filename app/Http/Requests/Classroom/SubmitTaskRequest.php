<?php

namespace App\Http\Requests\Classroom;

use Illuminate\Foundation\Http\FormRequest;

/**
 * El archivo que sube el alumno para una tarea.
 *
 * El límite de 10 MB es el declarado, pero **PHP puede cortar antes**: si
 * `upload_max_filesize` del servidor es menor, el archivo no llega y estas
 * reglas nunca se ejecutan. Ver docs/DEPLOY.md.
 */
class SubmitTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real la hacen el middleware `student` y el gateo de
        // la clase en el controlador
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:pdf,doc,docx,odt', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Elegí el archivo que querés entregar.',
            'archivo.file' => 'Eso no es un archivo válido.',
            'archivo.mimes' => 'El archivo tiene que ser un PDF o un documento de texto.',
            'archivo.max' => 'El archivo no puede pesar más de 10 MB.',
        ];
    }
}
