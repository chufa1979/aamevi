@extends('layouts.app')

@section('title', 'Verificar certificado')

@section('content')
    {{-- `x-page-hero` está pensado para una sola palabra corta (ver Ayuda):
         con dos se desborda hacia arriba, fuera de la franja de color. --}}
    <x-page-hero title="Verificar" />

    <x-section narrow>
        <p class="mb-6 text-lg font-light">
            Confirmá que un número de certificado de AAMEVi es real.
        </p>

        <form method="GET" action="{{ route('certificate.verify') }}" class="bg-card mb-8 flex flex-wrap items-end gap-3 p-5">
            <div class="grow">
                <label for="numero" class="field-label">Número de certificado</label>
                <input id="numero" name="numero" type="text" class="field font-mono"
                       value="{{ $numero }}" placeholder="AAMEVI-2026-XXXXXX" autofocus>
            </div>

            <x-button type="submit">Verificar</x-button>
        </form>

        @if ($buscado)
            @if ($certificado)
                <div class="card border-l-4 border-l-primary p-5" role="status">
                    <p class="text-sm text-primary-800">✓ Este certificado es real.</p>

                    <dl class="mt-3 space-y-1 text-sm">
                        <div>
                            <dt class="inline text-subtle">Alumno:</dt>
                            <dd class="inline">{{ $certificado->enrollment->student->user->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="inline text-subtle">Curso:</dt>
                            <dd class="inline">{{ $certificado->enrollment->course->title }}</dd>
                        </div>
                        <div>
                            <dt class="inline text-subtle">Emitido el:</dt>
                            <dd class="inline">{{ $certificado->issued_at->format('d/m/Y') }}</dd>
                        </div>
                    </dl>
                </div>
            @else
                <div class="card border-l-4 border-l-error p-5" role="alert">
                    <p class="text-sm">
                        No encontramos ningún certificado con ese número. Revisá que esté completo
                        y sin espacios de más.
                    </p>
                </div>
            @endif
        @endif
    </x-section>
@endsection
