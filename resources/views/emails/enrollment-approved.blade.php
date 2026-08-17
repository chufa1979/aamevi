@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px;">
        Aprobamos tu inscripción a <strong>{{ $course->title }}</strong>. Ya podés
        entrar al aula y empezar por la primera clase.
    </p>

    <x-emails.boton :href="route('classroom.course', $course)">Ir al curso</x-emails.boton>

    <p style="margin:0; font-size:13px; color:#5b5b5b;">
        Las clases se habilitan en la fecha que figura en el cronograma, y cada una
        se abre al aprobar la anterior.
    </p>
@endsection
