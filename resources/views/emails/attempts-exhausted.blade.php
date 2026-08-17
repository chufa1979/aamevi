@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px;">
        <strong>{{ $alumno->full_name }}</strong> usó todos los intentos de
        @if ($quiz->isModuleExam())
            el examen de <strong>{{ $quiz->module?->title }}</strong>
        @else
            la autoevaluación de <strong>{{ $quiz->class?->title }}</strong>
        @endif
        y no la aprobó, en {{ $course?->title }}.
    </p>

    {{-- Lo que importa que sepa: sin hacer nada, ese alumno no avanza más --}}
    <p style="margin:0 0 14px; font-size:13px; color:#5b5b5b;">
        Mientras no la apruebe no puede seguir con las clases siguientes. Desde la
        solapa Intentos del curso podés ver qué respondió y devolverle los intentos.
    </p>

    <x-emails.boton :href="route('filament.profesores.resources.courses.attempts', ['record' => $course])">
        Ver los intentos del curso
    </x-emails.boton>
@endsection
