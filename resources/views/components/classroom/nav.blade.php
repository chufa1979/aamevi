@props(['course' => null])

{{--
    Barra lateral del aula, en dos niveles.

    Fuera de un curso muestra sólo lo general; al abrir uno se suman sus
    secciones. Es la forma del aula de referencia y evita un menú donde la
    mitad de los ítems no aplican hasta que entrás a algún lado.

    Las secciones que todavía no existen —calificaciones, comunicaciones, mesa
    de ayuda— no figuran: un menú con ítems muertos es peor que uno corto.
--}}
@php
    $generales = [
        ['ruta' => 'classroom.courses', 'label' => 'Mis cursos', 'icono' => 'texto'],
        ['ruta' => 'classroom.catalog', 'label' => 'Catálogo', 'icono' => 'disponible'],
        ['ruta' => 'classroom.progress', 'label' => 'Mi progreso', 'icono' => 'aprobada'],
    ];

    $delCurso = $course === null ? [] : [
        ['ruta' => 'classroom.course', 'params' => $course, 'label' => 'Clases', 'icono' => 'video'],
        ['ruta' => 'classroom.evaluations', 'params' => $course, 'label' => 'Mis evaluaciones', 'icono' => 'evaluacion'],
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
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </nav>
</aside>
