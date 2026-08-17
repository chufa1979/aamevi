@props(['course' => null])

{{--
    Barra lateral del aula, en dos niveles.

    Fuera de un curso muestra sólo lo general; al abrir uno se suman sus
    secciones. Es la forma del aula de referencia y evita un menú donde la
    mitad de los ítems no aplican hasta que entrás a algún lado.

    Las comunicaciones son del curso —cuelgan de él— y las consultas no: el
    alumno busca «qué pregunté», no «qué pregunté en este curso». Por eso una
    está abajo y la otra arriba.
--}}
@php
    $alumno = auth()->user()->student;

    /*
     * Los pendientes del alumno. Dos consultas por pantalla del aula, las dos
     * `count()` con índice: alcanza para esto y evita que cada ítem del menú
     * pregunte por su cuenta.
     */
    $sinLeer = [
        'classroom.tickets' => app(\App\Services\SupportService::class)->unreadFor($alumno),
        'classroom.announcements' => $course === null
            ? 0
            : app(\App\Services\AnnouncementService::class)->unreadFor($alumno, $course),
    ];

    $generales = [
        ['ruta' => 'classroom.courses', 'label' => 'Mis cursos', 'icono' => 'texto'],
        ['ruta' => 'classroom.catalog', 'label' => 'Catálogo', 'icono' => 'disponible'],
        ['ruta' => 'classroom.progress', 'label' => 'Mi progreso', 'icono' => 'aprobada'],
        ['ruta' => 'classroom.certificates', 'label' => 'Certificados', 'icono' => 'certificado'],
        ['ruta' => 'classroom.tickets', 'label' => 'Consultas', 'icono' => 'evaluacion'],
    ];

    $delCurso = $course === null ? [] : [
        ['ruta' => 'classroom.course', 'params' => $course, 'label' => 'Clases', 'icono' => 'video'],
        ['ruta' => 'classroom.evaluations', 'params' => $course, 'label' => 'Mis evaluaciones', 'icono' => 'evaluacion'],
        ['ruta' => 'classroom.announcements', 'params' => $course, 'label' => 'Comunicaciones', 'icono' => 'texto'],
    ];
@endphp

<aside class="lg:w-64 lg:shrink-0">
    <nav aria-label="Secciones del aula"
         class="card overflow-hidden">
        <ul class="divide-y divide-line">
            @foreach ($generales as $item)
                @php($activo = request()->routeIs($item['ruta']))

                <li>
                    <a href="{{ route($item['ruta']) }}"
                       @if ($activo) aria-current="page" @endif
                       @class([
                           'flex items-center gap-3 px-4 py-3 text-sm no-underline transition-colors',
                           'hover:bg-line' => ! $activo,
                           'bg-primary font-medium text-ink' => $activo,
                       ])>
                        <x-ui.icon :name="$item['icono']" class="h-4 w-4 shrink-0" />
                        {{ $item['label'] }}

                        <x-classroom.unread :count="$sinLeer[$item['ruta']] ?? 0" />
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($course !== null)
            {{-- El curso abierto encabeza su propio bloque para que se entienda
                 que lo de abajo es de él y no del aula en general --}}
            <div class="border-t border-line bg-canvas px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-subtle">Curso abierto</p>
                <p class="mt-1 text-sm font-medium leading-snug">{{ $course->title }}</p>
            </div>

            <ul class="divide-y divide-line border-t border-line">
                @foreach ($delCurso as $item)
                    @php($activo = request()->routeIs($item['ruta']))

                    <li>
                        <a href="{{ route($item['ruta'], $item['params']) }}"
                           @if ($activo) aria-current="page" @endif
                           @class([
                               'flex items-center gap-3 px-4 py-3 text-sm no-underline transition-colors',
                               'hover:bg-line' => ! $activo,
                               'bg-primary font-medium text-ink' => $activo,
                           ])>
                            <x-ui.icon :name="$item['icono']" class="h-4 w-4 shrink-0" />
                            {{ $item['label'] }}

                            <x-classroom.unread :count="$sinLeer[$item['ruta']] ?? 0" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>
</aside>
