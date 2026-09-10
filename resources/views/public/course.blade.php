@extends('layouts.app')

@section('title', $course->title)

@section('content')
    {{--
        `x-page-hero` está pensado para títulos cortos y fijos como
        «Educación»: su tipografía crece con `clamp(2.5rem,10vw,7.5rem)` sobre
        una franja de altura fija, y con el título real de un curso (largo y
        variable) desbordaba el contenedor.

        Acá va el mismo degradado de marca, pero con altura libre —crece con
        el contenido en vez de recortarlo— y un tamaño de letra más moderado
        que sigue leyendo como portada sin desbordar con títulos largos.
    --}}
    <div class="border-b-[6px] border-primary bg-gradient-to-r from-pillar-blue via-primary to-pillar-green py-10 md:py-16">
        <div class="container-site-sm">
            <h1 class="font-medium text-white [font-size:clamp(1.875rem,4vw,3rem)] leading-tight">
                {{ $course->title }}
            </h1>

            @if ($course->teacher?->user)
                <p class="mt-3 text-sm text-white/80 md:text-base">
                    {{ $course->teacher->user->full_name }}
                </p>
            @endif
        </div>
    </div>

    <x-section narrow>
        <div class="grid gap-10 md:grid-cols-[1fr_300px]">
            <div class="min-w-0">
                <x-collapsible title="Acerca del curso" :open="true">
                    @if ($course->description)
                        <x-rich-text :html="$course->description" />
                    @else
                        <p class="text-sm text-subtle">Sin descripción cargada.</p>
                    @endif
                </x-collapsible>

                @if ($course->teacher?->user)
                    <x-collapsible title="Director del curso">
                        <p class="font-medium">{{ $course->teacher->user->full_name }}</p>

                        @if ($course->teacher->specialization)
                            <p class="mt-1 text-xs text-subtle">{{ $course->teacher->specialization }}</p>
                        @endif

                        @if ($course->teacher->bio)
                            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed">{{ $course->teacher->bio }}</p>
                        @endif
                    </x-collapsible>
                @endif

                @if ($course->teaching_staff)
                    <x-collapsible title="Cuerpo docente">
                        <x-bullet-list :text="$course->teaching_staff" />
                    </x-collapsible>
                @endif

                @if ($course->modules->isNotEmpty())
                    <x-collapsible title="Programa del curso">
                        <ol class="grid gap-2">
                            @foreach ($course->modules as $module)
                                <li class="flex items-center gap-3 rounded-button border border-line p-4">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">
                                        {{ $loop->iteration }}
                                    </span>
                                    <span class="font-medium">{{ $module->title }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </x-collapsible>
                @endif

                @if ($course->investment_info)
                    <x-collapsible title="Inversión del curso">
                        <p class="whitespace-pre-line text-sm leading-relaxed">{{ $course->investment_info }}</p>
                    </x-collapsible>
                @endif

                @if ($course->objectives)
                    <x-collapsible title="Objetivos del curso">
                        <x-bullet-list :text="$course->objectives" />
                    </x-collapsible>
                @endif

                @if ($course->certification_info)
                    <x-collapsible title="Certificación">
                        <p class="whitespace-pre-line text-sm leading-relaxed">{{ $course->certification_info }}</p>
                    </x-collapsible>
                @endif

                @php
                    $contacto = config('navigation.contact', []);
                @endphp

                @if ($course->enrollment_requirements || $contacto)
                    <x-collapsible title="Inscripción e Informes">
                        @if ($course->enrollment_requirements)
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-subtle">Requisitos</p>
                            <x-bullet-list :text="$course->enrollment_requirements" />
                        @endif

                        @if ($contacto)
                            <p class="mb-2 {{ $course->enrollment_requirements ? 'mt-5' : '' }} text-xs font-bold uppercase tracking-wide text-subtle">
                                Informes
                            </p>
                            <ul class="grid gap-1 text-sm">
                                @if ($contacto['email'] ?? null)
                                    <li>
                                        <a href="{{ $contacto['email'] }}" class="underline-offset-2 hover:underline hover:text-accent">
                                            {{ str_replace('mailto:', '', $contacto['email']) }}
                                        </a>
                                    </li>
                                @endif

                                @if ($contacto['whatsapp'] ?? null)
                                    <li>
                                        <a href="{{ $contacto['whatsapp'] }}" class="underline-offset-2 hover:underline hover:text-accent">
                                            WhatsApp
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </x-collapsible>
                @endif
            </div>

            {{--
                «Datos importantes»: el resumen ejecutivo que en la ficha de
                referencia aparece repetido varias veces a lo largo de la
                página. Acá va una sola vez, fijo en el costado, porque es
                justamente la información que alguien busca sin tener que leer
                todo lo demás.
            --}}
            <aside class="card h-fit p-6">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wide">Datos importantes</h2>

                <dl class="grid gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-subtle">Director del curso</dt>
                        <dd class="mt-1 font-medium">
                            {{ $course->teacher?->user?->full_name ?? 'A confirmar' }}
                        </dd>
                    </div>

                    @if ($course->start_date || $course->end_date)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-subtle">Fecha</dt>
                            <dd class="mt-1 font-medium">
                                {{ $course->start_date?->format('d/m/Y') ?? '?' }}
                                –
                                {{ $course->end_date?->format('d/m/Y') ?? '?' }}
                            </dd>
                        </div>
                    @endif

                    @if ($course->schedule_days)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-subtle">Días</dt>
                            <dd class="mt-1 font-medium">{{ $course->schedule_days }}</dd>
                        </div>
                    @endif

                    @if ($course->schedule_time)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-subtle">Horario</dt>
                            <dd class="mt-1 font-medium">{{ $course->schedule_time }}</dd>
                        </div>
                    @endif

                    @if ($course->location)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-subtle">Lugar</dt>
                            <dd class="mt-1 font-medium">{{ $course->location }}</dd>
                        </div>
                    @endif

                    @if ($course->specialties)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-subtle">Especialidad/es</dt>
                            <dd class="mt-1 font-medium">{{ $course->specialties }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-subtle">Modalidad</dt>
                        <dd class="mt-1 font-medium">{{ $course->modality->label() }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-subtle">Cupo</dt>
                        <dd class="mt-1 font-medium">
                            {{ max($course->max_students - $course->occupiedSeats(), 0) }} lugares disponibles
                        </dd>
                    </div>
                </dl>

                <div class="mt-6">
                    @auth
                        @if (auth()->user()->isStudent())
                            <form method="POST" action="{{ route('classroom.enroll', $course) }}">
                                @csrf
                                <x-button type="submit" class="w-full" cta>Inscribirme</x-button>
                            </form>
                        @endif
                    @else
                        <x-button href="{{ route('login', ['next' => url()->current()]) }}" class="w-full" cta>
                            Inscribirme
                        </x-button>
                        <p class="mt-2 text-center text-xs text-subtle">
                            Necesitás una cuenta para inscribirte.
                        </p>
                    @endauth
                </div>
            </aside>
        </div>
    </x-section>
@endsection
