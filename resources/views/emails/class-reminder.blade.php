@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px;">
        Mañana es la clase en vivo <strong>{{ $class->title }}</strong>@if ($course), de
        {{ $course->title }}@endif.
    </p>

    <p style="margin:0 0 14px;">
        {{ $class->activation_date->translatedFormat('l d \d\e F, H:i') }} h
    </p>

    @if (filled($class->meet_link))
        <x-emails.boton :href="$class->meet_link">Entrar a la videollamada</x-emails.boton>
    @else
        <x-emails.boton :href="route('classroom.class', $class)">Ver la clase</x-emails.boton>
    @endif
@endsection
