@extends('layouts.classroom')

@section('title', 'Buscar')

@section('content')
    <h1 class="mb-2 text-2xl font-medium">Buscar</h1>

    @if ($termino === '')
        <p class="mb-6 text-sm text-subtle">
            Escribí en el buscador del encabezado para encontrar un curso o una clase.
        </p>
    @else
        <p class="mb-6 text-sm text-subtle">
            Resultados para <strong>{{ $termino }}</strong>
        </p>
    @endif

    @if ($termino !== '' && mb_strlen($termino) < $minimo)
        <div class="card p-6">
            <p>Escribí al menos {{ $minimo }} letras.</p>
        </div>
    @elseif ($termino !== '' && $cursos->isEmpty() && $clases->isEmpty())
        <div class="card p-6">
            <p>No encontramos nada con «{{ $termino }}».</p>
            {{-- El buscador sólo mira lo suyo, y conviene decirlo: si no, parece
                 que el curso que le nombró un colega no existe --}}
            <p class="mt-2 text-sm text-subtle">
                La búsqueda incluye los cursos abiertos y las clases de los cursos que estás haciendo.
            </p>
        </div>
    @endif

    @if ($cursos->isNotEmpty())
        <h2 class="mb-3 text-lg font-medium">Cursos</h2>

        <ul class="mb-8 grid gap-3">
            @foreach ($cursos as $course)
                <li class="card p-4">
                    <a href="{{ route('classroom.course', $course) }}"
                       class="font-medium text-brand-text no-underline hover:underline">
                        {{ $course->title }}
                    </a>
                    <p class="mt-1 text-xs text-subtle">
                        {{ $course->teacher?->user?->full_name ?? 'Sin docente asignado' }}
                    </p>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($clases->isNotEmpty())
        <h2 class="mb-3 text-lg font-medium">Clases</h2>

        <ul class="grid gap-3">
            @foreach ($clases as $class)
                @php($cerrada = $estados[$class->getKey()] ?? null)

                <li class="card p-4">
                    @if ($cerrada === null)
                        <a href="{{ route('classroom.class', $class) }}"
                           class="font-medium text-brand-text no-underline hover:underline">
                            {{ $class->title }}
                        </a>
                    @else
                        {{-- Sin enlace a la clase: llevaría a un 403. El motivo va
                             abajo, y el curso queda a mano --}}
                        <p class="font-medium">{{ $class->title }}</p>
                    @endif

                    <p class="mt-1 text-xs text-subtle">
                        {{ $class->module?->course?->title }} · {{ $class->module?->title }}
                    </p>

                    @if ($cerrada !== null)
                        <p class="mt-2 text-sm text-subtle">{{ $cerrada }}</p>

                        @if ($class->module?->course)
                            <p class="mt-2 text-sm">
                                <a href="{{ route('classroom.course', $class->module->course) }}"
                                   class="text-brand-text">Ver el curso →</a>
                            </p>
                        @endif
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
@endsection
