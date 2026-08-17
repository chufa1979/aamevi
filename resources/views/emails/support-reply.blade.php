@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px;">
        Respondieron tu consulta <strong>{{ $ticket->subject }}</strong>, de
        {{ $ticket->course?->title }}.
    </p>

    <p style="margin:0 0 14px; padding:12px 14px; background-color:#ececec; border-left:4px solid #00b8b3;">
        {{ \Illuminate\Support\Str::limit($message->body, 400) }}
    </p>

    <x-emails.boton :href="route('classroom.ticket', $ticket)">Ver la consulta</x-emails.boton>
@endsection
