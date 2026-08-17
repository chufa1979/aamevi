@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px;">
        Completaste <strong>{{ $course->title }}</strong>. Tu certificado ya está
        emitido y lo podés descargar cuando quieras.
    </p>

    <p style="margin:0 0 14px;">
        Número: <strong style="font-family:'Courier New',monospace;">{{ $certificate->certificate_number }}</strong>
    </p>

    <x-emails.boton :href="route('classroom.certificates')">Descargar el certificado</x-emails.boton>
@endsection
