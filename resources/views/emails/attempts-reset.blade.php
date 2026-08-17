@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px;">
        Tu docente te habilitó de nuevo
        @if ($quiz->isModuleExam())
            el examen de <strong>{{ $quiz->module?->title }}</strong>
        @else
            la autoevaluación de <strong>{{ $quiz->class?->title }}</strong>
        @endif@if ($course), de {{ $course->title }}@endif. Ya podés volver a rendirla.
    </p>

    @if (filled($reset->reason))
        <p style="margin:0 0 14px; padding:12px 14px; background-color:#ececec; border-left:4px solid #00b8b3;">
            {{ $reset->reason }}
        </p>
    @endif

    @if ($quiz->class)
        <x-emails.boton :href="route('classroom.class', $quiz->class)">Ir a la clase</x-emails.boton>
    @elseif ($course)
        <x-emails.boton :href="route('classroom.course', $course)">Ir al curso</x-emails.boton>
    @endif
@endsection
