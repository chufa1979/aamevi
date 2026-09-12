@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    {{-- Texto plano de un Textarea, no HTML enriquecido: se escapa y sólo se
         preservan los saltos de línea, a diferencia de Announcement --}}
    <p style="margin:0 0 14px; white-space:pre-line;">{{ $cuerpo }}</p>
@endsection
