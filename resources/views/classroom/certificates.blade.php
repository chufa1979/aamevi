@extends('layouts.classroom')

@section('title', 'Certificados')

@section('content')
    <h1 class="mb-6 text-2xl font-medium">Certificados</h1>

    @if ($certificados->isEmpty() && $enCurso->isEmpty())
        <div class="card p-6">
            <p class="mb-4">Todavía no estás cursando nada, así que no hay certificados a la vista.</p>
            <x-button href="{{ route('classroom.catalog') }}">Ver el catálogo</x-button>
        </div>
    @endif

    @if ($certificados->isNotEmpty())
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($certificados as $certificado)
                <li class="card flex flex-col p-5">
                    <h2 class="text-lg font-medium leading-snug">
                        {{ $certificado->enrollment->course->title }}
                    </h2>

                    <p class="mt-1 text-xs text-subtle">
                        Emitido el {{ $certificado->issued_at->format('d/m/Y') }}
                    </p>

                    {{-- El número es lo que se cita al verificarlo: va legible y completo --}}
                    <p class="mt-3 font-mono text-sm">{{ $certificado->certificate_number }}</p>

                    <p class="mt-4">
                        <x-button href="{{ route('classroom.certificate', $certificado) }}">
                            Descargar PDF
                        </x-button>
                    </p>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($enCurso->isNotEmpty())
        <h2 class="mb-3 mt-8 text-lg font-medium">En camino</h2>

        <ul class="grid gap-3">
            @foreach ($enCurso as $fila)
                <li class="card flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-medium">
                            <a href="{{ route('classroom.course', $fila['course']) }}"
                               class="text-brand-text no-underline hover:underline">
                                {{ $fila['course']->title }}
                            </a>
                        </p>

                        {{-- El motivo concreto: «todavía no» no le dice si le falta
                             cursar o le falta que le corrijan --}}
                        <p class="mt-1 text-sm text-subtle">{{ $fila['falta'] }}</p>
                    </div>

                    <a href="{{ route('classroom.course', $fila['course']) }}" class="text-sm text-brand-text">
                        Ir al curso →
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
