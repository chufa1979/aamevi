@extends('layouts.classroom')

@section('title', $course->title)

@section('content')
    <h1 class="text-2xl font-medium leading-snug">{{ $course->title }}</h1>

    <p class="mt-1 text-sm text-subtle">
        {{ $course->teacher?->user?->full_name ?? 'Sin docente asignado' }}
    </p>

    <div class="card mt-5 p-5">
        <x-classroom.progress-bar
            :value="$avance"
            :label="'Avance en '.$course->title"
            :detail="collect($estados)->filter(fn ($e) => $e === \App\Enums\ClassProgressState::Completed)->count()
                .' de '.count($estados).' clases aprobadas'" />
    </div>

    <x-rich-text :html="$course->description" class="mt-6 text-sm" />

    <h2 class="sr-only">Temario</h2>

    <div class="mt-6 space-y-6">
        @foreach ($modulos as $fila)
            @php($module = $fila['module'])

            <section class="card overflow-hidden" aria-labelledby="modulo-{{ $module->id }}">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-line bg-canvas px-5 py-4">
                    <h3 id="modulo-{{ $module->id }}" class="font-medium">
                        <span class="text-subtle">Módulo {{ $module->order_number }}</span>
                        · {{ $module->title }}
                    </h3>

                    <p class="text-xs text-subtle tabular-nums">
                        {{ $fila['aprobadas'] }} de {{ $module->classes->count() }} aprobadas
                    </p>
                </div>

                <ul class="divide-y divide-line">
                    @foreach ($module->classes as $class)
                        @php($estado = $estados[$class->id] ?? \App\Enums\ClassProgressState::Available)

                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="font-medium">
                                    <span class="text-subtle tabular-nums">{{ $class->order_number }}.</span>

                                    @if ($estado->isOpen())
                                        <a href="{{ route('classroom.class', $class) }}"
                                           class="text-brand-text no-underline hover:underline">
                                            {{ $class->title }}
                                        </a>
                                    @else
                                        {{-- Sin enlace, pero listada: a diferencia del aula de
                                             referencia, que directamente las esconde y deja al
                                             alumno sin saber por qué desaparecieron --}}
                                        <span>{{ $class->title }}</span>
                                    @endif
                                </p>

                                <p class="mt-0.5 text-xs text-subtle">
                                    {{ $estado->describe($class) }}

                                    @if ($class->is_live_session)
                                        · Clase en vivo
                                    @endif
                                </p>
                            </div>

                            <x-classroom.state-badge :state="$estado" />
                        </li>
                    @endforeach
                </ul>

                @if ($fila['examen'])
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-canvas px-5 py-4">
                        <div>
                            <p class="text-sm font-medium">Examen del módulo</p>
                            <p class="mt-0.5 text-xs text-subtle">
                                {{ $fila['examenMotivo'] ?? 'Podés rendirlo cuando quieras.' }}
                            </p>
                        </div>

                        @if ($fila['examenMotivo'] === null)
                            <x-button href="{{ route('classroom.quiz', $fila['examen']) }}">Rendir el examen</x-button>
                        @endif
                    </div>
                @endif
            </section>
        @endforeach
    </div>
@endsection
