@extends('layouts.app')

@section('title', 'AAMEVi - Educación')

@section('content')
    {{--
        Los accesos dependen de quién mira: los del aula son de los alumnos, y a
        un docente o un administrador le daban 403. En su lugar va el panel, que
        es donde trabajan.
    --}}
    @php
        $accesos = auth()->user()->isStudent()
            ? [
                [
                    'href' => '/cursos',
                    'title' => 'Cursos',
                    'text' => 'Recorré el catálogo, inscribite y seguí las clases módulo a módulo.',
                    'color' => 'bg-pillar-blue',
                ],
                [
                    'href' => '/progreso',
                    'title' => 'Mi progreso',
                    'text' => 'Mirá qué clases completaste, tus evaluaciones y las tareas pendientes.',
                    'color' => 'bg-pillar-teal',
                ],
                [
                    'href' => '/certificados',
                    'title' => 'Certificados',
                    'text' => 'Descargá el certificado de cada curso que hayas finalizado.',
                    'color' => 'bg-pillar-green',
                ],
            ]
            : [
                [
                    'href' => auth()->user()->isAdmin() ? '/admin' : '/profesores',
                    'title' => auth()->user()->isAdmin() ? 'Administración' : 'Mis cursos',
                    'text' => auth()->user()->isAdmin()
                        ? 'Cursos, alumnos, evaluaciones y la configuración de la plataforma.'
                        : 'El material, las evaluaciones y el seguimiento de los cursos que dictás.',
                    'color' => 'bg-pillar-blue',
                ],
                [
                    'href' => '/ayuda',
                    'title' => 'Ayuda',
                    'text' => 'Dudas sobre el uso de la plataforma.',
                    'color' => 'bg-pillar-teal',
                ],
            ];
    @endphp

    <x-page-hero title="Educación" size="full" />

    <x-section
        title="Plataforma de formación AAMEVi"
        subtitle="Formación en medicina del estilo de vida para profesionales de la salud."
        narrow
    >
        {{-- Las clases van literales: Tailwind genera sólo las que encuentra
             escritas en el código, y `md:grid-cols-{{ $n }}` no existiría --}}
        <div @class([
            'grid gap-6',
            'md:grid-cols-3' => count($accesos) === 3,
            'md:grid-cols-2' => count($accesos) === 2,
        ])>
            @foreach ($accesos as $acceso)
                <a href="{{ $acceso['href'] }}"
                   class="group block bg-card no-underline transition-shadow hover:shadow-lg">
                    <div class="h-2 {{ $acceso['color'] }}"></div>
                    <div class="p-6">
                        <h3 class="mb-2 text-xl font-bold group-hover:text-accent">{{ $acceso['title'] }}</h3>
                        <p class="text-sm leading-relaxed">{{ $acceso['text'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </x-section>

    {{-- Banda de llamada a la acción, equivalente al bloque `.membresia` del sitio madre --}}
    <section>
        <h2 class="bg-ink py-2 text-center text-sm uppercase text-white">Sumate a AAMEVi</h2>
        <div class="container-site-sm py-10 text-center md:py-16">
            <p class="mx-auto max-w-xl text-xl font-light">
                Creá tu cuenta para inscribirte a los cursos y llevar el seguimiento de tu formación.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-6">
                <x-button href="/registro" cta>Quiero unirme</x-button>
                <x-button href="/login" variant="ghost" cta>Ya tengo cuenta</x-button>
            </div>
        </div>
    </section>
@endsection
