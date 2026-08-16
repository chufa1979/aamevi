@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px;">
        Ya está corregido tu trabajo <strong>{{ $content->title }}</strong>.
    </p>

    {{-- La nota va en el correo, no sólo el aviso: obligar a entrar para leer
         un número es hacer perder un viaje --}}
    <p style="margin:0 0 14px;">
        Resultado: <strong>{{ $submission->status->getLabel() }}</strong>@if ($submission->grade !== null),
        con una nota de <strong>{{ rtrim(rtrim(number_format((float) $submission->grade, 2, ',', ''), '0'), ',') }}</strong>@endif.
    </p>

    @if (filled($submission->feedback))
        <p style="margin:0 0 6px; font-size:13px; text-transform:uppercase; letter-spacing:1px; color:#5b5b5b;">
            Devolución del docente
        </p>
        <p style="margin:0 0 14px; padding:12px 14px; background-color:#ececec; border-left:4px solid #00b8b3;">
            {{ $submission->feedback }}
        </p>
    @endif

    @if ($submission->allowsResubmission())
        <p style="margin:0 0 14px;">Podés volver a entregarlo desde la clase.</p>
    @endif

    <x-emails.boton :href="route('classroom.class', $content->class)">Ver la clase</x-emails.boton>
@endsection
