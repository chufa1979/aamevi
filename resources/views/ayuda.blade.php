@extends('layouts.app')

@section('title', 'Ayuda - AAMEVi')

{{--
    A diferencia del aula, esta sección la tienen que poder ver todos los
    roles logueados —admin, profesor, administrativo y alumno—, no solo
    quien tiene ficha de alumno. Por eso extiende `layouts.app`, como
    `placeholder.blade.php`, y no `layouts.classroom`.
--}}
@section('content')
    <x-page-hero title="Ayuda" />

    <x-section narrow>
        <p class="mb-6 text-lg font-light">
            Videos cortos que muestran cómo usar las pantallas más comunes de tu perfil.
        </p>

        @if (empty($videos))
            <div class="card p-6">
                <p>Todavía no hay videos instructivos para tu perfil.</p>
            </div>
        @else
            <ul class="grid gap-6 sm:grid-cols-2">
                @foreach ($videos as $video)
                    <li class="card overflow-hidden">
                        <video controls preload="none" class="aspect-video w-full bg-black"
                               src="{{ asset('videos-ayuda/'.$video['file']) }}">
                        </video>

                        <div class="p-4">
                            <h2 class="font-medium leading-snug">{{ $video['title'] }}</h2>
                            <p class="mt-1 text-sm text-subtle">{{ $video['description'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-section>
@endsection
