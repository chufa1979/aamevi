@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px; font-size:13px; color:#5b5b5b;">
        Nueva comunicación de <strong>{{ $course->title }}</strong>
    </p>

    <p style="margin:0 0 10px; font-size:18px; font-weight:bold;">{{ $announcement->title }}</p>

    {{-- El texto del docente viene del editor: se sanea antes de imprimirlo --}}
    <div style="margin:0 0 14px;">{!! \App\Support\Html::sanitize($announcement->body) !!}</div>

    <x-emails.boton :href="route('classroom.announcements', $course)">Ver el curso</x-emails.boton>
@endsection
