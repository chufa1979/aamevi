@extends('layouts.classroom')

@section('title', $class->title)

@section('content')
    <nav aria-label="Ruta" class="mb-4 text-xs text-subtle">
        <a href="{{ route('classroom.course', $course) }}" class="text-brand-text no-underline hover:underline">
            {{ $course->title }}
        </a>
        <span aria-hidden="true"> › </span>
        Módulo {{ $class->module->order_number }}
    </nav>

    <h1 class="text-2xl font-medium leading-snug">
        <span class="text-subtle">Clase {{ $class->order_number }}</span> · {{ $class->title }}
    </h1>

    <p class="mt-2 flex flex-wrap items-center gap-3 text-xs text-subtle">
        <span>Se habilitó el {{ $class->activation_date->format('d/m/Y') }}</span>

        @if ($class->is_live_session)
            <span class="inline-flex items-center gap-1.5 text-accent-text">
                <x-ui.icon name="vivo" class="h-3.5 w-3.5" />
                Clase en vivo
            </span>
        @endif

        @if ($completada)
            <span class="inline-flex items-center gap-1.5 text-primary-800">
                <x-ui.icon name="aprobada" class="h-3.5 w-3.5" />
                Aprobada
            </span>
        @endif
    </p>

    @if ($class->is_live_session && $class->meet_link)
        <div class="card mt-5 flex flex-wrap items-center justify-between gap-3 border-l-4 border-l-accent p-4">
            <p class="text-sm">Esta clase se dicta por videollamada.</p>
            <x-button href="{{ $class->meet_link }}" variant="accent">Entrar a la videollamada</x-button>
        </div>
    @endif

    <x-rich-text :html="$class->description" class="mt-5 text-sm" />

    @if ($class->contents->isEmpty())
        <div class="card mt-6 p-5">
            <p class="text-sm text-subtle">Esta clase todavía no tiene material cargado.</p>
        </div>
    @else
        <h2 class="sr-only">Material de la clase</h2>

        <div class="mt-6 space-y-5">
            @foreach ($class->contents as $content)
                <x-classroom.content-block :content="$content" />
            @endforeach
        </div>
    @endif

    {{-- El cierre de la clase: rendir su autoevaluación, o darla por vista si no
         tiene. Es lo que desbloquea la siguiente. --}}
    <section class="card mt-8 p-5" aria-labelledby="cierre">
        <h2 id="cierre" class="text-lg font-medium">Para terminar la clase</h2>

        @if ($quiz)
            @if ($aprobado)
                <p class="mt-2 text-sm">Ya aprobaste la autoevaluación de esta clase.</p>

                <div class="mt-4 flex flex-wrap gap-3">
                    <x-button href="{{ route('classroom.quiz', $quiz) }}" variant="ghost">Ver mis intentos</x-button>

                    @if ($siguiente)
                        <x-button href="{{ route('classroom.class', $siguiente) }}">Ir a la clase siguiente</x-button>
                    @endif
                </div>
            @elseif ($intentosRestantes > 0)
                <p class="mt-2 text-sm">
                    Rendí la autoevaluación para aprobar la clase y habilitar la siguiente.
                    Te {{ $intentosRestantes === 1 ? 'queda' : 'quedan' }}
                    {{ $intentosRestantes }} {{ $intentosRestantes === 1 ? 'intento' : 'intentos' }}.
                </p>

                <div class="mt-4">
                    <x-button href="{{ route('classroom.quiz', $quiz) }}">Rendir la autoevaluación</x-button>
                </div>
            @else
                <p class="mt-2 text-sm">
                    Usaste todos los intentos de esta autoevaluación. Hablá con tu docente para continuar.
                </p>
            @endif
        @else
            <p class="mt-2 text-sm">Esta clase no tiene autoevaluación.</p>

            @if ($completada)
                <div class="mt-4">
                    @if ($siguiente)
                        <x-button href="{{ route('classroom.class', $siguiente) }}">Ir a la clase siguiente</x-button>
                    @else
                        <x-button href="{{ route('classroom.course', $course) }}" variant="ghost">Volver al temario</x-button>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('classroom.class.complete', $class) }}" class="mt-4">
                    @csrf
                    <x-button type="submit">Marcar la clase como vista</x-button>
                </form>
            @endif
        @endif
    </section>
@endsection
