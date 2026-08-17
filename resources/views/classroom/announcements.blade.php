@extends('layouts.classroom')

@section('title', 'Comunicaciones')

@section('content')
    <h1 class="mb-2 text-2xl font-medium">Comunicaciones</h1>
    <p class="mb-6 text-sm text-subtle">{{ $course->title }}</p>

    @if ($comunicaciones->isEmpty())
        <div class="card p-6">
            <p>Todavía no hay comunicaciones en este curso.</p>
            <p class="mt-2 text-sm text-subtle">
                Acá van a aparecer los avisos del docente sobre el cronograma, el material y las evaluaciones.
            </p>
        </div>
    @else
        <ul class="grid gap-4">
            @foreach ($comunicaciones as $comunicacion)
                <li class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h2 class="text-lg font-medium leading-snug">{{ $comunicacion->title }}</h2>

                        @unless ($comunicacion->isForEveryone())
                            {{-- Vale aclararlo: un aviso dirigido a una persona se
                                 lee distinto que uno para todo el curso --}}
                            <span class="rounded bg-line px-2 py-0.5 text-xs">Para vos</span>
                        @endunless
                    </div>

                    <p class="mt-1 text-xs text-subtle">
                        {{ $comunicacion->published_at->format('d/m/Y') }}
                        @if ($comunicacion->author)
                            · {{ $comunicacion->author->full_name }}
                        @endif
                    </p>

                    <x-rich-text :html="$comunicacion->body" class="mt-3 text-sm" />
                </li>
            @endforeach
        </ul>
    @endif
@endsection
