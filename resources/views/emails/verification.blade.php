@extends('emails.layout')

@section('cuerpo')
    <p style="margin:0 0 14px;">Hola {{ $user->first_name }},</p>

    <p style="margin:0 0 14px;">
        Creaste una cuenta en la plataforma de formación de AAMEVi. Confirmá que
        este correo es tuyo para poder empezar.
    </p>

    <x-emails.boton :href="$enlace">Verificar mi correo</x-emails.boton>

    <p style="margin:0 0 14px; font-size:13px; color:#5b5b5b;">
        El enlace vence en {{ config('auth.verification.expire', 60) }} minutos. Si
        se te venció, entrá a la plataforma y pedí uno nuevo.
    </p>

    {{-- Quien no se registró tiene que poder ignorarlo sin hacer nada --}}
    <p style="margin:0; font-size:13px; color:#5b5b5b;">
        Si no fuiste vos, ignorá este mensaje: sin verificar, la cuenta no se usa.
    </p>
@endsection
